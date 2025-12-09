<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFranchiseeRequest extends FormRequest
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
            'status' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'corporation_name' => 'nullable',
            'tin' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'profile_photo' => Rule::requiredIf(
                $this->input('current_step') === 'basic-details' && $this->hasFile('profile_photo')
            ),
            'first_name' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'middle_name' => 'nullable',
            'last_name' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'name_suffix' => 'nullable',
            'birthdate' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'gender' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'marital_status' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'spouse_name' => 'nullable',
            'nationality' => 'nullable',
            'religion' => 'nullable',
            'spouse_birthdate' => 'nullable',
            'wedding_date' => 'nullable',
            'number_of_children' => 'nullable',
            'residential_address_province' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'residential_address_city' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'residential_address_barangay' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'residential_address_street' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'residential_address_postal' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'contact_number' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'contact_number_2' => 'nullable',
            'contact_number_3' => 'nullable',
            'email' => Rule::requiredIf($this->input('current_step') === 'basic-details'),
            'email_2' => 'nullable',
            'email_3' => 'nullable',
            'fm_point_person' => 'nullable',
            'fm_contact_number' => 'nullable',
            'fm_contact_number_2' => 'nullable',
            'fm_contact_number_3' => 'nullable',
            'fm_email_address' => 'nullable',
            'fm_email_address_2' => 'nullable',
            'fm_email_address_3' => 'nullable',
            'fm_district_manager' => 'nullable',
            'fm_region' => 'nullable',
            'date_start_bakery_management_seminar' => 'nullable',
            'date_end_bakery_management_seminar' => 'nullable',
            'date_start_bread_baking_course' => 'nullable',
            'date_end_bread_baking_course' => 'nullable',
            'operations_manual_number' => 'nullable',
            'operations_manual_release' => 'nullable',
            'date_applied' => 'nullable',
            'date_approved' => 'nullable',
            'date_separated' => 'nullable',
            'background' => 'nullable',
            'custom_background' => 'nullable',
            'education' => 'nullable',
            'occupation' => 'nullable',
            'course' => 'nullable',
            'source_of_information' => 'nullable',
            'custom_source_of_information' => 'nullable',
            'legacy' => 'nullable',
            'generation' => 'nullable',
            'custom_generation' => 'nullable',
            'remarks' => 'nullable',
            'application_step' => 'nullable',
            'is_draft' => 'nullable',
            'current_step' => 'nullable',
            'documents_personal_financial' => 'nullable',
            'documents_corporate' => 'nullable',
            'documents_legal_agreements_special' => 'nullable',
            'documents_official_correspondence' => 'nullable',
            'documents_others' => 'nullable',
        ];

        return array_intersect_key($rules, request()->all());
    }
}
