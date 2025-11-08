<?php

namespace App\Services\General;

use App\Http\Requests\Api\General\ListRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneralCrudService
{
    private string $name;
    private string $resource;
    private string $model;

    public function __construct(string $name, string $resource, string $model)
    {
        $this->name = $name;
        $this->resource = $resource;
        $this->model = $model;
    }

    /**
     * List items with filtering, searching, and pagination
     */
    public function list(
        ListRequest $request,
        int $paginate = 20,
        bool $isPaginated = true,
        ?array $search = null,
        ?\Closure $closure = null,
        bool $all = false,
        bool $debug = false
    ) {
        $query = $this->buildQuery($request, $search, $closure);

        if ($debug) {
            return $query->dd();
        }

        if ($all) {
            return $query->get();
        }

        return $isPaginated
            ? $query->paginate($paginate)
            : $query->take($paginate)->get();
    }

    /**
     * Build the query with filters and search
     */
    private function buildQuery(ListRequest $request, ?array $search, ?\Closure $closure): Builder
    {
        return $this->model::query()
            ->when(
                request('type'),
                fn($query) => $query->type(request('type'))
            )
            ->when(
                $request->keyword,
                fn($query) => $this->applySearch($query, $request->keyword, $search)
            )
            ->when($closure, fn($query) => $closure($query))
            ->orderBy('created_at', $request->order ?? 'desc');
    }

    /**
     * Apply search functionality to the query
     */
    private function applySearch(Builder $query, string $keyword, ?array $search): Builder
    {
        if (!$search || !is_array($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword, $search) {
            foreach ($search as $key => $value) {
                $this->processSearchField($q, $key, $value, $keyword);
            }
        });
    }

    /**
     * Process individual search field
     */
    private function processSearchField(Builder $query, $key, $value, string $keyword): void
    {
        if (is_array($value)) {
            $this->handleArrayValue($query, $value, $keyword);
        } elseif (is_string($key) && str_contains($key, ':')) {
            $this->handleRelationSearch($query, $key, $keyword);
        } elseif (is_string($key) && $value === 'translatable') {
            $query->orWhereTranslationLike($key, "%{$keyword}%");
        } elseif (is_string($value)) {
            $query->orWhere($value, 'LIKE', "%{$keyword}%");
        }
    }

    /**
     * Handle array values in search
     */
    private function handleArrayValue(Builder $query, array $value, string $keyword): void
    {
        foreach ($value as $i => $v) {
            if (is_string($i)) {
                if ($v === 'translatable') {
                    $query->orWhereTranslationLike($i, "%{$keyword}%");
                } else {
                    $query->orWhereHas($v, function ($subQuery) use ($i, $keyword) {
                        if (str_contains($i, '.')) {
                            $column = explode('.', $i)[0];
                            $subQuery->orWhereTranslationLike($column, "%{$keyword}%");
                        } else {
                            $subQuery->orWhere($i, 'LIKE', "%{$keyword}%");
                        }
                    });
                }
            } else {
                $query->orWhere($v, 'LIKE', "%{$keyword}%");
            }
        }
    }

    /**
     * Handle relation-based search
     */
    private function handleRelationSearch(Builder $query, string $key, string $keyword): void
    {
        [$relation, $columns] = explode(':', $key, 2);

        $query->orWhereHas($relation, function ($subQuery) use ($columns, $keyword) {
            if (str_contains($columns, ',')) {
                foreach (explode(',', $columns) as $column) {
                    $subQuery->orWhere(trim($column), 'LIKE', "%{$keyword}%");
                }
            } else {
                $subQuery->where($columns, 'LIKE', "%{$keyword}%");
            }
        });
    }

    /**
     * Save or update a model
     */
    public function save(
        $request,
        ?Model $model = null,
        ?\Closure $closure = null,
        array|string $except = []
    ): JsonResponse {
        try {
            DB::beginTransaction();

            $data = is_array($request) ? $request : $request->validated();
            $filteredData = Arr::except($data, (array) $except);

            $model = $this->model::updateOrCreate(
                ['id' => $model?->id],
                $filteredData
            );

            if ($closure) {
                $closure($model);
            }

            DB::commit();

            return $this->successResponse(
                $this->resource::make($model->refresh()),
                __(':name saved successfully', ['name' => $this->name])
            );

        } catch (Throwable $exception) {
            DB::rollBack();
            return $this->handleException($exception);
        }
    }

    /**
     * Show a single model
     */
    public function show(Model $model): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->resource::make($model),
                __(':name fetched successfully', ['name' => $this->name])
            );
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * Update model status
     */
    public function updateStatus(Model $model, string $column = 'status'): JsonResponse
    {
        try {
            DB::beginTransaction();

            $model->update([$column => !$model->{$column}]);

            DB::commit();

            return $this->successResponse(
                $this->resource::make($model),
                __(':name :column changed successfully', [
                    'name' => $this->name,
                    'column' => __($column)
                ])
            );
        } catch (Throwable $exception) {
            DB::rollBack();
            return $this->handleException($exception);
        }
    }

    /**
     * Delete a model
     */
    public function destroy(Model $model, ?\Closure $closure = null): JsonResponse
    {
        try {
            DB::beginTransaction();

            if ($closure) {
                $closure($model);
            }

            $model->delete();

            DB::commit();

            return $this->successResponse(
                null,
                __(':name deleted successfully', ['name' => $this->name])
            );
        } catch (Throwable $exception) {
            DB::rollBack();
            return $this->handleException($exception);
        }
    }

    /**
     * Handle exceptions consistently
     */
    private function handleException(Throwable $exception): JsonResponse
    {
        Log::error('CRUD Service Error: ' . $exception->getMessage(), [
            'service' => static::class,
            'model' => $this->model,
            'trace' => $exception->getTraceAsString()
        ]);

        return json(
            __('Server Error'),
            status: 'fail',
            headerStatus: 500
        );
    }

    /**
     * Create consistent success response
     */
    private function successResponse($data, string $message): JsonResponse
    {
        return json($data, $message);
    }

    /**
     * Get model instance
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get resource class
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * Get service name
     */
    public function getName(): string
    {
        return $this->name;
    }
}
