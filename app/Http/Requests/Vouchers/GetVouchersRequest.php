<?php

namespace App\Http\Requests\Vouchers;

use Illuminate\Foundation\Http\FormRequest;

class GetVouchersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'startDate' => 'required',
            'endDate' => 'required|after_or_equal:startDate',
            'page' => ['required', 'int', 'gt:0'],
            'paginate' => ['required', 'int', 'gt:0'],
        ];
    }
}
