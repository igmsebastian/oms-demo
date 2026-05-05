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

                if (! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::PartiallyCancelled], true)) {
                    $validator->errors()->add('status', 'Only cancelled or partially cancelled orders can be refunded.');
                }

                if ((float) $this->input('amount') > (float) $order->total_amount) {
                    $validator->errors()->add('amount', 'Refund amount cannot exceed the order total.');
                }
            },
        ];
    }
}
