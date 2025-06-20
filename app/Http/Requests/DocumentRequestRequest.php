<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // API key middleware handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'learning_reference_number' => 'required|string|max:255',
            'name_of_student' => 'required|string|max:255',
            'last_schoolyear_attended' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'grade' => 'required|string|max:50',
            'section' => 'required|string|max:50',
            'major' => 'nullable|string|max:255',
            'adviser' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',

            // Individual person requesting validation
            'person_requesting_name' => 'required|string|max:255',
            'request_for' => 'required|in:SF10,ENROLLMENT_CERT,DIPLOMA,CAV,ENG. INST.,CERT OF GRAD,OTHERS',
            'signature_url' => 'required|url|max:500',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'learning_reference_number.required' => 'Learning reference number is required.',
            'name_of_student.required' => 'Student name is required.',
            'last_schoolyear_attended.required' => 'Last school year attended is required.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be male, female, or other.',
            'grade.required' => 'Grade is required.',
            'section.required' => 'Section is required.',
            'adviser.required' => 'Adviser name is required.',
            'contact_number.required' => 'Contact number is required.',
            'person_requesting_name.required' => 'Person requesting name is required.',
            'request_for.required' => 'Document type is required.',
            'request_for.in' => 'Document type must be one of: SF10, ENROLLMENT_CERT, '.
                'DIPLOMA, CAV, ENG. INST., CERT OF GRAD, OTHERS.',
            'signature_url.required' => 'Signature URL is required.',
            'signature_url.url' => 'Signature must be a valid URL.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'learning_reference_number' => 'learning reference number',
            'name_of_student' => 'student name',
            'last_schoolyear_attended' => 'last school year attended',
            'person_requesting_name' => 'person requesting name',
            'request_for' => 'document type',
            'signature_url' => 'signature',
        ];
    }
}
