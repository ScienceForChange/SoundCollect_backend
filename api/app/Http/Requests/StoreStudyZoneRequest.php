<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudyZoneRequest extends FormRequest
{

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'admin_user_id' => auth('sanctum')->user()->id,
        ]);
    }


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
            'id' => ["sometimes", "nullable", "integer", "exists:sound_zones,id"],
            'admin_user_id' => ['required','exists:admin_users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['string'],
            'conclusion' => ['string'],
            'coordinates' => ['required', 'array'],
            'coordinates.*' => ['required', 'string'],
            'start_date' => 'required|date',
            'end_date' => 'required|date',

            'collaborators' => ['array'],
            'collaborators.*.collaborator_name' => ['required', 'string'],
            //'collaborators.*.logo' => ['file', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'collaborators.*.contact_name' => ['required', 'string'],
            'collaborators.*.contact_email' => ['required', 'email'],
            'collaborators.*.contact_phone' => ['required', 'string'],

            'documents' => ['array'],
            'documents.*.name' => ['required', 'string'],
            //'documents.*.document' => ['file'],
            //'documents.*.type' => ['string'],

        ];
    }
}
