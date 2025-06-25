<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms;
use Filament\Actions\Action;
use App\Models\User;
use App\Models\Kotakab;
use App\ChecksOperationalHours;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Illuminate\Auth\Events\Registered as LaravelRegistered;

class Register extends BaseRegister
{
    use ChecksOperationalHours;

    protected static string $layout = 'filament.components.layout.auth';

    public function getMaxWidth(): ?string
    {
        return '3xl';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('register')
                ->label('Kirim Pengajuan')
                ->submit('register')
                ->size('xl')
                ->icon('heroicon-o-check-circle')
                ->extraAttributes(['class' => 'w-full'])
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Menggunakan trait untuk pengecekan operational
        $this->checkOperationalHoursWithRedirect('register', '/login');
    }

    public function register(): \Filament\Http\Responses\Auth\Contracts\RegistrationResponse|null
    {
        // Double check sebelum proses registrasi menggunakan trait
        if (!$this->checkOperationalHours('register')) {
            return null;
        }

        $data = $this->form->getState();

        // Fix: Ambil nama kotakab berdasarkan ID yang dipilih
        $kotakabName = null;
        if (!empty($data['kotakab'])) {
            $kotakab = Kotakab::find($data['kotakab']);
            $kotakabName = $kotakab ? $kotakab->nama_kotakab : null;
        }

        // Create the user
        $user = User::create([
            'name' => $data['name'],
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

        Notification::make()
            ->title('Pengajuan berhasil dikirim!')
            ->body('Pengajuan akun baru Anda telah berhasil dikirim. Tunggu konfirmasi selanjutnya melalui WhatsApp.')
            ->success()
            ->persistent()
            ->send();

        $this->form->fill([]);

        return null;
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('kotakab')
                                    ->label('Kota/Kabupaten')
                                    ->searchable()
                                    ->options(function () {
                                        return Kotakab::pluck('nama_kotakab', 'id')->toArray();
                                    })
                                    ->placeholder('Pilih Kota/Kabupaten')
                                    ->required()
                                    ->helperText('Pilih kota/kabupaten tempat faskes beroperasi'),

                                Forms\Components\Select::make('kategori')
                                    ->label('Kategori')
                                    ->options([
                                        'Klinik' => 'Klinik',
                                        'Rumah Sakit' => 'Rumah Sakit',
                                    ])
                                    ->placeholder('Pilih Kategori')
                                    ->reactive()
                                    ->required(),

                                Forms\Components\TextInput::make('name')
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

                                Forms\Components\TextInput::make('nomor_surat_izin_operasional')
                                    ->label('Nomor Surat Izin Operasional')
                                    ->placeholder('Contoh: 503/1234/DINKES/2024')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\DatePicker::make('masa_berlaku_surat_izin_operasional')
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

                                // Kembalikan pertanyaan ke dalam grid yang sama
                                Forms\Components\Radio::make('pertanyaan1')
                                    ->label('Apakah dokter vaksinator sudah mengikuti pelatihan vaksinologi?')
                                    ->options([
                                        'ya' => 'Ya, sudah mengikuti pelatihan vaksinologi',
                                        'tidak' => 'Tidak, belum mengikuti pelatihan vaksinologi',
                                    ])
                                    ->required(),

                                Forms\Components\Radio::make('pertanyaan2')
                                    ->label('Apakah faskes sudah memiliki cold chain sesuai ketentuan?')
                                    ->options([
                                        'ya' => 'Ya, sudah memiliki cold chain yang sesuai',
                                        'tidak' => 'Tidak, belum memiliki cold chain yang sesuai',
                                    ])
                                    ->required()
                            ]),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    public function getTitle(): string
    {
        return 'Pengajuan Akun Baru';
    }

    public function getDescription(): string
    {
        return 'Silakan isi formulir berikut untuk mengajukan akun baru. Pastikan semua informasi yang diberikan akurat dan lengkap.';
    }

    public function getHeading(): string
    {
        return 'Pengajuan Akun Baru';
    }
}
