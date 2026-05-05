<?php

namespace App\Http\Requests;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PartialCancelOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('orderItem');

        return $item instanceof OrderItem && ($this->user()?->can('partiallyCancel', $item->order) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $item = $this->route('orderItem');

                if (! $item instanceof OrderItem) {
                    return;
                }

                $available = $item->quantity - $item->cancelled_quantity;

                if ((int) $this->input('quantity') > $available) {
                    $validator->errors()->add('quantity', 'Cancellation quantity exceeds the available item quantity.');
                }
            },
        ];
    }
}
