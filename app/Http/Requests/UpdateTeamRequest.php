<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
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
        $rules = [
            'name' => 'nullable',
            'email' => 'nullable|email',
            'user_role_id' => 'nullable',
            'status' => 'nullable',
            'photo' => 'nullable',
        ];

        return array_intersect_key($rules, request()->all());
    }
}
