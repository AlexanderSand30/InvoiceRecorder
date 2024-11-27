<?php

namespace App\Http\Controllers\Vouchers;

use App\Http\Requests\Vouchers\GetVouchersRequest;
use App\Http\Resources\Vouchers\VoucherResource;
use App\Services\VoucherService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GetVouchersHandler
{
    public function __construct(private readonly VoucherService $voucherService) {}

    public function __invoke(GetVouchersRequest $request): AnonymousResourceCollection
    {
        $vouchers = $this->voucherService->getVouchers(
            $request->query('page'),
            $request->query('paginate'),
            $request->query('serie'),
            $request->query('number'),
            $request->query('invoiceType'),
            $request->query('currency'),
            $request->query('startDate'),
            $request->query('endDate'),
        );

        return VoucherResource::collection($vouchers);
    }
}
