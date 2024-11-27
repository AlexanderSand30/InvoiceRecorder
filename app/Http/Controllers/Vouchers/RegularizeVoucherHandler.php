<?php

namespace App\Http\Controllers\Vouchers;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\VoucherService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegularizeVoucherHandler extends Controller
{
    public function __construct(private readonly VoucherService $voucherService) {}

    public function __invoke(): JsonResponse|AnonymousResourceCollection
    {
        try {
            $vouchers = Voucher::with('lines')->get();

            $this->voucherService->regularizeVouchers($vouchers);

            return response()->json(['message' => 'Los comprobantes han sido regularizados.'], 200);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }
}
