<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddStoreHistoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'field' => [
                'required',
                'string',
                Rule::in(['jbmis_code', 'cluster_code'])
            ],
            'value' => 'nullable',
            'effective_at' => 'required|date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'field.required' => 'The field name is required.',
            'field.in' => 'The selected field is not supported for history tracking.',
            'effective_at.required' => 'The effectivity date is required.',
            'effective_at.date' => 'The effectivity date must be a valid date.',
        ];
    }
}