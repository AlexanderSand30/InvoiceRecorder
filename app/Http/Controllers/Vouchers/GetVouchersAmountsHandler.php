<?php

namespace App\Http\Controllers\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vouchers\VoucherAmountsResource;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetVouchersAmountsHandler extends Controller
{
    public function __construct(private readonly VoucherService $voucherService) {}

    public function __invoke(): AnonymousResourceCollection
    {
        $vouchers = $this->voucherService->getVouchersAmounts();

        return VoucherAmountsResource::collection($vouchers);
    }
}
