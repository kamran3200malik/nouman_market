<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare input for validation.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('artist_id') && !$this->has('artist_profile_id')) {
            $mergeData['artist_profile_id'] = $this->artist_id;
        }

        if ($this->has('date') && !$this->has('booking_date')) {
            $mergeData['booking_date'] = $this->date;
        }

        if ($this->has('time') && !$this->has('booking_time')) {
            $mergeData['booking_time'] = $this->time;
        }

        if ($this->has('home_address') && !$this->has('customer_address')) {
            $mergeData['customer_address'] = $this->home_address;
        }

        if ($this->has('home_area') && !$this->has('customer_area')) {
            $mergeData['customer_area'] = $this->home_area;
        }

        if ($this->has('notes') && !$this->has('customer_notes')) {
            $mergeData['customer_notes'] = $this->notes;
        }

        // Support both single service_id and multiple service_ids
        if ($this->has('service_ids') && is_array($this->service_ids) && count($this->service_ids) > 0) {
            $mergeData['service_id'] = $this->service_ids[0];
        } elseif ($this->has('service_id') && !$this->has('service_ids')) {
            $mergeData['service_ids'] = [$this->service_id];
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'artist_profile_id' => 'required|exists:artist_profiles,id',
            'service_id' => 'required|exists:services,id',
            'service_ids' => 'nullable|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
            'duration_minutes' => 'nullable|integer|min:15|max:720',
            'service_type' => 'required|in:salon,home',
            'customer_address' => 'required_if:service_type,home|nullable|string|max:500',
            'customer_area' => 'required_if:service_type,home|nullable|string|max:255',
            'customer_latitude' => 'nullable|numeric|between:-90,90',
            'customer_longitude' => 'nullable|numeric|between:-180,180',
            'customer_notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'artist_profile_id.required' => 'Please select an artist.',
            'service_id.required' => 'Please select at least one service.',
            'service_ids.min' => 'Please select at least one service.',
            'booking_date.required' => 'Please select a booking date.',
            'booking_date.after_or_equal' => 'Booking date cannot be in the past.',
            'booking_time.required' => 'Please select a booking time.',
            'service_type.required' => 'Please select service type.',
            'customer_address.required_if' => 'Address is required for home service.',
            'customer_area.required_if' => 'Area is required for home service.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $artistProfile = \App\Models\ArtistProfile::find($this->artist_profile_id);

            // Validate artist is approved and active
            if ($artistProfile && ($artistProfile->approval_status !== 'approved' || ($artistProfile->is_active !== null && !$artistProfile->is_active))) {
                $validator->errors()->add('artist_profile_id', 'This artist is not currently available for booking.');
            }

            // Validate all selected services
            $serviceIds = $this->service_ids ?: [$this->service_id];
            $services = \App\Models\Service::whereIn('id', $serviceIds)->get();

            foreach ($services as $service) {
                if ((int)$service->artist_profile_id !== (int)$this->artist_profile_id || ($service->is_active !== null && !$service->is_active)) {
                    $validator->errors()->add('service_ids', "The service '{$service->name}' is not available for booking.");
                }

                if ($this->service_type === 'home' && !$artistProfile->home_service_available && !$service->home_service_available) {
                    $validator->errors()->add('service_type', "Service '{$service->name}' does not provide home service.");
                }

                if ($this->service_type === 'salon' && !$service->salon_service_available) {
                    $validator->errors()->add('service_type', "Service '{$service->name}' is not available at the salon.");
                }
            }

            // Validate that the booking date is not on a closed weekly leave day or holiday
            if ($artistProfile && $this->booking_date) {
                $carbonDate = \Carbon\Carbon::parse($this->booking_date);
                $dayName = strtolower($carbonDate->format('l'));
                $dayNumber = $carbonDate->dayOfWeek;

                $isHoliday = $artistProfile->holidays()->whereDate('date', $carbonDate->toDateString())->exists();
                if ($isHoliday) {
                    $validator->errors()->add('booking_date', "The salon is closed on {$carbonDate->format('M d, Y')} (Holiday). Please select an active working day.");
                }

                $weeklyAvail = $artistProfile->availabilities()
                    ->where(function ($q) use ($dayName, $dayNumber) {
                        $q->where('day_of_week', $dayName)
                          ->orWhere('day_of_week', (string)$dayNumber);
                    })
                    ->first();

                if ($weeklyAvail && !$weeklyAvail->is_available) {
                    $capDay = ucfirst($dayName);
                    $validator->errors()->add('booking_date', "The salon is closed on {$capDay}s (Weekly Off / Leave). Please select an available date.");
                }
            }
        });
    }
}
