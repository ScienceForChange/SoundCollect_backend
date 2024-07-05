<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreObservationRequest extends FormRequest
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
            'Leq' => ['sometimes'],
            'LAeqT' => ['sometimes'],
            'LAmax' => ['sometimes'],
            'LAmin' => ['sometimes'],
            'L90' => ['sometimes'],
            'L10' => ['sometimes'],
            'sharpness_S' => ['sometimes'],
            'loudness_N' => ['sometimes'],
            'roughtness_R' => ['sometimes'],
            'fluctuation_strength_F' => ['sometimes'],
            'images.*' => ['sometimes'],
            'latitude' => ['required', 'string'],
            'longitude' => ['required', 'string'],
            'sound_types' => ['sometimes'],
            'quiet' => ['sometimes'],
            'cleanliness' => ['sometimes'],
            'accessibility' => ['sometimes'],
            'safety' => ['sometimes'],
            'influence' => ['sometimes'],
            'landmark' => ['sometimes'],
            'protection' => ['sometimes'],
            'pleasant' => ['sometimes'],
            'chaotic' => ['sometimes'],
            'vibrant' => ['sometimes'],
            'uneventful' => ['sometimes'],
            'calm' => ['sometimes'],
            'annoying' => ['sometimes'],
            'eventfull' => ['sometimes'],
            'monotonous' => ['sometimes'],
            'overall' => ['sometimes'],
            'user_id' => ['required','exists:users,id'],
            'path' => ['required', 'string'],
        ];
    }
}
