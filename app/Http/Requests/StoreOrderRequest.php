<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_address_id' => ['nullable', 'ulid', 'exists:user_addresses,id'],
            'shipping_address_line_1' => ['required_without:user_address_id', 'string', 'max:255'],
            'shipping_address_line_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required_without:user_address_id', 'string', 'max:255'],
            'shipping_country' => ['required_without:user_address_id', 'string', 'max:255'],
            'shipping_post_code' => ['required_without:user_address_id', 'string', 'max:255'],
            'shipping_full_address' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'ulid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $addressId = $this->input('user_address_id');

                if (! $addressId) {
                    return;
                }

                $belongsToUser = UserAddress::whereKey($addressId)
                    ->where('user_id', $this->user()?->id)
                    ->exists();

                if (! $belongsToUser) {
                    $validator->errors()->add('user_address_id', 'Choose one of your saved addresses.');
                }
            },
        ];
    }
}
