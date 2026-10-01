<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\MapSearchService;
use Carbon\Carbon;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'RS/Klinik';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Pengaturan User';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Klinik/RS')
                    ->schema([
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'Klinik' => 'Klinik',
                                'Rumah Sakit' => 'Rumah Sakit',
                            ])
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Klinik/RS')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        PhoneInput::make('kontak_pj')
                            ->label('No. Whatsapp')
                            ->defaultCountry('ID')
                            ->allowDropdown(false)
                            ->required(fn () => ! auth()->user()->hasRole('admin'))
                            ->columnSpan(2),

                        Forms\Components\Select::make('map_search')
                            ->label('Cari Titik Lokasi di Peta')
                            ->placeholder('Ketik nama faskes, klinik, RS, alamat, atau tempel koordinat lat, lng...')
                            ->helperText('Pilih lokasi dari saran pencarian untuk menggeser pin peta secara otomatis.')
                            ->prefixIcon('heroicon-o-magnifying-glass')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => MapSearchService::search($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => MapSearchService::getLabel($value))
                            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                if (! $state) {
                                    return;
                                }

                                $parts = explode(',', $state);
                                if (count($parts) === 2) {
                                    $lat = (float) trim($parts[0]);
                                    $lng = (float) trim($parts[1]);

                                    $set('location', [
                                        'lat' => $lat,
                                        'lng' => $lng,
                                    ]);

                                    $livewire->dispatch('refreshMap');
                                }
                            })
                            ->reactive()
                            ->dehydrated(false)
                            ->extraAttributes([
                                'style' => 'position: relative; z-index: 30;',
                            ])
                            ->extraFieldWrapperAttributes([
                                'style' => 'position: relative; z-index: 30;',
                            ])
                            ->columnSpan(2),

                        Map::make('location')
                            ->label('Lokasi Klinik/RS')
                            ->extraStyles([
                                'min-height: 350px',
                                'position: relative',
                                'z-index: 1',
                                'isolation: isolate',
                            ])
                            ->defaultLocation(
                                latitude: auth()->user()->latitude ?? -6.9039,
                                longitude: auth()->user()->longitude ?? 107.6186,
                            )
                            ->clickable(true)
                            ->showMyLocationButton(true)
                            ->zoom(12)
                            ->required(fn () => ! auth()->user()->hasRole('admin'))
                            ->columnSpan(2),

                        Forms\Components\Select::make('kotakab')
                            ->label('Kota/Kabupaten')
                            ->options(function () {
                                return \App\Models\Kotakab::aktif()
                                    ->orderBy('nama_kotakab')
                                    ->get()
                                    ->pluck('nama_kotakab', 'nama_kotakab')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('alamat')
                            ->label('Alamat')
                            ->required(fn () => ! auth()->user()->hasRole('admin'))
                            ->maxLength(500)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Data Izin Operasional')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_surat_izin_operasional')
                            ->label('Nomor Surat Izin Operasional')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'users', column: 'nomor_surat_izin_operasional', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Nomor surat izin operasional ini sudah terdaftar.',
                            ])
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('masa_berlaku_surat_izin_operasional')
                            ->label('Masa Berlaku Surat Izin Operasional')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Persyaratan Vaksinasi')
                    ->schema([
                        Forms\Components\Radio::make('pertanyaan1')
                            ->label('Dokter vaksinator sudah pelatihan vaksinologi?')
                            ->options([
                                'Ya' => 'Ya',
                                'Tidak' => 'Tidak',
                            ])
                            ->required()
                            ->inline()
                            ->columnSpan(2),

                        Forms\Components\Radio::make('pertanyaan2')
                            ->label('Faskes sudah memiliki coldchain sesuai ketentuan?')
                            ->options([
                                'Ya' => 'Ya',
                                'Tidak' => 'Tidak',
                            ])
                            ->required()
                            ->inline()
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Data Akun (Opsional)')
                    ->description('Biarkan kosong jika akan diisi otomatis saat verifikasi')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->unique(table: 'users', column: 'username', ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->minLength(8)
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->columnSpan(2),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generateCredentials')
                                ->label('Generate Username & Password')
                                ->icon('heroicon-o-key')
                                ->color('success')
                                ->action(function (callable $set, callable $get, $record) {
                                    $name = $get('name');
                                    if (empty($name)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Error')
                                            ->body('Nama Klinik/RS harus diisi terlebih dahulu')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    // Generate username
                                    $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
                                    $baseUsername = $cleanName.'_bkkbandung';

                                    // Check if username already exists (exclude current user)
                                    $username = $baseUsername;
                                    $iteration = 1;

                                    $query = User::where('username', $username);
                                    if ($record && $record->exists) {
                                        $query->where('id', '!=', $record->id);
                                    }

                                    while ($query->exists()) {
                                        $username = $baseUsername.$iteration;
                                        $query = User::where('username', $username);
                                        if ($record && $record->exists) {
                                            $query->where('id', '!=', $record->id);
                                        }
                                        $iteration++;
                                    }

                                    // Set the generated values
                                    $set('username', $username);
                                    $set('password', '12345678');

                                    \Filament\Notifications\Notification::make()
                                        ->title('Berhasil')
                                        ->body('Username dan password berhasil di-generate')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('copyCredentials')
                                ->label('Copy Kredensial')
                                ->icon('heroicon-o-clipboard-document')
                                ->color('info')
                                ->action(function (callable $get, $livewire) {
                                    $username = $get('username');
                                    $password = $get('password');
                                    $name = $get('name');
                                    $kontak = $get('kontak_pj');
                                    $email = $get('email');

                                    if (empty($username) || empty($password)) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Error')
                                            ->body('Username dan password harus diisi terlebih dahulu')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $credentials = "Nama Faskes: {$name}\\n";
                                    $credentials .= "Username: {$username}\\n";
                                    $credentials .= "Email: {$email}\\n";
                                    $credentials .= "Password: {$password}\\n";
                                    $credentials .= "Kontak PJ: {$kontak}\\n";

                                    $livewire->js('
                                        window.navigator.clipboard.writeText("'.$credentials.'");
                                        $tooltip("Kredensial berhasil disalin ke clipboard", { timeout: 2000 });
                                    ');
                                }),
                        ])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('verifikasi_akun')
                            ->label('Status Verifikasi')
                            ->options([
                                'Belum Diverifikasi' => 'Belum Diverifikasi',
                                'Diterima' => 'Diterima',
                                'Ditolak' => 'Ditolak',
                            ])
                            ->default('Belum Diverifikasi')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status_akun')
                            ->label('Status Akun')
                            ->options([
                                'Tidak Aktif' => 'Tidak Aktif',
                                'Aktif' => 'Aktif',
                            ])
                            ->default('Tidak Aktif')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_blocked')
                            ->label('Blokir Akun')
                            ->default(false)
                            ->inline(false)
                            ->columnSpan(2),

                        Forms\Components\Hidden::make('date_updated')
                            ->default(now()),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    protected static function generateUniqueUsername(string $name, $excludeUserId = null): string
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        $baseUsername = $cleanName.'_bkkbandung';

        $query = User::where('username', $baseUsername);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        if (! $query->exists()) {
            return $baseUsername;
        }

        $counter = 1;
        do {
            $username = $baseUsername.$counter;
            $query = User::where('username', $username);
            if ($excludeUserId) {
                $query->where('id', '!=', $excludeUserId);
            }
            $counter++;
        } while ($query->exists());

        return $username;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->select('users.*')
                ->whereHas('roles', function (Builder $query) {
                    $query->where('name', 'user');
                })
                ->withoutGlobalScopes([SoftDeletingScope::class]))
            ->columns([

                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->getStateUsing(function ($record) {
                        // Jika date_updated lebih tua dari created_at, gunakan date_updated
                        $createdAt = $record->created_at;
                        $dateUpdated = $record->date_updated;

                        if ($dateUpdated && $createdAt && $dateUpdated < $createdAt) {
                            return $dateUpdated;
                        }

                        return $createdAt;
                    })
                    ->wrapHeader()
                    ->date('d M Y')
                    ->sortable(['created_at', 'date_updated']),

                // Compact Faskes Info
                Tables\Columns\TextColumn::make('faskes_info')
                    ->label('Klinik/RS')
                    ->getStateUsing(function ($record) {
                        $name = $record->name;
                        $kategori = $record->kategori;
                        $kotakab = $record->kotakab ?? 'N/A';

                        return "{$name}\n{$kategori} • {$kotakab}";
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        $lines = explode("\n", $state);

                        return '<div class="space-y-1">'.
                            '<div class="font-medium text-sm">'.$lines[0].'</div>'.
                            '<div class="text-xs text-gray-500">'.$lines[1].'</div>'.
                            '</div>';
                    })
                    ->searchable(['name', 'kategori', 'kotakab'])
                    ->sortable(['name']),

                // Izin Operasional
                Tables\Columns\TextColumn::make('izin_operasional')
                    ->label('Izin Operasional')
                    ->getStateUsing(function ($record) {
                        $nomor = $record->nomor_surat_izin_operasional ?? 'N/A';
                        $tanggal = $record->masa_berlaku_surat_izin_operasional
                            ? Carbon::parse($record->masa_berlaku_surat_izin_operasional)->translatedFormat('d M Y')
                            : 'N/A';

                        return "{$nomor}\nBerlaku s/d {$tanggal}";
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        $lines = explode("\n", $state);

                        return '<div class="space-y-1">'.
                            '<div class="font-medium text-sm">'.$lines[0].'</div>'.
                            '<div class="text-xs text-gray-500">'.$lines[1].'</div>'.
                            '</div>';
                    })
                    ->searchable(['nomor_surat_izin_operasional'])
                    ->sortable(['masa_berlaku_surat_izin_operasional']),

                // Kontak
                Tables\Columns\TextColumn::make('kontak_pj')
                    ->label('Kontak')
                    ->searchable()
                    ->sortable(),

                // Status Verifikasi & Akun
                Tables\Columns\TextColumn::make('verifikasi_akun')
                    ->label('Verifikasi')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Convert NULL to a default value at the data level
                        return $record->verifikasi_akun ?? 'Belum Diverifikasi';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Diterima' => 'success',
                            'Ditolak' => 'danger',
                            'Belum Diverifikasi' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'Belum Diverifikasi' => 'Belum',
                            'Diterima' => 'Diterima',
                            'Ditolak' => 'Ditolak',
                            default => 'Belum',
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_akun')
                    ->label('Status Akun')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Convert NULL to a default value at the data level
                        return $record->status_akun ?? 'Tidak Aktif';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Aktif' => 'success',
                            'Tidak Aktif' => 'warning',
                            default => 'warning',
                        };
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'Aktif' => 'Aktif',
                            'Tidak Aktif' => 'Nonaktif',
                            default => 'Nonaktif',
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_blocked')
                    ->label('Blokir')
                    ->badge()
                    ->color(fn (string $state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state ? 'Diblokir' : 'Tidak Diblokir')
                    ->sortable(),

                // Persyaratan Vaksinasi
                Tables\Columns\TextColumn::make('persyaratan')
                    ->label('Persyaratan')
                    ->getStateUsing(function ($record) {
                        $p1 = $record->pertanyaan1 ?? 'N/A';
                        $p2 = $record->pertanyaan2 ?? 'N/A';

                        return "Pelatihan: {$p1}\nCold Chain: {$p2}";
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        $lines = explode("\n", $state);

                        return '<div class="space-y-1">'.
                            '<div class="text-xs">'.$lines[0].'</div>'.
                            '<div class="text-xs">'.$lines[1].'</div>'.
                            '</div>';
                    })
                    ->toggleable(),
            ])
            ->filters([
                // Kategori Filter
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'Klinik' => 'Klinik',
                        'Rumah Sakit' => 'Rumah Sakit',
                    ]),

                // Kota/Kabupaten Filter
                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->options(function () {
                        return User::whereHas('roles', fn ($q) => $q->where('name', 'user'))
                            ->whereNotNull('kotakab')
                            ->distinct('kotakab')
                            ->pluck('kotakab', 'kotakab')
                            ->filter()
                            ->sort()
                            ->toArray();
                    }),

                // Status Verifikasi Filter
                SelectFilter::make('verifikasi_akun')
                    ->label('Status Verifikasi')
                    ->options([
                        'Belum Diverifikasi' => 'Belum Diverifikasi',
                        'Diterima' => 'Diterima',
                        'Ditolak' => 'Ditolak',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'Belum Diverifikasi') {
                            return $query->whereNull('verifikasi_akun');
                        } elseif ($value) {
                            return $query->where('verifikasi_akun', $value);
                        }

                        return $query;
                    }),

                // Status Akun Filter
                SelectFilter::make('status_akun')
                    ->label('Status Akun')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Tidak Aktif' => 'Tidak Aktif',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'Tidak Aktif') {
                            return $query->where(function ($query) {
                                $query->whereNull('status_akun')
                                    ->orWhere('status_akun', 'Tidak Aktif');
                            });
                        } elseif ($value === 'Aktif') {
                            return $query->where('status_akun', 'Aktif');
                        }

                        return $query;
                    }),

                TernaryFilter::make('is_blocked')
                    ->label('Status Blokir')
                    ->placeholder('Semua')
                    ->trueLabel('Diblokir')
                    ->falseLabel('Tidak Diblokir'),

                // Persyaratan Filters
                TernaryFilter::make('pertanyaan1')
                    ->label('Pelatihan Vaksinologi')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn (Builder $query) => $query->where('pertanyaan1', 'Ya'),
                        false: fn (Builder $query) => $query->where('pertanyaan1', 'Tidak'),
                    ),

                TernaryFilter::make('pertanyaan2')
                    ->label('Cold Chain')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn (Builder $query) => $query->where('pertanyaan2', 'Ya'),
                        false: fn (Builder $query) => $query->where('pertanyaan2', 'Tidak'),
                    ),

                // Filter Akun Baru
                Filter::make('akun_baru')
                    ->label('Akun Baru')
                    // ->toggle()
                    ->query(
                        fn (Builder $query): Builder => $query->where(function ($q) {
                            $q->where(function ($subQ) {
                                $subQ->whereNull('verifikasi_akun')
                                    ->orWhere('verifikasi_akun', 'Belum Diverifikasi');
                            })->where(function ($subQ) {
                                $subQ->whereNull('status_akun')
                                    ->orWhere('status_akun', 'Tidak Aktif');
                            });
                        })
                    ),
            ])
            ->headerActions([
                // Tambahkan header action untuk export Excel
                Tables\Actions\Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'all' => 'Semua',
                                'Klinik' => 'Klinik',
                                'Rumah Sakit' => 'Rumah Sakit',
                            ])
                            ->default('all'),
                        Forms\Components\Select::make('kotakab')
                            ->label('Kota/Kabupaten')
                            ->options(function () {
                                return User::whereHas('roles', fn ($q) => $q->where('name', 'user'))
                                    ->whereNotNull('kotakab')
                                    ->distinct('kotakab')
                                    ->pluck('kotakab', 'kotakab')
                                    ->prepend('Semua', 'all')
                                    ->toArray();
                            })
                            ->searchable()
                            ->default('all'),
                        Forms\Components\Select::make('verifikasi_akun')
                            ->label('Status Verifikasi')
                            ->options([
                                'all' => 'Semua',
                                'Belum Diverifikasi' => 'Belum Diverifikasi',
                                'Diterima' => 'Diterima',
                                'Ditolak' => 'Ditolak',
                            ])
                            ->default('all'),
                        Forms\Components\Select::make('status_akun')
                            ->label('Status Akun')
                            ->options([
                                'all' => 'Semua',
                                'Aktif' => 'Aktif',
                                'Tidak Aktif' => 'Tidak Aktif',
                            ])
                            ->default('all'),
                        Forms\Components\Select::make('is_blocked')
                            ->label('Status Blokir')
                            ->options([
                                'all' => 'Semua',
                                '1' => 'Diblokir',
                                '0' => 'Tidak Diblokir',
                            ])
                            ->default('all'),
                    ])
                    ->action(function (array $data) {
                        return static::exportToExcel($data);
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->defaultSort('date_updated', 'desc')
            ->striped()
            ->paginated([15, 30, 50])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->size('sm')
                    ->color('info')
                    ->modalHeading(fn (User $record): string => "Detail {$record->name}")
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->infolist([
                        Section::make('Informasi Klinik/RS')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nama Klinik/RS')
                                            ->weight('bold')
                                            ->color('primary'),

                                        TextEntry::make('kategori')
                                            ->label('Kategori')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Klinik' => 'success',
                                                'Rumah Sakit' => 'info',
                                                default => 'gray',
                                            }),

                                        TextEntry::make('alamat')
                                            ->label('Alamat')
                                            ->columnSpanFull(),

                                        TextEntry::make('kotakab')
                                            ->label('Kota/Kabupaten')
                                            ->icon('heroicon-o-map-pin'),

                                        TextEntry::make('kontak_pj')
                                            ->label('No. WhatsApp')
                                            ->icon('heroicon-o-phone')
                                            ->copyable()
                                            ->copyMessage('Nomor telepon berhasil disalin')
                                            ->formatStateUsing(
                                                fn (string $state): string => preg_replace('/(\d{2})(\d{4})(\d{4})(\d{2,4})/', '$1-$2-$3-$4', $state)
                                            ),
                                    ]),
                            ])
                            ->icon('heroicon-o-building-office')
                            ->collapsible(),

                        Section::make('Data Izin Operasional')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('nomor_surat_izin_operasional')
                                            ->label('Nomor Surat Izin')
                                            ->copyable()
                                            ->copyMessage('Nomor surat berhasil disalin')
                                            ->placeholder('Belum diisi'),

                                        TextEntry::make('masa_berlaku_surat_izin_operasional')
                                            ->label('Masa Berlaku')
                                            ->date('d F Y')
                                            ->placeholder('Belum diisi')
                                            ->color(function ($state) {
                                                if (! $state) {
                                                    return 'gray';
                                                }
                                                $now = now();
                                                $expiry = \Carbon\Carbon::parse($state);
                                                $daysDiff = $now->diffInDays($expiry, false);

                                                if ($daysDiff < 0) {
                                                    return 'danger';
                                                } // Expired
                                                if ($daysDiff <= 30) {
                                                    return 'warning';
                                                } // Expires soon

                                                return 'success'; // Valid
                                            })
                                            ->formatStateUsing(function ($state) {
                                                if (! $state) {
                                                    return 'Belum diisi';
                                                }

                                                $now = now();
                                                $expiry = \Carbon\Carbon::parse($state);
                                                $daysDiff = $now->diffInDays($expiry, false);
                                                $formatted = $expiry->translatedFormat('d F Y');

                                                if ($daysDiff < 0) {
                                                    return $formatted.' (KADALUARSA)';
                                                } elseif ($daysDiff <= 30) {
                                                    return $formatted." (Expires in {$daysDiff} days)";
                                                }

                                                return $formatted;
                                            }),
                                    ]),
                            ])
                            ->icon('heroicon-o-document-text')
                            ->collapsible(),

                        Section::make('Persyaratan Vaksinasi')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('pertanyaan1')
                                            ->label('Dokter vaksinator sudah pelatihan vaksinologi?')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Ya' => 'success',
                                                'Tidak' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (?string $state): string => $state ?? 'Belum dijawab'),

                                        TextEntry::make('pertanyaan2')
                                            ->label('Faskes sudah memiliki coldchain sesuai ketentuan?')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Ya' => 'success',
                                                'Tidak' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (?string $state): string => $state ?? 'Belum dijawab'),
                                    ]),
                            ])
                            ->icon('heroicon-o-shield-check')
                            ->collapsible(),

                        Section::make('Status Akun')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('verifikasi_akun')
                                            ->label('Status Verifikasi')
                                            ->badge()
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Diterima' => 'success',
                                                'Ditolak' => 'danger',
                                                'Belum Diverifikasi' => 'warning',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (?string $state): string => $state ?? 'Belum Diverifikasi'),

                                        TextEntry::make('status_akun')
                                            ->label('Status Akun')
                                            ->badge()
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Aktif' => 'success',
                                                'Tidak Aktif' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (?string $state): string => $state ?? 'Tidak Aktif'),

                                        TextEntry::make('is_blocked')
                                            ->label('Status Blokir')
                                            ->badge()
                                            ->color(fn (string $state): string => $state ? 'danger' : 'success')
                                            ->formatStateUsing(fn (string $state): string => $state ? 'Diblokir' : 'Tidak Diblokir'),

                                        TextEntry::make('date_updated')
                                            ->label('Terakhir Diperbarui')
                                            ->dateTime('d F Y, H:i')
                                            ->placeholder('Belum pernah diperbarui'),
                                    ]),
                            ])
                            ->icon('heroicon-o-user-circle')
                            ->collapsible(),

                        Section::make('Informasi Akun Login')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('username')
                                            ->label('Username')
                                            ->copyable()
                                            ->copyMessage('Username berhasil disalin')
                                            ->placeholder('Belum diset'),

                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->copyable()
                                            ->copyMessage('Email berhasil disalin')
                                            ->placeholder('Belum diset'),
                                    ]),
                            ])
                            ->icon('heroicon-o-key')
                            ->collapsible(),

                        Section::make('Koordinat Lokasi')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('latitude')
                                            ->label('Latitude')
                                            ->copyable()
                                            ->placeholder('Belum diset'),

                                        TextEntry::make('longitude')
                                            ->label('Longitude')
                                            ->copyable()
                                            ->placeholder('Belum diset'),
                                    ]),
                            ])
                            ->icon('heroicon-o-map')
                            ->collapsed()
                            ->collapsible(),
                    ]),

                Tables\Actions\EditAction::make()
                    ->size('sm'),

                Tables\Actions\Action::make('toggleBlock')
                    ->label(fn (User $record) => $record->is_blocked ? 'Buka Blokir' : 'Blokir')
                    ->icon(fn (User $record) => $record->is_blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (User $record) => $record->is_blocked ? 'success' : 'danger')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->is_blocked = ! $record->is_blocked;
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil')
                            ->body($record->is_blocked ? 'Akun berhasil diblokir' : 'Blokir akun berhasil dibuka')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('toggleModal')
                    ->label('Status')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->size('sm')
                    ->form([
                        Forms\Components\Toggle::make('toggleVerifikasi')
                            ->label('Ubah Status Verifikasi Akun')
                            ->default(function (User $record) {
                                return $record->verifikasi_akun === 'Diterima';
                            })
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, User $record) {
                                if ($state) {
                                    $record->verifikasi_akun = 'Diterima';
                                    if (empty($record->username)) {
                                        $record->username = static::generateUniqueUsername($record->name, $record->id);
                                    }
                                    if (empty($record->password)) {
                                        $record->password = bcrypt('12345678');
                                    }
                                    $set('defaultUsername', $record->username);
                                } else {
                                    $record->verifikasi_akun = 'Ditolak';
                                }
                                $record->save();
                            }),

                        Forms\Components\Toggle::make('toggleStatus')
                            ->label('Ubah Status Akun')
                            ->default(function (User $record) {
                                return $record->status_akun === 'Aktif';
                            })
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state, User $record) {
                                $previousStatus = $record->status_akun;
                                $record->status_akun = $state ? 'Aktif' : 'Tidak Aktif';
                                $record->save();

                                // Kirim email hanya jika status berubah menjadi aktif dan sebelumnya null (bukan 'Tidak Aktif')
                                if ($state && $previousStatus === null) {
                                    static::sendAccountActivationEmail($record);
                                }
                            })
                            ->disabled(fn (User $record): bool => $record->verifikasi_akun !== 'Diterima'),

                        Forms\Components\TextInput::make('defaultUsername')
                            ->label('Username yang akan di-generate')
                            ->default(function ($record) {
                                if (! empty($record->username)) {
                                    return $record->username;
                                }

                                return static::generateUniqueUsername($record->name, $record->id);
                            })
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('defaultPassword')
                            ->label('Password Default')
                            ->default('12345678')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('reset')
                                ->label('Reset Username & Password')
                                ->requiresConfirmation()
                                ->action(function (callable $set, User $record) {
                                    $newUsername = static::generateUniqueUsername($record->name, $record->id);
                                    $record->username = $newUsername;
                                    $record->password = bcrypt('12345678');
                                    $record->date_updated = now();
                                    $record->save();
                                    $set('defaultUsername', $newUsername);
                                })
                                ->color('secondary')
                                ->icon('heroicon-o-arrow-path'),
                        ]),
                    ])
                    ->modalHeading('Ubah Status Akun')
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Export data user ke Excel
     */
    public static function exportToExcel(array $filters): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Buat query berdasarkan filter
        $query = User::query()
            ->whereHas('roles', function (Builder $query) {
                $query->where('name', 'user');
            });

        // Aplikasikan filter
        if ($filters['kategori'] !== 'all') {
            $query->where('kategori', $filters['kategori']);
        }

        if ($filters['kotakab'] !== 'all') {
            $query->where('kotakab', $filters['kotakab']);
        }

        if ($filters['verifikasi_akun'] !== 'all') {
            $query->where('verifikasi_akun', $filters['verifikasi_akun']);
        }

        if ($filters['status_akun'] !== 'all') {
            $query->where('status_akun', $filters['status_akun']);
        }

        if (isset($filters['is_blocked']) && $filters['is_blocked'] !== 'all') {
            $query->where('is_blocked', $filters['is_blocked'] === '1');
        }

        // Ambil data
        $data = $query->orderBy('name')->get();

        // Buat file Excel
        $filename = 'daftar_faskes_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($data) {
            $output = fopen('php://output', 'w');

            // BOM untuk UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header
            fputcsv($output, [
                'Nama Faskes',
                'Kategori',
                'Kota/Kabupaten',
                'Alamat',
                'No. WhatsApp',
                'No. Surat Izin Operasional',
                'Masa Berlaku Izin',
                'Status Verifikasi',
                'Status Akun',
                'Status Blokir',
                'Pelatihan Vaksinologi',
                'Cold Chain',
                'Username',
                'Email',
                'Terakhir Diperbarui',
            ]);

            // Data
            foreach ($data as $row) {
                fputcsv($output, [
                    $row->name,
                    $row->kategori,
                    $row->kotakab,
                    $row->alamat,
                    "'".$row->kontak_pj, // Tambahkan kutip satu di depan kontak PJ
                    "'".$row->nomor_surat_izin_operasional, // Tambahkan kutip satu di depan nomor surat
                    $row->masa_berlaku_surat_izin_operasional
                        ? Carbon::parse($row->masa_berlaku_surat_izin_operasional)->format('d/m/Y')
                        : '-',
                    $row->verifikasi_akun ?? 'Belum Diverifikasi',
                    $row->status_akun ?? 'Tidak Aktif',
                    $row->is_blocked ? 'Diblokir' : 'Tidak Diblokir',
                    $row->pertanyaan1 ?? '-',
                    $row->pertanyaan2 ?? '-',
                    $row->username ?? '-',
                    $row->email ?? '-',
                    $row->date_updated
                        ? Carbon::parse($row->date_updated)->format('d/m/Y H:i')
                        : '-',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Kirim email aktivasi akun dengan username dan password
     */
    protected static function sendAccountActivationEmail(User $user): void
    {
        try {
            // Pastikan user memiliki email
            if (empty($user->email)) {
                \Log::warning('Cannot send activation email: user has no email', [
                    'user_id' => $user->id,
                    'name' => $user->name,
                ]);

                return;
            }

            // Pastikan user memiliki username dan password
            if (empty($user->username)) {
                \Log::warning('Cannot send activation email: user has no username', [
                    'user_id' => $user->id,
                    'name' => $user->name,
                ]);

                return;
            }

            // Kirim email aktivasi
            \Mail::to($user->email)->send(new \App\Mail\AccountActivationNotification($user));

            // Log berhasil
            \Log::info('Account activation email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
            ]);

            // Notifikasi ke admin
            \Filament\Notifications\Notification::make()
                ->title('Email Aktivasi Dikirim')
                ->body("Email aktivasi akun telah dikirim ke {$user->name} ({$user->email})")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Log::error('Failed to send account activation email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            // Notifikasi error ke admin
            \Filament\Notifications\Notification::make()
                ->title('Gagal Mengirim Email')
                ->body("Gagal mengirim email aktivasi ke {$user->name}. Error: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }
}
