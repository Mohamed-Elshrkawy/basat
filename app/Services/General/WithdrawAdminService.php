<?php

namespace App\Services\General;

use App\Http\Resources\Api\General\Wallet\WithdrawRequestResource;
use App\Models\WithdrawRequest;
use Exception;

class WithdrawAdminService
{
    public function list($request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $query = WithdrawRequest::with(['wallet.payable', 'transaction'])
            ->when($request->filled('withdraw_status'), function ($q) use ($request) {
                $q->where('status', $request->withdraw_status);
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from);
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to);
            })
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $term = $request->keyword;

                $q->where(function ($query) use ($term) {
                    $query->where('bank_name', 'like', "%{$term}%")
                        ->orWhere('account_name', 'like', "%{$term}%")
                        ->orWhere('account_number', 'like', "%{$term}%")
                        ->orWhere('IBAN', 'like', "%{$term}%")
                        ->orWhereHas('wallet.payable', function ($payableQuery) use ($term) {
                            $payableQuery->where(function ($subQuery) use ($term) {
                                $subQuery->where('name', 'like', "%{$term}%")
                                    ->orWhere('full_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            });
                        });
                });
            })
            ->latest();

        $requests = $query->paginate($request->get('per_page', 15));

        return WithdrawRequestResource::collection($requests)->additional([
            'status' => 'success',
            'message' => $requests->count()
                ? __('Withdraw requests retrieved successfully')
                : __('No withdraw requests found'),
        ]);
    }

    public function approve(WithdrawRequest $withdraw, $request): \Illuminate\Http\JsonResponse
    {
        try {
            $admin = $request->admin;
            $withdraw->approve($request->get('admin_note'), $admin->id, $admin->toArray());

            return json(__('Withdraw request approved successfully.'));
        } catch (Exception $e) {
            return json($e->getMessage(), status: 'fail', headerStatus: 400);
        }
    }

    public function reject(WithdrawRequest $withdraw, $request): \Illuminate\Http\JsonResponse
    {
        try {
            $admin = $request->get('admin');
            $withdraw->reject($request->get('admin_note'), $admin->id, $admin->to_array());

            return json(__('Withdraw request rejected successfully.'));
        } catch (Exception $e) {
            return json($e->getMessage(), status: 'fail', headerStatus: 400);
        }
    }

}
