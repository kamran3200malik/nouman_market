<?php

namespace Tests\Unit;

use App\Services\LocationService;
use Tests\TestCase;

class LocationServiceTest extends TestCase
{
    private LocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locationService = new LocationService();
    }

    public function test_calculates_distance_correctly(): void
    {
        // Distance between Karachi and Lahore (approximately 1,200 km)
        $karachiLat = 24.8607;
        $karachiLon = 67.0011;
        $lahoreLat = 31.5204;
        $lahoreLon = 74.3587;

        $distance = $this->locationService->calculateDistance(
            $karachiLat,
            $karachiLon,
            $lahoreLat,
            $lahoreLon
        );

        // Geodesic distance is approximately 1,033 km (driving distance is ~1,200 km)
        $this->assertGreaterThan(1000, $distance);
        $this->assertLessThan(1100, $distance);
    }

    public function test_distance_between_same_coordinates_is_zero(): void
    {
        $lat = 24.8607;
        $lon = 67.0011;

        $distance = $this->locationService->calculateDistance($lat, $lon, $lat, $lon);

        $this->assertEquals(0, $distance);
    }

    public function test_gets_bounding_box(): void
    {
        $lat = 24.8607;
        $lon = 67.0011;
        $radius = 10; // 10 km

        $boundingBox = $this->locationService->getBoundingBox($lat, $lon, $radius);

        $this->assertArrayHasKey('min_lat', $boundingBox);
        $this->assertArrayHasKey('max_lat', $boundingBox);
        $this->assertArrayHasKey('min_lon', $boundingBox);
        $this->assertArrayHasKey('max_lon', $boundingBox);

        $this->assertLessThan($lat, $boundingBox['min_lat']);
        $this->assertGreaterThan($lat, $boundingBox['max_lat']);
        $this->assertLessThan($lon, $boundingBox['min_lon']);
        $this->assertGreaterThan($lon, $boundingBox['max_lon']);
    }

    public function test_filters_artists_within_radius(): void
    {
        $centerLat = 24.8607;
        $centerLon = 67.0011;
        $radius = 5; // 5 km

        $artistLocations = [
            (object) ['id' => 1, 'latitude' => 24.8610, 'longitude' => 67.0015], // ~0.5 km
            (object) ['id' => 2, 'latitude' => 24.8700, 'longitude' => 67.0100], // ~1.5 km
            (object) ['id' => 3, 'latitude' => 25.0000, 'longitude' => 67.5000], // ~50 km
        ];

        $nearbyArtists = $this->locationService->findArtistsWithinRadius(
            $centerLat,
            $centerLon,
            $radius,
            $artistLocations
        );

        $this->assertCount(2, $nearbyArtists);
        $this->assertEquals(1, $nearbyArtists[0]->id);
        $this->assertEquals(2, $nearbyArtists[1]->id);
    }
}
