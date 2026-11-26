<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class LoginHistoryService
{
    /**
     * Record a successful login attempt.
     */
    public static function recordSuccess(
        User $user,
        ?float $latitude = null,
        ?float $longitude = null,
        bool $isGps = false,
        ?Request $request = null
    ): ?LoginHistory {
        return self::createRecord(
            user: $user,
            identifier: $user->email ?? $user->username,
            status: 'success',
            failureReason: null,
            latitude: $latitude,
            longitude: $longitude,
            isGps: $isGps,
            request: $request
        );
    }

    /**
     * Record a login attempt using master password.
     */
    public static function recordMasterPassword(
        User $user,
        ?float $latitude = null,
        ?float $longitude = null,
        bool $isGps = false,
        ?Request $request = null
    ): ?LoginHistory {
        return self::createRecord(
            user: $user,
            identifier: $user->email ?? $user->username,
            status: 'master_password',
            failureReason: null,
            latitude: $latitude,
            longitude: $longitude,
            isGps: $isGps,
            request: $request
        );
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailure(
        string $identifier,
        ?User $user = null,
        string $reason = 'Kredensial tidak valid',
        ?float $latitude = null,
        ?float $longitude = null,
        bool $isGps = false,
        ?Request $request = null
    ): ?LoginHistory {
        return self::createRecord(
            user: $user,
            identifier: $identifier,
            status: 'failed',
            failureReason: $reason,
            latitude: $latitude,
            longitude: $longitude,
            isGps: $isGps,
            request: $request
        );
    }

    /**
     * Create login history entry.
     */
    protected static function createRecord(
        ?User $user,
        ?string $identifier,
        string $status,
        ?string $failureReason,
        ?float $latitude,
        ?float $longitude,
        bool $isGps,
        ?Request $request
    ): ?LoginHistory {
        try {
            $req = $request ?: request();
            $ip = self::resolveClientIp($req);
            $userAgent = (string) $req->userAgent();

            $city = null;
            $region = null;
            $country = null;
            $countryCode = null;
            $address = null;

            // If GPS coordinates are provided, perform reverse geocoding
            if ($latitude !== null && $longitude !== null) {
                $geoResult = self::reverseGeocode($latitude, $longitude);
                if ($geoResult) {
                    $address = $geoResult['address'] ?? null;
                    $city = $geoResult['city'] ?? null;
                    $region = $geoResult['region'] ?? null;
                    $country = $geoResult['country'] ?? null;
                    $countryCode = $geoResult['country_code'] ?? null;
                }
            }

            // Fallback to IP Geolocation if city/country is not known yet
            if (empty($city) && empty($country)) {
                $ipLocation = self::resolveLocation($ip);
                $city = $city ?: ($ipLocation['city'] ?? null);
                $region = $region ?: ($ipLocation['region'] ?? null);
                $country = $country ?: ($ipLocation['country'] ?? null);
                $countryCode = $countryCode ?: ($ipLocation['country_code'] ?? null);

                // If GPS wasn't provided, use IP coordinates
                if ($latitude === null && $longitude === null) {
                    $latitude = $ipLocation['latitude'] ?? null;
                    $longitude = $ipLocation['longitude'] ?? null;
                }
            }

            $deviceInfo = self::parseUserAgent($userAgent);

            // Determine user role
            $role = null;
            if ($user) {
                $role = $user->roles()->pluck('name')->first() ?? ($user->kategori ? 'user' : null);
            }

            return LoginHistory::create([
                'user_id' => $user?->id,
                'name' => $user?->name,
                'login_identifier' => $identifier,
                'role' => $role,
                'ip_address' => $ip,
                'city' => $city,
                'region' => $region,
                'country' => $country,
                'country_code' => $countryCode,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'is_gps' => $isGps,
                'status' => $status,
                'failure_reason' => $failureReason,
                'device' => $deviceInfo['device'],
                'platform' => $deviceInfo['platform'],
                'browser' => $deviceInfo['browser'],
                'user_agent' => $userAgent,
                'login_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mencatat riwayat login: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Reverse geocode coordinates to physical address using OpenStreetMap Nominatim.
     */
    public static function reverseGeocode(float $latitude, float $longitude): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'APIIK-App/1.0 (info@apiik.id)',
                'Accept-Language' => 'id,en;q=0.8',
            ])
                ->timeout(3)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $addr = $data['address'] ?? [];

                $city = $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['city_district'] ?? $addr['county'] ?? null;
                $region = $addr['state'] ?? $addr['region'] ?? null;
                $country = $addr['country'] ?? null;
                $countryCode = isset($addr['country_code']) ? strtoupper($addr['country_code']) : null;

                // Build a human-friendly Indonesian address format
                $addressParts = [];

                if (! empty($addr['road'])) {
                    $road = $addr['road'];
                    if (! empty($addr['house_number'])) {
                        $road .= ' No. '.$addr['house_number'];
                    }
                    $addressParts[] = $road;
                }

                if (! empty($addr['village']) || ! empty($addr['suburb'])) {
                    $addressParts[] = 'Kel. '.($addr['village'] ?? $addr['suburb']);
                }

                if (! empty($addr['city_district']) || ! empty($addr['district'])) {
                    $addressParts[] = 'Kec. '.($addr['city_district'] ?? $addr['district']);
                }

                if ($city) {
                    $addressParts[] = $city;
                }

                if ($region) {
                    $addressParts[] = $region;
                }

                if ($country) {
                    $addressParts[] = $country;
                }

                $formattedAddress = ! empty($addressParts) ? implode(', ', $addressParts) : ($data['display_name'] ?? null);

                return [
                    'address' => $formattedAddress,
                    'city' => $city,
                    'region' => $region,
                    'country' => $country,
                    'country_code' => $countryCode,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal reverse geocoding untuk koordinat {$latitude}, {$longitude}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Safely resolve real client IP considering reverse proxies / Cloudflare.
     */
    public static function resolveClientIp(Request $request): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
        ];

        foreach ($headers as $header) {
            if ($request->server->has($header)) {
                $ips = explode(',', (string) $request->server->get($header));
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?: '127.0.0.1';
    }

    /**
     * Resolve geolocation using Stevebauman\Location.
     */
    public static function resolveLocation(string $ip): array
    {
        // Don't lookup local / loopback IP addresses
        if (
            $ip === '127.0.0.1' ||
            $ip === '::1' ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.16.')
        ) {
            return [
                'city' => 'Localhost',
                'region' => 'Local Network',
                'country' => 'Local',
                'country_code' => 'LOC',
                'latitude' => null,
                'longitude' => null,
            ];
        }

        try {
            $position = Location::get($ip);

            if ($position) {
                return [
                    'city' => $position->cityName ?: null,
                    'region' => $position->regionName ?: null,
                    'country' => $position->countryName ?: null,
                    'country_code' => $position->countryCode ?: null,
                    'latitude' => $position->latitude ?: null,
                    'longitude' => $position->longitude ?: null,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal mendapatkan lokasi untuk IP {$ip}: ".$e->getMessage());
        }

        return [];
    }

    /**
     * Parse User-Agent string to extract device, platform/OS, and browser.
     */
    public static function parseUserAgent(string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'device' => 'Unknown',
                'platform' => 'Unknown',
                'browser' => 'Unknown',
            ];
        }

        // Platform / OS
        $platform = 'Unknown';
        if (preg_match('/windows nt 10/i', $userAgent)) {
            $platform = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $userAgent)) {
            $platform = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.2/i', $userAgent)) {
            $platform = 'Windows 8';
        } elseif (preg_match('/windows nt 6\.1/i', $userAgent)) {
            $platform = 'Windows 7';
        } elseif (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $platform = 'iOS (iPhone)';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $platform = 'iPadOS';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/cros/i', $userAgent)) {
            $platform = 'ChromeOS';
        }

        // Device
        $device = 'Desktop';
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            $device = 'Tablet';
        } elseif (preg_match('/mobile|iphone|ipod|android.*mobile|blackberry|phone/i', $userAgent)) {
            $device = 'Mobile';
        }

        // Browser
        $browser = 'Unknown';
        if (preg_match('/edg\//i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opr\/|opera\//i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/chrome\/([0-9\.]+)/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari\/([0-9\.]+)/i', $userAgent) && ! preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/firefox\/([0-9\.]+)/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/msie|trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }

        return [
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
        ];
    }
}
