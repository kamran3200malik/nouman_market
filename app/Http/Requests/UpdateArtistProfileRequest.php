<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArtistProfileRequest extends FormRequest
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
            'business_name' => 'required|string|max:255',
            'professional_type' => 'nullable|string|max:100',
            'years_of_experience' => 'nullable|integer|min:0|max:60',
            'bio' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:3000',
            'specializations' => 'nullable',
            'country' => 'nullable|string|max:100',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'home_service_available' => 'nullable|boolean',
            'home_service_fee' => 'nullable|numeric|min:0',
            'max_service_distance' => 'nullable|numeric|min:0',
            'service_areas' => 'nullable',
        ];
    }
}
