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
            'user_id' => $this->user()->id,
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
            'user_id' => ['required','exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['string'],
            'conclusion' => ['string'],
            'coordinates' => ['required', 'array'],
            'coordinates.*' => ['required', 'string'],
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'deleted' => 'boolean',
        ];
    }
}
