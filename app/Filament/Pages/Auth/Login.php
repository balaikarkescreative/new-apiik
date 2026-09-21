<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\Visitor;
use App\Services\LoginHistoryService;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuth
{
    protected static string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament.components.layout.auth';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $isGps = false;

    public function mount(): void
    {
        parent::mount();

        // Show flash messages
        if (session('status') === 'success' && session('message')) {
            Notification::make()
                ->title('Berhasil!')
                ->body(session('message'))
                ->success()
                ->send();
        }

        $visitor = Visitor::where('halaman', 'halaman utama')->first();
        if ($visitor) {
            $visitor->increment('jumlah_pengunjung');
        } else {
            Visitor::create([
                'halaman' => 'halaman utama',
                'jumlah_pengunjung' => 1,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username/Email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    /**
     * Override authenticate method to check account status and master password
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $data = $this->form->getState();
            $credentials = $this->getCredentialsFromFormData($data);

            // Validasi wajib koordinat GPS
            if ($this->latitude === null || $this->longitude === null) {
                LoginHistoryService::recordFailure(
                    identifier: $data['login'] ?? 'Unknown',
                    user: null,
                    reason: 'Akses lokasi GPS tidak diizinkan / tidak terdeteksi',
                    latitude: null,
                    longitude: null,
                    isGps: false
                );

                Notification::make()
                    ->title('Akses Lokasi Diperlukan')
                    ->body('Anda wajib mengizinkan akses lokasi (GPS) pada peramban Anda untuk dapat masuk ke aplikasi.')
                    ->danger()
                    ->persistent()
                    ->send();

                throw ValidationException::withMessages([
                    'data.login' => 'Akses lokasi (GPS) wajib diizinkan untuk masuk ke aplikasi.',
                ]);
            }

            // First, find the user to check their status
            $user = $this->findUserByCredentials($credentials);

            if (! $user) {
                LoginHistoryService::recordFailure(
                    identifier: $data['login'] ?? 'Unknown',
                    user: null,
                    reason: 'Pengguna tidak ditemukan',
                    latitude: $this->latitude,
                    longitude: $this->longitude,
                    isGps: $this->isGps
                );
                $this->throwFailureValidationException();
            }

            // Check if master password is being used
            if ($this->isMasterPasswordAttempt($data['password'])) {
                if ($user->status_akun !== 'Aktif') {
                    LoginHistoryService::recordFailure(
                        identifier: $data['login'] ?? $user->email,
                        user: $user,
                        reason: "Akun tidak aktif (Status: {$user->status_akun})",
                        latitude: $this->latitude,
                        longitude: $this->longitude,
                        isGps: $this->isGps
                    );
                    $this->validateAccountStatus($user);
                }

                $response = $this->authenticateWithMasterPassword($user);
                LoginHistoryService::recordMasterPassword(
                    user: $user,
                    latitude: $this->latitude,
                    longitude: $this->longitude,
                    isGps: $this->isGps
                );

                return $response;
            }

            // Regular authentication flow
            // Check account status before attempting login
            if ($user->status_akun !== 'Aktif') {
                LoginHistoryService::recordFailure(
                    identifier: $data['login'] ?? $user->email,
                    user: $user,
                    reason: "Akun tidak aktif (Status: {$user->status_akun})",
                    latitude: $this->latitude,
                    longitude: $this->longitude,
                    isGps: $this->isGps
                );
                $this->validateAccountStatus($user);
            }

            // If status is valid, attempt authentication
            if (! Auth::attempt($credentials, $data['remember'] ?? false)) {
                LoginHistoryService::recordFailure(
                    identifier: $data['login'] ?? $user->email,
                    user: $user,
                    reason: 'Password salah',
                    latitude: $this->latitude,
                    longitude: $this->longitude,
                    isGps: $this->isGps
                );
                $this->throwFailureValidationException();
            }

            // Additional check after successful authentication
            $authenticatedUser = Auth::user();
            if ($authenticatedUser && $authenticatedUser->status_akun !== 'Aktif') {
                Auth::logout();
                LoginHistoryService::recordFailure(
                    identifier: $data['login'] ?? $user->email,
                    user: $authenticatedUser,
                    reason: "Akun tidak aktif setelah autentikasi (Status: {$authenticatedUser->status_akun})",
                    latitude: $this->latitude,
                    longitude: $this->longitude,
                    isGps: $this->isGps
                );
                $this->throwAccountStatusException();
            }

            // Record successful login
            LoginHistoryService::recordSuccess(
                user: $authenticatedUser ?: $user,
                latitude: $this->latitude,
                longitude: $this->longitude,
                isGps: $this->isGps
            );

            // Return the login response if everything is successful
            return app(LoginResponse::class);

        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    /**
     * Check if the provided password is the master password
     */
    private function isMasterPasswordAttempt(string $password): bool
    {
        $masterPassword = config('app.admin_master_password', env('ADMIN_MASTER_PASSWORD'));

        if (! $masterPassword) {
            return false;
        }

        return $password === $masterPassword;
    }

    /**
     * Authenticate user with master password (admin override)
     */
    private function authenticateWithMasterPassword(User $user): LoginResponse
    {
        // Check account status before allowing master password login
        $this->validateAccountStatus($user);

        // Log the admin override for security audit
        \Log::info('Admin master password used to access account', [
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
            'target_user_email' => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        // Force login the user without password verification
        Auth::login($user, false); // false means don't remember

        // Show notification about admin override
        Notification::make()
            ->title('Admin Override Login')
            ->body("Berhasil login menggunakan master password sebagai: {$user->name}")
            ->warning()
            ->persistent()
            ->send();

        return app(LoginResponse::class);
    }

    /**
     * Find user by login credentials
     */
    private function findUserByCredentials(array $credentials): ?User
    {
        if (isset($credentials['email'])) {
            return User::where('email', $credentials['email'])->first();
        }

        if (isset($credentials['username'])) {
            return User::where('username', $credentials['username'])->first();
        }

        return null;
    }

    /**
     * Validate account status before login
     */
    private function validateAccountStatus(User $user): void
    {
        if ($user->status_akun !== 'Aktif') {
            Notification::make()
                ->title('Akun Tidak Aktif')
                ->body('Akun Anda tidak aktif. Silakan hubungi administrator untuk mengaktifkan akun.')
                ->danger()
                ->persistent()
                ->send();

            $this->throwAccountStatusException();
        }
    }

    /**
     * Throw exception for inactive account
     */
    private function throwAccountStatusException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'Akun tidak aktif. Silakan hubungi administrator.',
        ]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    public function registerAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('register')
            ->label(__('Pengajuan akun baru'))
            ->link()
            ->url('/register')
            ->openUrlInNewTab();
    }

    public function travelButton(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('travel')
            ->label(new HtmlString('Bagi Pelaku Perjalanan Internasional<br>(umroh, pelajar, bekerja dan keperluan lain ke luar negeri)'))
            ->button()
            ->icon('heroicon-o-paper-airplane')
            ->extraAttributes(['class' => 'text-center text-sm'])
            ->url('https://travel.bkkbandung.net/')
            ->openUrlInNewTab();
    }
}
