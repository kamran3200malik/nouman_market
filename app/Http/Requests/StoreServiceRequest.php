<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('artist');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'price' => 'required|numeric|min:0|max:999999.99',
            'discount_price' => 'nullable|numeric|min:0|max:999999.99|lt:price',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'is_active' => 'boolean',
            'home_service_available' => 'boolean',
            'salon_service_available' => 'boolean',
            'booking_required' => 'boolean',
            'advance_payment_required' => 'boolean',
            'advance_payment_amount' => 'nullable|numeric|min:0|max:999999.99',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'name.required' => 'Service name is required.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be at least 0.',
            'duration_minutes.required' => 'Duration is required.',
            'duration_minutes.min' => 'Duration must be at least 15 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 8 hours.',
            'discount_price.lt' => 'Discount price must be less than regular price.',
            'image.image' => 'Service image must be a valid image file.',
            'image.max' => 'Service image cannot exceed 5MB.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'artist_profile_id' => auth()->user()->artistProfile->id,
        ]);
    }
}
