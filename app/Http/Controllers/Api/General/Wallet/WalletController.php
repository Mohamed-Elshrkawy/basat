<?php

namespace App\Http\Controllers\Api\General\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\General\Wallet\WithdrawRequest;
use App\Services\General\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $service){}

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->index($request);
    }

    public function transactions(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->service->transactions($request);
    }

    public function withdrawRequest(WithdrawRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->withdrawRequest($request);
    }
}
