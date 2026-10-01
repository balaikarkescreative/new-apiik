<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapSearchService
{
    /**
     * Search coordinates by place name, address, or raw latitude, longitude.
     *
     * @return array<string, string> Key: "lat,lng", Value: Display Name
     */
    public static function search(string $query): array
    {
        $query = trim($query);

        if (empty($query)) {
            return [];
        }

        // 1. Direct coordinate check: e.g. "-6.9039, 107.6186" or "-6.9039 107.6186"
        if (preg_match('/^([-+]?\d{1,2}(?:\.\d+)?)[,\s]+([-+]?\d{1,3}(?:\.\d+)?)$/', $query, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];

            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $coordKey = "{$lat},{$lng}";
                $label = "Titik Koordinat: {$lat}, {$lng}";
                Cache::put('map_label_'.md5($coordKey), $label, now()->addHours(24));

                return [
                    $coordKey => $label,
                ];
            }
        }

        if (mb_strlen($query) < 3) {
            return [];
        }

        $cacheKey = 'map_search_'.md5(mb_strtolower($query));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
            $results = [];

            // 2. Query OpenStreetMap Nominatim
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'APIIK-BKK-Bandung/1.0 (info@bkkbandung.net)',
                ])
                    ->timeout(4)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'json',
                        'limit' => 6,
                        'countrycodes' => 'id',
                        'addressdetails' => 1,
                    ]);

                if ($response->successful()) {
                    $items = $response->json();
                    if (is_array($items) && count($items) > 0) {
                        foreach ($items as $item) {
                            $key = $item['lat'].','.$item['lon'];
                            $name = $item['display_name'];
                            $results[$key] = $name;
                            Cache::put('map_label_'.md5($key), $name, now()->addHours(24));
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Nominatim search failed: '.$e->getMessage());
            }

            // 3. Fallback to Photon (Komoot) if Nominatim returns empty or failed
            if (empty($results)) {
                try {
                    $response = Http::timeout(4)->get('https://photon.komoot.io/api/', [
                        'q' => $query,
                        'limit' => 6,
                    ]);

                    if ($response->successful()) {
                        $features = $response->json('features') ?? [];
                        foreach ($features as $feature) {
                            $props = $feature['properties'] ?? [];
                            $geom = $feature['geometry']['coordinates'] ?? [];

                            if (count($geom) === 2) {
                                $lng = $geom[0];
                                $lat = $geom[1];
                                $key = "{$lat},{$lng}";

                                $name = $props['name'] ?? '';
                                $street = $props['street'] ?? '';
                                $city = $props['city'] ?? $props['district'] ?? $props['county'] ?? '';
                                $state = $props['state'] ?? '';

                                $label = implode(', ', array_filter([$name, $street, $city, $state]));
                                if (empty($label)) {
                                    $label = "{$lat}, {$lng}";
                                }

                                $results[$key] = $label;
                                Cache::put('map_label_'.md5($key), $label, now()->addHours(24));
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Photon search failed: '.$e->getMessage());
                }
            }

            return $results;
        });
    }

    /**
     * Get label for a selected coordinate key.
     */
    public static function getLabel(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $cached = Cache::get('map_label_'.md5($value));
        if ($cached) {
            return $cached;
        }

        $parts = explode(',', $value);
        if (count($parts) === 2) {
            return 'Lokasi Terpilih ('.trim($parts[0]).', '.trim($parts[1]).')';
        }

        return $value;
    }
}
