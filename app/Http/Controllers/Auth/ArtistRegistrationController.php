<?php

namespace App\Http\Controllers\Auth;

use App\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ArtistApprovalService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArtistRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ArtistRegister');
    }

    public function store(Request $request, ArtistApprovalService $artistApprovalService): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', 'digits:11'],
            'password' => ['required', 'confirmed', 'min:8'],
            'business_name' => ['required', 'string', 'max:255'],
            'professional_type' => ['required', 'string', Rule::in([
                'makeup_artist',
                'bridal_makeup_artist',
                'hair_stylist',
                'nail_artist',
                'facial_specialist',
                'mehndi_artist',
                'lash_artist',
                'brow_artist',
                'beauty_salon',
                'spa',
                'other',
            ])],
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'bio' => ['required', 'string', 'max:1000'],
            'full_description' => ['nullable', 'string', 'max:5000'],
            'specializations' => ['nullable', 'string'],
            'languages' => ['nullable', 'string'],
            'home_service_available' => ['nullable'],
            'home_service_fee' => ['nullable', 'numeric', 'min:0'],
            'max_service_distance' => ['nullable', 'integer', 'min:1', 'max:500'],
            'service_areas' => ['nullable', 'string', 'max:1000'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'cnic_front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'cnic_back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'professional_certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'business_registration' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'other_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'portfolio_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'agreed_to_terms' => ['nullable'],
        ]);

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, $artistApprovalService) {
            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'is_verified' => false,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('artist');

            event(new Registered($user));

            $location = \App\Models\Location::firstOrCreate(
                ['country_code' => 'PK'],
                ['name' => 'Pakistan', 'is_active' => true]
            );

            $cityId = null;
            if (!empty($validated['city'])) {
                $city = \App\Models\City::firstOrCreate(
                    ['name' => $validated['city'], 'location_id' => $location->id],
                    ['slug' => \Illuminate\Support\Str::slug($validated['city']), 'is_active' => true]
                );
                $cityId = $city->id;
            }

            $areaId = null;
            if (!empty($validated['area']) && $cityId) {
                $area = \App\Models\Area::firstOrCreate(
                    ['name' => $validated['area'], 'city_id' => $cityId],
                    ['slug' => \Illuminate\Support\Str::slug($validated['area']), 'is_active' => true]
                );
                $areaId = $area->id;
            }

            $user->update([
                'city' => $validated['city'] ?? null,
                'area' => $validated['area'] ?? null,
                'address' => $validated['address'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);

            $serviceAreas = [];
            if (!empty($validated['service_areas'])) {
                $serviceAreas = is_array($validated['service_areas']) 
                    ? $validated['service_areas'] 
                    : array_map('trim', explode(',', $validated['service_areas']));
            }

            $profileData = [
                'business_name' => $validated['business_name'],
                'professional_type' => $validated['professional_type'],
                'years_of_experience' => $validated['years_of_experience'],
                'bio' => $validated['bio'],
                'full_description' => $validated['full_description'] ?? null,
                'specializations' => $validated['specializations'] ? (is_array($validated['specializations']) ? $validated['specializations'] : json_decode($validated['specializations'], true)) : [],
                'languages' => $validated['languages'] ? (is_array($validated['languages']) ? $validated['languages'] : json_decode($validated['languages'], true)) : ['Urdu', 'English'],
                'home_service_available' => filter_var($request->input('home_service_available'), FILTER_VALIDATE_BOOLEAN),
                'home_service_fee' => $validated['home_service_fee'] ?? 0,
                'max_service_distance' => $validated['max_service_distance'] ?? null,
                'service_areas' => $serviceAreas,
                'city_id' => $cityId,
                'area_id' => $areaId,
                'approval_status' => ApprovalStatus::PENDING->value,
                'is_active' => false,
                'is_verified' => false,
            ];

            $documents = [
                'cnic_front' => $request->file('cnic_front'),
                'cnic_back' => $request->file('cnic_back'),
                'professional_certificate' => $request->file('professional_certificate'),
                'business_registration' => $request->file('business_registration'),
                'other_document' => $request->file('other_document'),
            ];

            $artistProfile = $artistApprovalService->submitForApproval($user, $profileData, array_filter($documents));

            if ($request->hasFile('profile_image')) {
                $profilePath = $request->file('profile_image')->store('artists/profile', 'public');
                $user->update(['avatar' => $profilePath]);
            }

            if ($request->hasFile('cover_image')) {
                $coverPath = $request->file('cover_image')->store('artists/covers', 'public');
                $artistProfile->update(['cover_image' => $coverPath]);
            }

            if ($request->hasFile('portfolio_images')) {
                foreach ($request->file('portfolio_images', []) as $image) {
                    $path = $image->store('artists/portfolio', 'public');

                    $artistProfile->portfolios()->create([
                        'image_path' => $path,
                        'is_active' => true,
                    ]);
                }
            }

            return $user;
        });

        Auth::login($user);

        return redirect()->route('artist.pending-approval')
            ->with('success', 'Your salon application has been submitted and is currently under review by our onboarding team.');
    }
}
