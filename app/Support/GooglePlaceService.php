<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GooglePlaceService
{
    public function geocodeAddress(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $apiKey = (string) config('services.google_places.api_key');
        $endpoint = (string) config('services.google_places.endpoint');
        $timeout = (int) config('services.google_places.timeout', 8);

        if ($apiKey === '' || $endpoint === '') {
            return null;
        }

        try {
            $response = Http::timeout(max($timeout, 1))
                ->acceptJson()
                ->get($endpoint, [
                    'input' => $address,
                    'inputtype' => 'textquery',
                    'fields' => 'geometry/location,name',
                    'language' => app()->getLocale(),
                    'key' => $apiKey,
                ]);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return null;
        }

        $candidate = Arr::first((array) Arr::get($payload, 'candidates', []));
        if (! is_array($candidate)) {
            return null;
        }

        $lat = Arr::get($candidate, 'geometry.location.lat');
        $lng = Arr::get($candidate, 'geometry.location.lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'latitude' => round((float) $lat, 7),
            'longitude' => round((float) $lng, 7),
            'place_name' => (string) Arr::get($candidate, 'name', ''),
        ];
    }
}
