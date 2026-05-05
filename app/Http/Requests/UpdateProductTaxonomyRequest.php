<?php

namespace App\Http\Requests;

use App\Services\ProductTaxonomyService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductTaxonomyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $table = $this->tableName();
        $recordId = (string) $this->route('record');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($recordId)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($table, 'slug')->ignore($recordId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:50'],
            'hex_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function tableName(): string
    {
        $model = app(ProductTaxonomyService::class)->modelFor((string) $this->route('module'));

        return (new $model)->getTable();
    }
}
