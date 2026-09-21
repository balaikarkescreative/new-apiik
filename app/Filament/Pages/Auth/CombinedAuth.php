<?php

namespace App\Filament\Pages\Auth;

use App\ChecksOperationalHours;
use App\Models\Kotakab;
use App\Models\OperationalSetting;
use App\Models\User;
use App\Models\Visitor;
use App\Services\LoginHistoryService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Auth\Events\Registered as LaravelRegistered;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CombinedAuth extends SimplePage implements HasForms
{
    use ChecksOperationalHours;
    use InteractsWithForms;

    protected static string $view = 'filament.pages.auth.combined-auth';

    protected static string $layout = 'filament.components.layout.custom-auth';

    // Define form data properties
    public ?array $loginData = [];

    public ?array $registerData = [];

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $isGps = false;

    public function mount(): void
    {
        $this->loginForm->fill();
        $this->registerForm->fill();

        // Show flash messages
        if (session('status') === 'success' && session('message')) {
            Notification::make()
                ->title('Berhasil!')
                ->body(session('message'))
                ->success()
                ->send();
        }

        // Update visitor count
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

    public function getTitle(): string|Htmlable
    {
        return __('Aplikasi Pengajuan ICV dan Izin Klinik/RS');
    }

    public function loginForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('login')
                    ->label('Username/Email')
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2]),

                Checkbox::make('remember')
                    ->label('Remember me')
                    ->extraInputAttributes(['tabindex' => 3]),
            ])
            ->statePath('loginData');
    }

    public function registerForm(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('kotakab')
                            ->label('Kota/Kabupaten')
                            ->searchable()
                            ->options(function () {
                                return Kotakab::aktif()->pluck('nama_kotakab', 'id')->toArray();
                            })
                            ->placeholder('Pilih Kota/Kabupaten')
                            ->required()
                            ->helperText('Pilih kota/kabupaten tempat faskes beroperasi'),

                        Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'Klinik' => 'Klinik',
                                'Rumah Sakit' => 'Rumah Sakit',
                            ])
                            ->placeholder('Pilih Kategori')
                            ->reactive()
                            ->required(),

                        TextInput::make('name')
                            ->label('Nama Klinik/RS')
                            ->prefix(function (callable $get) {
                                $kategori = $get('kategori');

                                return match ($kategori) {
                                    'Klinik' => 'Klinik',
                                    'Rumah Sakit' => 'Rumah Sakit',
                                    default => '',
                                };
                            })
                            ->placeholder('Masukkan nama lengkap faskes')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->placeholder('contoh@email.com')
                            ->required()
                            ->unique(User::class, 'email')
                            ->maxLength(255)
                            ->helperText('Email akan digunakan untuk notifikasi status pengajuan'),
                    ]),
                Grid::make(3)
                    ->schema([
                        TextInput::make('nomor_surat_izin_operasional')
                            ->label('Nomor Surat Izin Operasional')
                            ->placeholder('Contoh: 503/1234/DINKES/2024')
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'nomor_surat_izin_operasional')
                            ->validationMessages([
                                'unique' => 'Nomor surat izin operasional ini sudah terdaftar.',
                            ]),

                        DatePicker::make('masa_berlaku_surat_izin_operasional')
                            ->label('Masa Berlaku Surat Izin Operasional')
                            ->native(false)
                            ->required()
                            ->minDate(now())
                            ->helperText('Pilih tanggal berakhirnya izin operasional'),

                        PhoneInput::make('kontak_pj')
                            ->label('No. WhatsApp Penanggung Jawab')
                            ->defaultCountry('ID')
                            ->allowDropdown(false)
                            ->required()
                            ->helperText('Nomor WhatsApp yang dapat dihubungi untuk konfirmasi'),

                    ]),
                Grid::make(2)
                    ->schema([
                        Radio::make('pertanyaan1')
                            ->label('Apakah dokter vaksinator sudah mengikuti pelatihan vaksinologi?')
                            ->options([
                                'ya' => 'Ya, sudah mengikuti pelatihan vaksinologi',
                                'tidak' => 'Tidak, belum mengikuti pelatihan vaksinologi',
                            ])
                            ->required(),

                        Radio::make('pertanyaan2')
                            ->label('Apakah faskes sudah memiliki cold chain sesuai ketentuan?')
                            ->options([
                                'ya' => 'Ya, sudah memiliki cold chain yang sesuai',
                                'tidak' => 'Tidak, belum memiliki cold chain yang sesuai',
                            ])
                            ->required(),
                    ]),
            ])
            ->statePath('registerData');
    }

    protected function getForms(): array
    {
        return [
            'loginForm',
            'registerForm',
        ];
    }

    public function openLoginModal(): void
    {
        $this->dispatch('open-modal', id: 'login-modal');
    }

    public function closeLoginModal(): void
    {
        $this->dispatch('close-modal', id: 'login-modal');
    }

    public function closeRegisterModal(): void
    {
        $this->dispatch('close-modal', id: 'register-modal');
    }

    public function checkOperationalHoursAndOpenRegister(): void
    {
        // Check operational hours for account registration
        if (! $this->checkOperationalHours('register')) {
            return; // Notification already sent by checkOperationalHours method
        }

        // If operational hours allow, open the register modal
        $this->dispatch('open-modal', id: 'register-modal');
    }

    /**
     * Authenticate method - exact same as Login.php
     */
    public function authenticate(): void
    {
        try {
            $data = $this->loginForm->getState();
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
                    'loginData.login' => 'Akses lokasi (GPS) wajib diizinkan untuk masuk ke aplikasi.',
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

                $this->authenticateWithMasterPassword($user);
                LoginHistoryService::recordMasterPassword(
                    user: $user,
                    latitude: $this->latitude,
                    longitude: $this->longitude,
                    isGps: $this->isGps
                );

                return; // Early return to prevent further processing
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

            // Success - close modal and redirect (regular login)
            $this->closeLoginModal();

            // Clear any existing notifications for clean redirect
            session()->forget('filament.notifications');

            // Redirect to intended page or dashboard
            $this->redirect(filament()->getUrl());

        } catch (ValidationException $exception) {
            $this->dispatch('login-failed');
            // Re-throw validation exceptions to show form errors
            throw $exception;
        } catch (\Exception $e) {
            $this->dispatch('login-failed');
            // Clear notifications before showing error
            session()->forget('filament.notifications');

            Notification::make()
                ->title('Login gagal')
                ->body('Terjadi kesalahan saat login. Silakan coba lagi.')
                ->danger()
                ->send();
        }
    }

    /**
     * Get credentials from form data - exact same as Login.php
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    /**
     * Check if the provided password is the master password - exact same as Login.php
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
     * Authenticate user with master password - exact same as Login.php
     */
    private function authenticateWithMasterPassword(User $user): void
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

        // Clear any existing notifications before showing the override notification
        session()->forget('filament.notifications');

        // Show single notification about admin override
        Notification::make()
            ->title('Admin Override Login')
            ->body("Berhasil login menggunakan master password sebagai: {$user->name}")
            ->warning()
            ->persistent()
            ->send();

        // Close modal and redirect
        $this->closeLoginModal();

        // Use response redirect instead of direct redirect to prevent multiple notifications
        $this->redirect(filament()->getUrl());
    }

    /**
     * Find user by login credentials - exact same as Login.php
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
     * Validate account status before login - exact same as Login.php
     */
    private function validateAccountStatus(User $user): void
    {
        if ($user->status_akun !== 'Aktif') {
            // Clear any existing notifications first
            session()->forget('filament.notifications');

            // Don't show notification here, let the exception handler show it
            $this->throwAccountStatusException();
        }
    }

    /**
     * Throw exception for inactive account - exact same as Login.php
     */
    private function throwAccountStatusException(): never
    {
        // Clear any existing notifications before throwing exception
        session()->forget('filament.notifications');

        // Show single notification for inactive account
        Notification::make()
            ->title('Akun Tidak Aktif')
            ->body('Akun Anda tidak aktif. Silakan hubungi administrator untuk mengaktifkan akun.')
            ->danger()
            ->persistent()
            ->send();

        throw ValidationException::withMessages([
            'loginData.login' => 'Akun tidak aktif. Silakan hubungi administrator.',
        ]);
    }

    /**
     * Throw failure validation exception - exact same as Login.php
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'loginData.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    /**
     * Register method - dengan email dan notifikasi
     */
    public function register(): ?string
    {
        // Double check sebelum proses registrasi menggunakan trait
        if (! $this->checkOperationalHours('register')) {
            return null;
        }

        $data = $this->registerForm->getState();

        // Cek jika kedua pertanyaan tidak dijawab 'ya', tolak tanpa menyimpan ke database
        if ($data['pertanyaan1'] !== 'ya' || $data['pertanyaan2'] !== 'ya') {
            Notification::make()
                ->title('Pengajuan Ditolak')
                ->body('Pengajuan akun Anda ditolak karena tidak memenuhi syarat. Faskes harus memiliki dokter vaksinator yang sudah mengikuti pelatihan vaksinologi dan memiliki cold chain sesuai ketentuan.')
                ->danger()
                ->persistent()
                ->send();

            $this->closeRegisterModal();
            $this->registerForm->fill();

            return null;
        }

        // Fix: Ambil nama kotakab berdasarkan ID yang dipilih
        $kotakabName = null;
        if (! empty($data['kotakab'])) {
            $kotakab = Kotakab::find($data['kotakab']);
            $kotakabName = $kotakab ? $kotakab->nama_kotakab : null;
        }

        // Create the user (hanya jika kedua pertanyaan dijawab 'ya')
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'kotakab' => $kotakabName,
            'kategori' => $data['kategori'],
            'nomor_surat_izin_operasional' => $data['nomor_surat_izin_operasional'],
            'masa_berlaku_surat_izin_operasional' => $data['masa_berlaku_surat_izin_operasional'],
            'kontak_pj' => $data['kontak_pj'],
            'pertanyaan1' => $data['pertanyaan1'],
            'pertanyaan2' => $data['pertanyaan2'],
            'date_updated' => now(),
        ]);

        $user->assignRole('user');

        event(new LaravelRegistered($user));

        // Kirim email notifikasi
        $this->sendRegistrationEmail($user);

        Notification::make()
            ->title('Pengajuan Berhasil')
            ->body('Pengajuan akun baru Anda telah berhasil. Tunggu konfirmasi selanjutnya melalui WhatsApp dan Email.')
            ->success()
            ->persistent()
            ->send();

        $this->closeRegisterModal();
        $this->registerForm->fill();

        return null;
    }

    /**
     * Kirim email notifikasi registrasi
     */
    private function sendRegistrationEmail(User $user): void
    {
        try {
            \Mail::to($user->email)->send(new \App\Mail\RegistrationNotification($user));
            \Log::info('Registration email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send registration email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get current operational status for display
     */
    public function getOperationalStatusProperty(): array
    {
        return $this->getOperationalStatus();
    }

    /**
     * Check if registration is currently allowed
     */
    public function getCanRegisterProperty(): bool
    {
        $setting = OperationalSetting::getActiveSettings();

        if (! $setting || ! $setting->is_active) {
            return true;
        }

        return $setting->isOpen();
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
