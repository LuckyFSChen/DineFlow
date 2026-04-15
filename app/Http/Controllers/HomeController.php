<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private const MAX_DISTANCE_KM = 30;

    public function index(Request $request)
    {
        $keyword = trim((string) $request->get('keyword', ''));
        $userLatitude = $this->parseLatitude($request->query('lat'));
        $userLongitude = $this->parseLongitude($request->query('lng'));
        $hasUserLocation = $userLatitude !== null && $userLongitude !== null;

        $query = Store::query()
            ->where('is_active', 1)
            ->where('takeout_qr_enabled', 1);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('address', 'like', "%{$keyword}%")
                ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        if ($hasUserLocation) {
            $distanceSql = '6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))';

            $query
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('stores.*')
                ->selectRaw($distanceSql . ' as distance_km', [$userLatitude, $userLongitude, $userLatitude])
                ->whereRaw($distanceSql . ' <= ?', [$userLatitude, $userLongitude, $userLatitude, self::MAX_DISTANCE_KM])
                ->orderBy('distance_km')
                ->orderBy('name');
        }

        $stores = $query
            ->when(! $hasUserLocation, function ($q) {
                $q->orderBy('name');
            })
            ->paginate(12)
            ->withQueryString();

        return view('home', compact('stores', 'keyword', 'userLatitude', 'userLongitude', 'hasUserLocation'));
    }

    private function parseLatitude(mixed $value): ?float
    {
        $latitude = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $latitude < -90 || $latitude > 90) {
            return null;
        }

        return (float) $latitude;
    }

    private function parseLongitude(mixed $value): ?float
    {
        $longitude = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($longitude === false || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return (float) $longitude;
    }
}
