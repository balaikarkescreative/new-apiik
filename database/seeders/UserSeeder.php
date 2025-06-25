<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Read and decode the JSON file
        $jsonPath = database_path('seeders/tbl_akun.json');
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find the data array in the JSON structure
        $userData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $userData = $item['data'];
                break;
            }
        }

        if (!$userData) {
            $this->command->error('No user data found in JSON file');
            return;
        }

        // Create roles if they don't exist
        $roles = [
            'admin',
            'verifikator1',
            'verifikator2',
            'pos',
            'user'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Group users by email to handle duplicates
        $groupedUsers = $this->groupUsersByEmail($userData);

        $this->command->info('Processing ' . count($groupedUsers) . ' unique emails...');

        foreach ($groupedUsers as $email => $users) {
            // Select the best user from duplicates
            $selectedUser = $this->selectBestUser($users);

            // Skip if name is null or empty
            if (empty($selectedUser['nama'])) {
                $this->command->warn("Skipping user with empty name. Email: " . ($selectedUser['email'] ?? 'N/A'));
                continue;
            }

            // Check if ID already exists in database
            if (!empty($selectedUser['id']) && User::where('id', $selectedUser['id'])->exists()) {
                $this->command->warn("User with ID {$selectedUser['id']} already exists. Skipping...");
                continue;
            }

            // Check if email already exists in database
            if (User::where('email', $selectedUser['email'])->exists()) {
                $this->command->warn("User with email {$selectedUser['email']} already exists. Skipping...");
                continue;
            }

            // Map the old field names to new User model fields
            $userDataToInsert = [
                'id' => $selectedUser['id'] ?? null,
                'name' => $selectedUser['nama'] ?? '',
                'email' => $selectedUser['email'] ?? '',
                'password' => Hash::make($selectedUser['katasandi'] ?? 'password'),
                'username' => $selectedUser['email'] ?? '',
                'latitude' => $selectedUser['latitude'] ?? null,
                'longitude' => $selectedUser['longitude'] ?? null,
                'kategori' => $selectedUser['kategori'] ?? '',
                'alamat' => $selectedUser['alamat'] ?? '',
                'kotakab' => $this->mapKotakab($selectedUser['kotakab'] ?? ''),
                'pimpinan' => $selectedUser['pimpinan'] ?? '',
                'pj_akun' => $selectedUser['pj_akun'] ?? '',
                'kontak_pj' => $selectedUser['kontak_pj'] ?? '',
                'nomor_surat_izin_operasional' => $selectedUser['nomor_surat_izin_operasional'] ?? '',
                'masa_berlaku_surat_izin_operasional' => $this->parseDate($selectedUser['masa_berlaku_surat_izin_operasional'] ?? '0000-00-00'),
                'pertanyaan1' => $selectedUser['pertanyaan1'] ?? '',
                'pertanyaan2' => $selectedUser['pertanyaan2'] ?? '',
                'status_akun' => $this->mapStatusAkun($selectedUser['status_akun'] ?? ''),
                'date_updated' => $this->parseDateTime($selectedUser['date_updated'] ?? null),
            ];

            // Only add verifikasi_akun if it's not null or empty
            if (!empty($selectedUser['verifikasi_akun'])) {
                $userDataToInsert['verifikasi_akun'] = $selectedUser['verifikasi_akun'];
            }

            $user = User::create($userDataToInsert);

            // Assign role based on email pattern
            $roleName = $this->getRoleNameByEmail($selectedUser['email'] ?? '');

            if ($roleName) {
                $user->assignRole($roleName);
            }

            $duplicateInfo = count($users) > 1 ? " (selected from " . count($users) . " duplicates)" : "";
            $this->command->info("Created user: {$user->name} ({$user->email}) with role: {$roleName}{$duplicateInfo}");
        }

        $this->command->info('UserSeeder completed successfully!');
    }

    /**
     * Group users by email to handle duplicates
     */
    private function groupUsersByEmail($userData)
    {
        $grouped = [];

        foreach ($userData as $user) {
            $email = $user['email'] ?? '';
            if (empty($email)) {
                continue; // Skip users without email
            }

            if (!isset($grouped[$email])) {
                $grouped[$email] = [];
            }

            $grouped[$email][] = $user;
        }

        return $grouped;
    }

    /**
     * Select the best user from duplicates
     * Priority: verifikasi_akun = 'Diterima' > latest date_updated
     */
    private function selectBestUser($users)
    {
        if (count($users) === 1) {
            return $users[0];
        }

        // First priority: users with verifikasi_akun = 'Diterima'
        $acceptedUsers = array_filter($users, function($user) {
            return isset($user['verifikasi_akun']) && $user['verifikasi_akun'] === 'Diterima';
        });

        if (!empty($acceptedUsers)) {
            // If multiple accepted users, get the one with latest date_updated
            usort($acceptedUsers, function($a, $b) {
                $dateA = $a['date_updated'] ?? '1970-01-01 00:00:00';
                $dateB = $b['date_updated'] ?? '1970-01-01 00:00:00';
                return strtotime($dateB) - strtotime($dateA);
            });
            return $acceptedUsers[0];
        }

        // If no accepted users, get the one with latest date_updated
        usort($users, function($a, $b) {
            $dateA = $a['date_updated'] ?? '1970-01-01 00:00:00';
            $dateB = $b['date_updated'] ?? '1970-01-01 00:00:00';
            return strtotime($dateB) - strtotime($dateA);
        });

        return $users[0];
    }

    /**
     * Parse date string to proper format
     */
    private function parseDate($dateString)
    {
        if (!$dateString || $dateString === '0000-00-00') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse datetime string to proper format
     */
    private function parseDateTime($datetimeString)
    {
        if (!$datetimeString) {
            return now();
        }

        try {
            return \Carbon\Carbon::parse($datetimeString);
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Get role name based on email pattern
     */
    private function getRoleNameByEmail($email)
    {
        $email = strtolower($email);

        // Admin role
        if ($email === 'admin' || strpos($email, 'admin') !== false) {
            return 'admin';
        }

        // Verifikator1 role
        if (strpos($email, 'verifikator1') !== false || strpos($email, 'verify1') !== false) {
            return 'verifikator1';
        }

        // Verifikator2 role
        if (strpos($email, 'verifikator2') !== false || strpos($email, 'verify2') !== false) {
            return 'verifikator2';
        }

        // Pos role
        if (strpos($email, 'pos') !== false) {
            return 'pos';
        }

        // Default to user role
        return 'user';
    }

    /**
     * Map status akun from old to new values
     */
    private function mapStatusAkun($status)
    {
        if ($status === 'Belum Aktif') {
            return 'Tidak Aktif';
        }

        return $status;
    }

    /**
     * Map kotakab ID to name
     */
    private function mapKotakab($kotakab)
    {
        // If it's not numeric, return as is
        if (!is_numeric($kotakab)) {
            return $kotakab;
        }

        // Load kotakab mapping data
        $kotakabMap = $this->getKotakabMapping();

        // Convert to integer for lookup
        $kotakabId = (int)$kotakab;

        // Return mapped name or original value if not found
        return $kotakabMap[$kotakabId] ?? $kotakab;
    }

    /**
     * Get kotakab mapping from JSON file
     */
    private function getKotakabMapping()
    {
        static $mapping = null;

        if ($mapping === null) {
            $jsonPath = database_path('seeders/tbl_kotakab_jabar.json');

            if (!file_exists($jsonPath)) {
                $this->command->warn("Kotakab mapping file not found: {$jsonPath}");
                return [];
            }

            $jsonContent = file_get_contents($jsonPath);
            $data = json_decode($jsonContent, true);

            // Find the data array in the JSON structure
            $kotakabData = null;
            foreach ($data as $item) {
                if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                    $kotakabData = $item['data'];
                    break;
                }
            }

            $mapping = [];
            if ($kotakabData) {
                foreach ($kotakabData as $kotakab) {
                    $id = (int)($kotakab['id'] ?? 0);
                    $nama = trim($kotakab['nama_kotakab'] ?? '');

                    if ($id > 0 && !empty($nama)) {
                        $mapping[$id] = $nama;
                    }
                }
            }
        }

        return $mapping;
    }
}
