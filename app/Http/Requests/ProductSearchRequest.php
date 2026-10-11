<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('in_stock')) {
            $this->merge([
                'in_stock' => filter_var($this->in_stock, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'q'           => ['nullable', 'string', 'max:100'],
            'min_price'   => ['nullable', 'numeric', 'min:0'],
            'max_price'   => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'in_stock'    => ['nullable', 'boolean'],
            'sort_by'     => ['nullable', 'string', 'in:price,created_at'],
            'sort_order'  => ['nullable', 'string', 'in:asc,desc'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
