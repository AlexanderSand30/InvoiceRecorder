<?php

namespace App\Http\Resources\Vouchers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherAmountsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            "currency_code" => $this->resource->currency_code,
            "total" => floatval($this->resource->total)
        ];
    }
}
