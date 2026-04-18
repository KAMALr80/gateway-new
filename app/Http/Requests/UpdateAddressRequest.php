<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label'      => ['sometimes', 'nullable', 'string', 'max:50'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'string', 'max:100'],
            'company'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_1'  => ['sometimes', 'string', 'max:255'],
            'address_2'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'city'       => ['sometimes', 'string', 'max:100'],
            'state'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'postcode'   => ['sometimes', 'string', 'max:20'],
            'country'    => ['sometimes', 'string', 'size:2'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
