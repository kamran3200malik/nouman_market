<?php

namespace App\Services;

class LocationService
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Find artists within a given radius of a location
     */
    public function findArtistsWithinRadius(float $lat, float $lon, float $radiusKm, array $artistLocations): array
    {
        $nearbyArtists = [];
        
        foreach ($artistLocations as $artist) {
            if ($artist->latitude && $artist->longitude) {
                $distance = $this->calculateDistance($lat, $lon, $artist->latitude, $artist->longitude);
                
                if ($distance <= $radiusKm) {
                    $artist->distance = $distance;
                    $nearbyArtists[] = $artist;
                }
            }
        }
        
        // Sort by distance
        usort($nearbyArtists, function($a, $b) {
            return $a->distance <=> $b->distance;
        });
        
        return $nearbyArtists;
    }
    
    /**
     * Get bounding box coordinates for a given radius
     * Useful for database queries
     */
    public function getBoundingBox(float $lat, float $lon, float $radiusKm): array
    {
        $earthRadius = 6371;
        $latDelta = rad2deg($radiusKm / $earthRadius);
        $lonDelta = rad2deg($radiusKm / $earthRadius / cos(deg2rad($lat)));
        
        return [
            'min_lat' => $lat - $latDelta,
            'max_lat' => $lat + $latDelta,
            'min_lon' => $lon - $lonDelta,
            'max_lon' => $lon + $lonDelta,
        ];
    }
}
