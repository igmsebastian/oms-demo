<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order && (
            ($this->user()?->can('requestCancellation', $order) ?? false)
            || ($this->user()?->can('cancel', $order) ?? false)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:5000'],
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

                if ($order instanceof Order && $order->status === OrderStatus::Cancelled) {
                    $validator->errors()->add('status', 'This order is already cancelled. No action is needed.');
                }
            },
        ];
    }
}
