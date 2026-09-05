<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeolocationController extends Controller
{
    /**
     * Detect user location based on client/public IP address.
     */
    public function detect(Request $request): JsonResponse
    {
        $clientIp = $request->ip();

        // If local or private network IP, query public IP from external service
        $isPrivateIp = filter_var(
            $clientIp,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;

        $targetIp = $isPrivateIp ? '' : $clientIp;

        // Try Provider 1: ipwho.is
        try {
            $url = $targetIp ? "https://ipwho.is/{$targetIp}" : "https://ipwho.is/";
            $response = Http::withoutVerifying()->timeout(4)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['success']) && !empty($data['latitude']) && !empty($data['longitude'])) {
                    $city = $data['city'] ?? '';
                    $region = $data['region'] ?? '';
                    $country = $data['country'] ?? '';
                    $parts = array_filter([$city, $region, $country]);

                    return response()->json([
                        'success' => true,
                        'source' => 'ip',
                        'latitude' => (float) $data['latitude'],
                        'longitude' => (float) $data['longitude'],
                        'city' => $city,
                        'area' => $region ?: $city,
                        'state' => $region,
                        'country' => $country,
                        'postcode' => $data['postal'] ?? '',
                        'fullAddress' => implode(', ', $parts) ?: 'Pakistan',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ipwho.is failed: ' . $e->getMessage());
        }

        // Try Provider 2: freeipapi.com
        try {
            $url = $targetIp ? "https://freeipapi.com/api/json/{$targetIp}" : "https://freeipapi.com/api/json";
            $response = Http::withoutVerifying()->timeout(4)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    $city = $data['cityName'] ?? '';
                    $region = $data['regionName'] ?? '';
                    $country = $data['countryName'] ?? '';
                    $parts = array_filter([$city, $region, $country]);

                    return response()->json([
                        'success' => true,
                        'source' => 'ip',
                        'latitude' => (float) $data['latitude'],
                        'longitude' => (float) $data['longitude'],
                        'city' => $city,
                        'area' => $region ?: $city,
                        'state' => $region,
                        'country' => $country,
                        'postcode' => $data['zipCode'] ?? '',
                        'fullAddress' => implode(', ', $parts) ?: 'Pakistan',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('freeipapi.com failed: ' . $e->getMessage());
        }

        // Try Provider 3: ipapi.co
        try {
            $url = $targetIp ? "https://ipapi.co/{$targetIp}/json/" : "https://ipapi.co/json/";
            $response = Http::withoutVerifying()->timeout(4)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['latitude']) && !empty($data['longitude'])) {
                    $city = $data['city'] ?? '';
                    $region = $data['region'] ?? '';
                    $country = $data['country_name'] ?? '';
                    $parts = array_filter([$city, $region, $country]);

                    return response()->json([
                        'success' => true,
                        'source' => 'ip',
                        'latitude' => (float) $data['latitude'],
                        'longitude' => (float) $data['longitude'],
                        'city' => $city,
                        'area' => $region ?: $city,
                        'state' => $region,
                        'country' => $country,
                        'postcode' => $data['postal'] ?? '',
                        'fullAddress' => implode(', ', $parts) ?: 'Pakistan',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ipapi.co failed: ' . $e->getMessage());
        }

        // Fallback default coordinates (Islamabad, Pakistan) if all IP lookups are unreachable
        return response()->json([
            'success' => true,
            'source' => 'default',
            'latitude' => 33.6844,
            'longitude' => 73.0479,
            'city' => 'Islamabad',
            'area' => 'Islamabad Capital Territory',
            'state' => 'Islamabad',
            'country' => 'Pakistan',
            'postcode' => '44000',
            'fullAddress' => 'Islamabad, Pakistan',
        ]);
    }

    /**
     * Reverse geocode coordinates to human readable address.
     */
    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->input('latitude') ?? $request->input('lat');
        $lon = $request->input('longitude') ?? $request->input('lon');

        if (!$lat || !$lon) {
            return response()->json(['success' => false, 'message' => 'Coordinates required.'], 422);
        }

        // Try BigDataCloud
        try {
            $response = Http::withoutVerifying()->timeout(5)->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                'latitude' => $lat,
                'longitude' => $lon,
                'localityLanguage' => 'en',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $city = $data['city'] ?? $data['locality'] ?? $data['principalSubdivision'] ?? '';
                $locality = $data['locality'] ?? '';
                $state = $data['principalSubdivision'] ?? '';
                $country = $data['countryName'] ?? '';
                $postcode = $data['postcode'] ?? '';

                $parts = [];
                if ($locality && $locality !== $city) $parts[] = $locality;
                if ($city) $parts[] = $city;
                if ($state && $state !== $city && !in_array($state, $parts)) $parts[] = $state;
                if ($country && !in_array($country, $parts)) $parts[] = $country;

                return response()->json([
                    'success' => true,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lon,
                    'city' => $city,
                    'area' => $locality ?: $state,
                    'state' => $state,
                    'country' => $country,
                    'postcode' => $postcode,
                    'fullAddress' => implode(', ', $parts) ?: "{$city}, {$country}",
                    'raw' => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Backend BigDataCloud reverse geocode failed: ' . $e->getMessage());
        }

        // Try OpenStreetMap Nominatim with proper User-Agent
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'BeautyApp/1.0 (contact@beauty.local)',
                'Accept-Language' => 'en',
            ])->timeout(5)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $lat,
                'lon' => $lon,
                'zoom' => 18,
                'addressdetails' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $addr = $data['address'] ?? [];

                $road = $addr['road'] ?? $addr['street'] ?? $addr['footway'] ?? '';
                $houseNumber = $addr['house_number'] ?? $addr['building'] ?? '';
                $neighbourhood = $addr['neighbourhood'] ?? $addr['suburb'] ?? $addr['residential'] ?? '';
                $area = $neighbourhood ?: ($addr['city_district'] ?? $addr['county'] ?? '');
                $city = $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['state_district'] ?? $addr['state'] ?? '';
                $state = $addr['state'] ?? '';
                $country = $addr['country'] ?? '';
                $postcode = $addr['postcode'] ?? '';

                $parts = [];
                if ($houseNumber) $parts[] = $houseNumber;
                if ($road) $parts[] = $road;
                if ($neighbourhood && $neighbourhood !== $road) $parts[] = $neighbourhood;
                if ($city && !in_array($city, $parts)) $parts[] = $city;
                if ($country && !in_array($country, $parts)) $parts[] = $country;

                return response()->json([
                    'success' => true,
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lon,
                    'road' => $road,
                    'neighbourhood' => $neighbourhood,
                    'area' => $area,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'postcode' => $postcode,
                    'fullAddress' => implode(', ', $parts) ?: ($data['display_name'] ?? "{$lat}, {$lon}"),
                    'raw' => $data,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Backend Nominatim reverse geocode failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'latitude' => (float) $lat,
            'longitude' => (float) $lon,
            'city' => '',
            'area' => '',
            'state' => '',
            'country' => '',
            'postcode' => '',
            'fullAddress' => "Lat: " . round($lat, 5) . ", Lon: " . round($lon, 5),
            'raw' => null,
        ]);
    }
}
