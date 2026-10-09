<?php

namespace App\Services;

use App\Models\SimplyUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SimplyAuthService
{
    /**
     * Check if a user is linked to SIMPLY or has the dokter role.
     */
    public static function isSimplyDoctor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ! empty($user->simply_user_id) || $user->hasRole('dokter');
    }

    /**
     * Authenticate a user against the SIMPLY database.
     *
     * @throws ValidationException
     */
    public static function attemptSimplyLogin(User $user, string $password): bool
    {
        try {
            $simplyUser = null;

            if ($user->simply_user_id) {
                $simplyUser = SimplyUser::find($user->simply_user_id);
            }

            if (! $simplyUser && $user->username) {
                $simplyUser = SimplyUser::where('username', $user->username)->first();
            }

            if (! $simplyUser) {
                Log::warning('SIMPLY User not found for login attempt', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'simply_user_id' => $user->simply_user_id,
                ]);

                return false;
            }

            // Check if employee is active in SIMPLY
            if ($simplyUser->status_pegawai_aktif !== 'Aktif') {
                throw ValidationException::withMessages([
                    'loginData.login' => 'Akun pegawai SIMPLY Anda saat ini berstatus Tidak Aktif.',
                    'data.login' => 'Akun pegawai SIMPLY Anda saat ini berstatus Tidak Aktif.',
                ]);
            }

            // Verify password using bcrypt hash from simply.user
            if (Hash::check($password, $simplyUser->password)) {
                // Keep local password hash in sync with SIMPLY
                if ($user->password !== $simplyUser->password) {
                    $user->update(['password' => $simplyUser->password]);
                }

                // If simply_user_id was not linked yet, link it now
                if (! $user->simply_user_id && $simplyUser->id_user) {
                    $user->update(['simply_user_id' => $simplyUser->id_user]);
                }

                return true;
            }

            return false;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error during SIMPLY authentication: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
