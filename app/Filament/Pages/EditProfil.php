<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Dotswan\MapPicker\Fields\Map;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use App\Models\Kotakab;
use App\Models\User;

class EditProfil extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.edit-profil';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static bool $shouldRegisterNavigation = false;

    public static ?string $slug = 'edit-profil';

    public static ?string $title = 'Edit Profil';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'alamat' => $user->alamat,
            'kotakab' => $user->kotakab,
            'pimpinan' => $user->pimpinan,
            'pj_akun' => $user->pj_akun,
            'kontak_pj' => $user->kontak_pj,
            'location' => [
                'lat' => $user->latitude ?? -6.9039,
                'lng' => $user->longitude ?? 107.6186,
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section Informasi Dasar
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Klinik/RS')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $existingUser = User::where('email', $value)
                                        ->where('id', '!=', Auth::id())
                                        ->first();

                                    if ($existingUser) {
                                        $fail('Email sudah digunakan oleh pengguna lain.');
                                    }
                                };
                            }),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('kotakab')
                            ->label('Kota/Kabupaten')
                            ->searchable()
                            ->options(Kotakab::pluck('nama_kotakab')->toArray())
                            ->required(),

                        Forms\Components\TextInput::make('pimpinan')
                            ->required()
                            ->label('Pimpinan'),

                        Forms\Components\TextInput::make('pj_akun')
                            ->required()
                            ->label('Penanggung Jawab Akun'),

                        PhoneInput::make('kontak_pj')
                            ->label('No. Whatsapp')
                            ->defaultCountry('ID')
                            ->allowDropdown(false)
                            ->required(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Section Lokasi
                Forms\Components\Section::make('Lokasi')
                    ->schema([
                        Map::make('location')
                            ->label('Lokasi Klinik/RS')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('latitude', $state['lat'] ?? null);
                                    $set('longitude', $state['lng'] ?? null);
                                }
                            })
                            ->showMyLocationButton(true)
                            ->zoom(12)

                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('tutorial')
                                ->label('Cara Menentukan Latitude dan Longitude')
                                ->icon('heroicon-o-question-mark-circle')
                                ->link()
                                ->url('https://support.google.com/maps/answer/18539?hl=id&co=GENIE.Platform%3DDesktop&sjid=5970428221449970434-NC')
                                ->openUrlInNewTab()
                        ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->maxLength(20)
                                    ->default(function (callable $get) {
                                        $location = $get('location');
                                        return $location['lat'] ?? '';
                                    })
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        $lng = $get('longitude');
                                        if ($state && $lng) {
                                            $set('location', [
                                                'lat' => (float) $state,
                                                'lng' => (float) $lng
                                            ]);
                                            // Trigger map refresh
                                            $this->dispatch('refreshMap');
                                        }
                                    })
                                    ->reactive(),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->maxLength(20)
                                    ->default(function (callable $get) {
                                        $location = $get('location');
                                        return $location['lng'] ?? '';
                                    })
                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                        $lat = $get('latitude');
                                        if ($state && $lat) {
                                            $set('location', [
                                                'lat' => (float) $lat,
                                                'lng' => (float) $state
                                            ]);
                                            // Trigger map refresh
                                            $this->dispatch('refreshMap');
                                        }
                                    })
                                    ->reactive(),
                            ]),
                    ])
                    ->collapsible(),

                // Section Keamanan Akun
                Forms\Components\Section::make('Keamanan Akun')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->maxLength(255)
                            ->alphaDash()
                            ->helperText('Username harus unik dan hanya boleh mengandung huruf, angka, tanda hubung (-), dan garis bawah (_)')
                            ->required()
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $existingUser = User::where('username', $value)
                                        ->where('id', '!=', Auth::id())
                                        ->first();

                                    if ($existingUser) {
                                        $fail('Username sudah digunakan oleh pengguna lain.');
                                    }
                                };
                            }),

                        Forms\Components\TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->helperText('Masukkan password saat ini untuk mengubah password')
                            ->visible(fn(callable $get) => !empty($get('new_password')) || !empty($get('new_password_confirmation')))
                            ->required(fn(callable $get) => !empty($get('new_password')) && !Auth::user()->hasRole('admin'))
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    // Skip validation if user is admin or if no new password is provided
                                    if (Auth::user()->hasRole('admin') || empty($value)) {
                                        return;
                                    }

                                    if (!Hash::check($value, Auth::user()->password)) {
                                        $fail('Password saat ini tidak sesuai.');
                                    }
                                };
                            }),

                        Forms\Components\TextInput::make('new_password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password')
                            ->reactive(),

                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->same('new_password')
                            ->visible(fn(callable $get) => !empty($get('new_password')))
                            ->required(fn(callable $get) => !empty($get('new_password'))),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(true)
                    ->icon('heroicon-o-lock-closed'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        // Validate form first - ini akan stop execution jika ada error
        $data = $this->form->getState();

        // Handle location data
        if (isset($data['location'])) {
            $data['latitude'] = $data['location']['lat'] ?? null;
            $data['longitude'] = $data['location']['lng'] ?? null;
            unset($data['location']);
        }

        // Handle password change
        $passwordChanged = false;

        if (!empty($data['new_password'])) {
            $data['password'] = Hash::make($data['new_password']);
            $passwordChanged = true;
        }

        // Remove password-related fields dari data yang akan disimpan
        unset($data['current_password'], $data['new_password'], $data['new_password_confirmation']);

        try {
            // Update user data
            $user = Auth::user();
            $user->update($data);

            if ($passwordChanged) {
                // For password change: logout and redirect with session message
                Auth::logout();

                session()->flash('status', 'success');
                session()->flash('message', 'Password berhasil diubah. Silakan login ulang dengan password baru Anda.');

                $this->redirect('/login');
                return;
            }

            // Success notification for profile update without password change
            Notification::make()
                ->title('Berhasil!')
                ->body('Profil berhasil diperbarui')
                ->success()
                ->send();

            // Refresh form data setelah berhasil disimpan
            $this->mount();

        } catch (\Exception $e) {
            // Error notification
            Notification::make()
                ->title('Gagal menyimpan')
                ->body('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Simpan Perubahan')
                ->icon('heroicon-o-check')
                ->action('save')
                ->color('primary'),
        ];
    }
}
