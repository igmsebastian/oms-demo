<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && ($this->user()?->can('refund', $order) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $order = $this->route('order');

                if (! $order instanceof Order) {
                    return;
                }

                if (! in_array($order->status, [OrderStatus::Delivered, OrderStatus::Cancelled, OrderStatus::PartiallyCancelled], true)) {
                    $validator->errors()->add('status', 'Refunds are only available for delivered, cancelled, or partially cancelled orders.');
                }

                if ((float) $this->input('amount') > (float) $order->total_amount) {
                    $validator->errors()->add('amount', 'Enter a refund amount that is not higher than the order total.');
                }
            },
        ];
    }
}
