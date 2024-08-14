<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudyZoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['string'],
            'conclusion' => ['string'],
            'coordinates' => ['required', 'array'],
            'coordinates.*' => ['required', 'string'],
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ];
    }
}
