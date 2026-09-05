<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {}
    
    public function searchNearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|min:-90|max:90',
            'longitude' => 'required|numeric|min:-180|max:180',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);
        
        $lat = $request->latitude;
        $lon = $request->longitude;
        $radius = $request->radius ?? 50; // Default 50km radius
        
        // Get bounding box for efficient database query
        $boundingBox = $this->locationService->getBoundingBox($lat, $lon, $radius);
        
        // Query artists within bounding box
        $artists = ArtistProfile::where('approval_status', 'approved')
            ->where('is_verified', true)
            ->where('latitude', '>=', $boundingBox['min_lat'])
            ->where('latitude', '<=', $boundingBox['max_lat'])
            ->where('longitude', '>=', $boundingBox['min_lon'])
            ->where('longitude', '<=', $boundingBox['max_lon'])
            ->with('user')
            ->get();
        
        // Calculate exact distances and filter by radius
        $nearbyArtists = $this->locationService->findArtistsWithinRadius($lat, $lon, $radius, $artists->toArray());
        
        return response()->json([
            'artists' => $nearbyArtists,
            'center' => [
                'latitude' => $lat,
                'longitude' => $lon,
            ],
            'radius' => $radius,
        ]);
    }
    
    public function geocode(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string',
        ]);
        
        // This would typically use a geocoding service like Google Maps API
        // For now, return a placeholder response
        return response()->json([
            'latitude' => null,
            'longitude' => null,
            'formatted_address' => $request->address,
            'message' => 'Geocoding service not configured. Please use manual coordinates.',
        ]);
    }
}
