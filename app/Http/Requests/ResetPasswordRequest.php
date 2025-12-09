<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ResetPasswordRequest extends FormRequest
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
        // $team = $this->route('team');

        return [
            //            'current_password' => [
            //                'required',
            //                function ($attribute, $value, $fail) use ($team) {
            //                    if (!Hash::check($value, $team->password)) {
            //                        $fail(__('The provided password is incorrect.'));
            //                    }
            //                },
            //            ],
            'new_password' => ['required', 'string', 'min:8'],
        ];
    }
}
