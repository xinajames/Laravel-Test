<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCoordinatedStoreHistoryRequest extends FormRequest
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
            'cluster_code' => 'required|string|max:255',
            'jbmis_code' => 'required|string|max:255',
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
            'cluster_code.required' => 'The cluster code is required.',
            'cluster_code.string' => 'The cluster code must be a string.',
            'cluster_code.max' => 'The cluster code may not be greater than 255 characters.',
            'jbmis_code.required' => 'The JBMIS code is required.',
            'jbmis_code.string' => 'The JBMIS code must be a string.',
            'jbmis_code.max' => 'The JBMIS code may not be greater than 255 characters.',
            'effective_at.required' => 'The effectivity date is required.',
            'effective_at.date' => 'The effectivity date must be a valid date.',
        ];
    }
}