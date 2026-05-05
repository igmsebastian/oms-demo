<?php

namespace App\Http\Requests;

use App\Enums\RefundStockDisposition;
use App\Models\OrderRefund;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $refund = $this->route('refund');

        return $refund instanceof OrderRefund && ($this->user()?->can('refund', $refund->order) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stock_disposition' => ['required', Rule::in(RefundStockDisposition::completionValues())],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
