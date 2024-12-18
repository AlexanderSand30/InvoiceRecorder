<?php

namespace App\Http\Controllers\Vouchers;

use App\Services\VoucherService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeleteVoucherHandler
{
    public function __construct(private readonly VoucherService $voucherService) {}

    public function __invoke(string $id): JsonResponse|AnonymousResourceCollection
    {
        try {
            $voucher = $this->voucherService->deleteVoucher($id);
            return $voucher;
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }
}
