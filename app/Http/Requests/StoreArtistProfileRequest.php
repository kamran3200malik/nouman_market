<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistProfileRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => 'nullable|string|max:255',
            'professional_type' => 'required|in:makeup_artist,bridal_makeup_artist,hair_stylist,nail_artist,facial_specialist,mehndi_artist,lash_artist,brow_artist,beauty_salon,spa,other',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'bio' => 'nullable|string|max:500',
            'full_description' => 'nullable|string',
            'specializations' => 'nullable|array',
            'specializations.*' => 'string|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'cover_image' => 'nullable|image|max:5120',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'home_service_available' => 'boolean',
            'home_service_fee' => 'nullable|numeric|min:0',
            'max_service_distance' => 'nullable|integer|min:1|max:100',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'professional_type.required' => 'Please select your professional type.',
            'years_of_experience.required' => 'Please specify your years of experience.',
            'years_of_experience.min' => 'Years of experience must be at least 0.',
            'years_of_experience.max' => 'Years of experience cannot exceed 50.',
            'cover_image.image' => 'Cover image must be a valid image file.',
            'cover_image.max' => 'Cover image cannot exceed 5MB.',
        ];
    }
}
