<?php

namespace App\Http\Controllers\Api\Client\Child;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ChildController extends Controller
{
    /**
     * عرض جميع أطفال العميل
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $children = Child::where('parent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'phone' => $child->phone,
                    'gender' => $child->gender,
                    'birth_date' => $child->birth_date?->format('Y-m-d'),
                    'age' => $child->age,
                    'profile_image' => $child->getFirstMediaUrl('profile_image'),
                    'created_at' => $child->created_at->toDateTimeString(),
                ];
            });

        return json($children, __('Children retrieved successfully'));
    }

    /**
     * إضافة طفل جديد
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => 'nullable|date|before:today',
            'profile_image' => 'nullable|image|max:5120',
        ], [
            'name.required' => __('Child name is required'),
            'gender.required' => __('Gender is required'),
            'gender.in' => __('Gender must be male or female'),
            'birth_date.before' => __('Birth date must be in the past'),
            'profile_image.image' => __('Profile image must be a valid image'),
            'profile_image.max' => __('Profile image must not exceed 5MB'),
        ]);

        DB::beginTransaction();

        try {
            $user = $request->user();

            // إنشاء الطفل
            $child = Child::create([
                'parent_id' => $user->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'] ?? null,
            ]);

            // رفع الصورة إن وجدت
            if ($request->hasFile('profile_image')) {
                $child->addMediaFromRequest('profile_image')
                    ->toMediaCollection('profile_image');
            }

            DB::commit();

            return json([
                'id' => $child->id,
                'name' => $child->name,
                'phone' => $child->phone,
                'gender' => $child->gender,
                'birth_date' => $child->birth_date?->format('Y-m-d'),
                'age' => $child->age,
                'profile_image' => $child->getFirstMediaUrl('profile_image'),
                'created_at' => $child->created_at->toDateTimeString(),
            ],
                __('Child added successfully')
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create child: ' . $e->getMessage());
            return json(__('Failed to add child'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * عرض تفاصيل طفل محدد
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $child = Child::where('parent_id', $user->id)->find($id);

        if (!$child) {
            return json(__('Child not found'), status: 'fail', headerStatus: 404);
        }

        return json([
            'id' => $child->id,
            'name' => $child->name,
            'phone' => $child->phone,
            'gender' => $child->gender,
            'birth_date' => $child->birth_date?->format('Y-m-d'),
            'age' => $child->age,
            'profile_image' => $child->getFirstMediaUrl('profile_image'),
            'created_at' => $child->created_at->toDateTimeString(),
            'updated_at' => $child->updated_at->toDateTimeString(),
        ],
            __('Child details retrieved successfully')
        );
    }

    /**
     * تحديث بيانات طفل
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $child = Child::where('parent_id', $user->id)->find($id);

        if (!$child) {
            return json(__('Child not found'), status: 'fail', headerStatus: 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => ['sometimes', 'required', Rule::in(['male', 'female'])],
            'birth_date' => 'nullable|date|before:today',
            'profile_image' => 'nullable|image|max:5120', // 5MB max
        ], [
            'name.required' => __('Child name is required'),
            'gender.required' => __('Gender is required'),
            'gender.in' => __('Gender must be male or female'),
            'birth_date.before' => __('Birth date must be in the past'),
            'profile_image.image' => __('Profile image must be a valid image'),
            'profile_image.max' => __('Profile image must not exceed 5MB'),
        ]);

        DB::beginTransaction();

        try {
            // تحديث البيانات
            $child->update([
                'name' => $validated['name'] ?? $child->name,
                'phone' => $validated['phone'] ?? $child->phone,
                'gender' => $validated['gender'] ?? $child->gender,
                'birth_date' => $validated['birth_date'] ?? $child->birth_date,
            ]);

            // تحديث الصورة إن وجدت
            if ($request->hasFile('profile_image')) {
                // حذف الصورة القديمة
                $child->clearMediaCollection('profile_image');

                // رفع الصورة الجديدة
                $child->addMediaFromRequest('profile_image')
                    ->toMediaCollection('profile_image');
            }

            DB::commit();

            return json([
                'id' => $child->id,
                'name' => $child->name,
                'phone' => $child->phone,
                'gender' => $child->gender,
                'birth_date' => $child->birth_date?->format('Y-m-d'),
                'age' => $child->age,
                'profile_image' => $child->getFirstMediaUrl('profile_image'),
                'updated_at' => $child->updated_at->toDateTimeString(),

            ],
                __('Child updated successfully')
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update child: ' . $e->getMessage());
            return json(__('Failed to update child'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * حذف طفل
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $child = Child::where('parent_id', $user->id)->find($id);

        if (!$child) {
            return json(__('Child not found'), status: 'fail', headerStatus: 404);
        }

        try {
            // حذف الصور المرتبطة
            $child->clearMediaCollection('profile_image');

            // حذف الطفل
            $child->delete();

            return json(__('Child deleted successfully'));

        } catch (\Exception $e) {
            Log::error('Failed to delete child: ' . $e->getMessage());
            return json(__('Failed to delete child'), status: 'fail', headerStatus: 500);
        }
    }
}
