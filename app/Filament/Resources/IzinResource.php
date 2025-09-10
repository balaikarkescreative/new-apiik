<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IzinResource\Pages;
use App\FileLabels;
use App\Mail\IzinCreatedNotification;
use App\Models\Izin;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class IzinResource extends Resource
{
    use FileLabels;

    protected static ?string $model = Izin::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Kelola Pengajuan Izin';

    protected static ?string $title = 'Pengajuan Izin';

    protected static ?string $slug = 'izin';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Generate custom filename for uploaded documents
     */
    private static function generateCustomFilename(
        string $documentType,
        string $extension,
        ?int $userId = null,
    ): string {
        if (auth()->user()->hasRole('admin')) {

            $userName = User::find($userId)
                ?->name ?? 'Admin';
        } else {
            $userName = auth()->user()->name;
        }
        $timestamp = date('dmyHi');

        // Clean filename from special characters
        $cleanDocumentType = preg_replace(
            "/[^A-Za-z0-9\s\-_]/",
            '',
            $documentType
        );
        $cleanUserName = preg_replace("/[^A-Za-z0-9\s\-_]/", '', $userName);

        return $cleanDocumentType.
            ' '.
            $cleanUserName.
            ' ['.
            $timestamp.
            '].'.
            $extension;
    }

    public static function canCreate(): bool
    {
        // Admin selalu bisa create
        if (auth()->user()->name === 'Admin') {
            return true;
        }

        // User biasa cek apakah sudah pernah mengajukan izin baru
        // $hasIzinBaru = Izin::where("user_id", auth()->id())
        //     ->where("kategori_izin", "Izin Baru")
        //     ->exists();

        // // // Jika sudah ada izin baru, cek apakah sudah selesai
        // if ($hasIzinBaru) {
        //     $latestIzin = Izin::where("user_id", auth()->id())
        //         ->where("kategori_izin", "Izin Baru")
        //         ->with("izinPersetujuan")
        //         ->orderBy("tanggal_pengajuan", "desc")
        //         ->first();

        //     // Hanya bisa create lagi jika izin sudah selesai (ada PKS)
        //     return $latestIzin &&
        //         $latestIzin->izinPersetujuan &&
        //         $latestIzin->izinPersetujuan->file_persetujuan;
        // }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pengajuan Izin')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Pemohon')
                                ->required()
                                ->relationship('user', 'name', function (Builder $query) {
                                    // Admin can see all active users, regular users only see themselves
                                    if (auth()->user()->hasRole('admin')) {
                                        return $query->where('status_akun', 'Aktif')
                                            ->orderBy('name');
                                    }

                                    return $query->where('id', auth()->id());
                                })
                                ->searchable()
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    // Show name with kotakab for better identification
                                    $name = $record->name;
                                    $kotakab = $record->kotakab ?? 'Lokasi tidak diset';

                                    if (auth()->user()->hasRole('admin')) {
                                        return $name.' ('.$kotakab.')';
                                    }

                                    return $name.' ('.$kotakab.')';
                                })
                                ->placeholder(function () {
                                    if (auth()->user()->hasRole('admin')) {
                                        return 'Pilih pemohon...';
                                    }

                                    $kotakab = auth()->user()->kotakab ?? 'Lokasi tidak diset';

                                    return auth()->user()->name.' ('.$kotakab.')';
                                })
                                ->disabled(fn () => ! auth()->user()->hasRole('admin'))
                                ->dehydrated(true) // Ensure value is saved even when disabled

                                ->searchable(['name', 'kotakab']),

                            Forms\Components\Select::make('kategori_izin')
                                ->label('Kategori Izin')
                                ->options([
                                    'Izin Baru' => 'Izin Baru',
                                    'Perpanjang Izin' => 'Perpanjang Izin',
                                ])
                                ->dehydrated()
                                ->required()
                                ->default(function () {
                                    $type = request()->get('type');

                                    return match ($type) {
                                        'baru' => 'Izin Baru',
                                        'perpanjang' => 'Perpanjang Izin',
                                        default => 'Izin Baru',
                                    };
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Hanya validasi untuk non-admin
                                    if (
                                        auth()->user()->hasRole('user') &&
                                        $state === 'Izin Baru'
                                    ) {
                                        $userId = $get('user_id') ?? auth()->id();
                                        $existingNewPermit = Izin::where('user_id', $userId)
                                            ->where('kategori_izin', 'Izin Baru')
                                            ->whereHas('izinDokumenPks', function ($query) {
                                                $query->whereNotNull('dokumen_pks_bkk');
                                            })
                                            ->exists();

                                        if ($existingNewPermit) {
                                            Notification::make()
                                                ->title('Peringatan')
                                                ->body(
                                                    'Anda sudah pernah mengajukan Izin Baru sebelumnya. Silakan gunakan Perpanjang Izin untuk memperpanjang izin yang sudah ada.'
                                                )
                                                ->warning()
                                                ->send();

                                            $set('kategori_izin', 'Perpanjang Izin');
                                        }
                                    }
                                }),

                            Forms\Components\DatePicker::make('tanggal_pengajuan')
                                ->label('Tanggal Pengajuan')
                                ->native(false)

                                ->default(now())
                                ->disabled(fn () => auth()->user()->name !== 'Admin')
                                ->required(),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('nomor_surat')
                                ->label('Nomor Surat Pengajuan')
                                ->maxLength(255)
                                ->required(),

                            Forms\Components\DatePicker::make('tanggal_surat')
                                ->label('Tanggal Surat')
                                ->native(false)

                                ->required(),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    0 => 'Pending',
                                    1 => 'Disetujui',
                                    2 => 'Ditolak',
                                ])
                                ->default(0)
                                ->visible(fn () => auth()->user()->name === 'Admin'),
                        ]),
                ])
                ->compact()
                ->collapsible(false),

            Forms\Components\Section::make('Catatan Hasil Verifikasi')
                ->schema([
                    Forms\Components\Textarea::make('catatan_perbaikan')
                        ->label('Catatan')
                        ->disabled()
                        ->formatStateUsing(fn ($record) => $record->izinDokumen?->catatan ?? '')
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(function ($record) {
                            if (! $record) {
                                return false;
                            }

                            $izinDokumen = \App\Models\IzinDokumen::where('izin_id', $record->id)->first();

                            return $izinDokumen &&
                                $izinDokumen->catatan &&
                                $izinDokumen->hasil === 2; // Hanya tampil jika berkas tidak lengkap
                        }),
                ])
                ->visible(function ($record) {
                    if (! $record) {
                        return false;
                    }

                    $izinDokumen = \App\Models\IzinDokumen::where('izin_id', $record->id)->first();

                    return $izinDokumen && $izinDokumen->catatan;
                })
                ->collapsible()
                ->collapsed(false)
                ->icon('heroicon-o-chat-bubble-left-right'),

            Forms\Components\Section::make('Dokumen Pendukung')
                ->description('Semua dokumen wajib diupload. Format PDF, maksimal 3MB per dokumen.')
                ->schema([
                    Forms\Components\FileUpload::make('file1')
                        ->label(self::getIzinLabel('file1'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file1'),
                                $file->getClientOriginalExtension(),
                                // get user ID if available from the form data
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file2')
                        ->label(self::getIzinLabel('file2'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file2'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file3')
                        ->label(self::getIzinLabel('file3'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file3'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file4')
                        ->label(self::getIzinLabel('file4'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file4'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file5')
                        ->label(self::getIzinLabel('file5'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file5'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file6')
                        ->label(self::getIzinLabel('file6'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file6'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file7')
                        ->label(self::getIzinLabel('file7'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file7'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file8')
                        ->label(self::getIzinLabel('file8'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file8'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file9')
                        ->label(self::getIzinLabel('file9'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file9'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file10')
                        ->label(self::getIzinLabel('file10'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file10'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file11')
                        ->label(self::getIzinLabel('file11'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file11'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file12')
                        ->label(self::getIzinLabel('file12'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file12'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file13')
                        ->label(self::getIzinLabel('file13'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file13'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file14')
                        ->label(self::getIzinLabel('file14'))
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getIzinLabel('file14'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\DateTimePicker::make('date_updated')
                        ->label('Tanggal Update')
                        ->default(now())
                        ->hidden(),
                ])
                ->columns(2)
                ->disabled(function () {
                    return auth()->user()->name !== 'Admin' && empty(auth()->user()->email);
                })
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Izin::query()
                    ->with([
                        'user',
                        'izinDokumen',
                        'izinVisitasi',
                        'izinPersetujuan',
                        'izinDokumenPks',
                    ])
            )
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->searchable()
            ->headerActions([
                Tables\Actions\Action::make('downloadLaporan')
                    ->label('Download Laporan')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->native(false)

                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->native(false)

                            ->required(),
                        Forms\Components\Select::make('kategori_izin')
                            ->label('Kategori Izin')
                            ->options([
                                'semua' => 'Semua',
                                'Izin Baru' => 'Izin Baru',
                                'Perpanjang Izin' => 'Perpanjang Izin',
                            ])
                            ->default('semua'),
                        Forms\Components\Select::make('status_progress')
                            ->label('Status Progress')
                            ->options([
                                'semua' => 'Semua',
                                'dokumen_diajukan' => 'Dokumen Diajukan',
                                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                'berkas_lengkap' => 'Berkas Lengkap',
                                'berkas_tidak_lengkap' => 'Berkas Tidak Lengkap',
                                'visitasi_dijadwalkan' => 'Visitasi Dijadwalkan',
                                'disetujui' => 'Disetujui - Menunggu PKS',
                                'selesai' => 'Selesai',
                            ])
                            ->default('semua'),
                        Forms\Components\Select::make('format')
                            ->label('Format File')
                            ->options([
                                'excel' => 'Excel (.xlsx)',
                                'csv' => 'CSV (.csv)',
                                'pdf' => 'PDF (.pdf)',
                            ])
                            ->default('excel')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return static::downloadLaporanIzin($data);
                    }),
            ])
            ->columns([
                // Tanggal Pengajuan - Sortable & Searchable
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->description(function ($record) {
                        return $record->nomor_surat.' - '.$record->kategori_izin;
                    })
                    ->sortable()
                    ->searchable(),

                // Klinik/RS + Kota/Kab (Combined) - Same as IcvResource
                Tables\Columns\TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        $klinik = $record->user?->name ?? 'N/A';

                        return $klinik;
                    })
                    ->description(function ($record) {
                        $kotakab = $record->user?->kotakab ?? 'Lokasi tidak diset';

                        return $kotakab;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('kotakab', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('users', 'tbl_izin.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction);
                    }),

                // Progress Status - Similar to IcvResource
                Tables\Columns\TextColumn::make('progress_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Cek apakah sudah ada PKS
                        if (
                            $record->izinDokumenPks &&
                            $record->izinDokumenPks->dokumen_pks_bkk
                        ) {
                            return 'Selesai';
                        }

                        // Cek apakah sudah disetujui
                        if (
                            $record->izinPersetujuan &&
                            $record->izinPersetujuan->file_persetujuan
                        ) {
                            return 'Disetujui - Menunggu PKS';
                        }

                        // Cek apakah sudah dijadwalkan visitasi
                        if (
                            $record->izinDokumenPks &&
                            $record->izinDokumenPks->tanggal_visitasi
                        ) {
                            return 'Visitasi Dijadwalkan';
                        }

                        // Cek apakah dokumen sudah diverifikasi
                        if ($record->izinDokumen) {
                            switch ($record->izinDokumen->hasil) {
                                case 1:
                                    return 'Berkas Lengkap';
                                case 2:
                                    return 'Berkas Tidak Lengkap';
                                default:
                                    return 'Menunggu Verifikasi';
                            }
                        }

                        return 'Dokumen Diajukan';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Selesai' => 'success',
                            'Disetujui - Menunggu PKS' => 'info',
                            'Visitasi Dijadwalkan' => 'warning',
                            'Berkas Lengkap' => 'success',
                            'Berkas Tidak Lengkap' => 'danger',
                            'Menunggu Verifikasi' => 'gray',
                            'Dokumen Diajukan' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting based on progress priority
                        return $query
                            ->leftJoin('tbl_izin_dokumen', 'tbl_izin.id', '=', 'tbl_izin_dokumen.izin_id')
                            ->leftJoin('tbl_izin_dokumen_pks', 'tbl_izin.id', '=', 'tbl_izin_dokumen_pks.id_izin')
                            ->leftJoin('tbl_izin_persetujuan', 'tbl_izin.id', '=', 'tbl_izin_persetujuan.id_izin')
                            ->select('tbl_izin.*')
                            ->selectRaw('
                                CASE
                                    WHEN tbl_izin_dokumen_pks.dokumen_pks_bkk IS NOT NULL THEN 6
                                    WHEN tbl_izin_persetujuan.file_persetujuan IS NOT NULL THEN 5
                                    WHEN tbl_izin_dokumen_pks.tanggal_visitasi IS NOT NULL THEN 4
                                    WHEN tbl_izin_dokumen.hasil = 1 THEN 3
                                    WHEN tbl_izin_dokumen.hasil = 2 THEN 2
                                    WHEN tbl_izin_dokumen.hasil = 0 THEN 1
                                    ELSE 0
                                END as progress_order
                            ')
                            ->orderBy('progress_order', $direction);
                    }),

                // Detail Info (toggleable) - Similar to IcvResource
                Tables\Columns\TextColumn::make('detail_info')
                    ->label('Detail')
                    ->getStateUsing(function ($record) {
                        $details = [];

                        if ($record->izinDokumen && $record->izinDokumen->hasil) {
                            $hasil = match ($record->izinDokumen->hasil) {
                                1 => 'Lengkap',
                                2 => 'Tidak Lengkap',
                                default => 'Pending'
                            };
                            $details[] = 'Verifikasi: '.$hasil;
                        }

                        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
                            $details[] = 'Visitasi: '.Carbon::parse($record->izinDokumenPks->tanggal_visitasi)->translatedFormat('d M Y');
                        }

                        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
                            $details[] = 'Status: Disetujui';
                        }

                        return implode("\n", $details) ?: 'Belum ada detail';
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if ($state === 'Belum ada detail') {
                            return '<span class="text-xs text-gray-400">'.$state.'</span>';
                        }

                        $lines = explode("\n", $state);
                        $formatted = array_map(function ($line) {
                            return '<div class="text-xs">'.$line.'</div>';
                        }, $lines);

                        return '<div class="space-y-1">'.implode('', $formatted).'</div>';
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                // Date Range Filter
                DateRangeFilter::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan'),

                // Klinik/RS Filter
                SelectFilter::make('user_id')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->relationship('user', 'name')
                    ->preload(),

                // Kota/Kabupaten Filter - Fixed to query from Izin instead of User
                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->options(function () {
                        return Izin::join('users', 'tbl_izin.user_id', '=', 'users.id')
                            ->distinct()
                            ->whereNotNull('users.kotakab')
                            ->orderBy('users.kotakab')
                            ->pluck('users.kotakab', 'users.kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->whereHas('user', function (Builder $query) use ($data) {
                            $query->where('kotakab', $data['value']);
                        });
                    })
                    ->preload(),

                // Kategori Izin Filter
                SelectFilter::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->options([
                        'Izin Baru' => 'Izin Baru',
                        'Perpanjang Izin' => 'Perpanjang Izin',
                    ]),

                // Progress Status Filter
                SelectFilter::make('status_progress')
                    ->label('Status Progress')
                    ->options([
                        'semua' => 'Semua',
                        'dokumen_diajukan' => 'Dokumen Diajukan',
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'berkas_lengkap' => 'Berkas Lengkap',
                        'berkas_tidak_lengkap' => 'Berkas Tidak Lengkap',
                        'visitasi_dijadwalkan' => 'Visitasi Dijadwalkan',
                        'disetujui' => 'Disetujui - Menunggu PKS',
                        'selesai' => 'Selesai',
                    ])
                    ->default('semua')
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'dokumen_diajukan' => $query->whereDoesntHave('izinDokumen'),
                            'menunggu_verifikasi' => $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 0)),
                            'berkas_lengkap' => $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 1)),
                            'berkas_tidak_lengkap' => $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 2)),
                            'visitasi_dijadwalkan' => $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('tanggal_visitasi')),
                            'disetujui' => $query->whereHas('izinPersetujuan', fn ($q) => $q->whereNotNull('file_persetujuan')),
                            'selesai' => $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('dokumen_pks_bkk')),
                            default => $query,
                        };
                    }),

                // Status Filter
                SelectFilter::make('status')
                    ->label('Status Pengajuan')
                    ->options([
                        0 => 'Pending',
                        1 => 'Disetujui',
                        2 => 'Ditolak',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->size('sm'),

                Tables\Actions\EditAction::make()
                    ->label('Edit Izin')
                    ->icon('heroicon-o-pencil')
                    ->size('sm'),

                Tables\Actions\Action::make('verifikasi_dokumen')
                    ->label('Verifikasi Dokumen')
                    ->icon('heroicon-o-document-check')
                    ->color('info')
                    ->size('sm')
                    ->visible(
                        fn ($record) => ! $record->izinDokumenPks ||
                        $record->izinDokumenPks->dokumen_pks_bkk === null
                    )
                    ->url(
                        fn ($record) => IzinResource::getUrl('verifikasi', [
                            'record' => $record->id,
                        ])
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(
                        'Hapus Terpilih'
                    ),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Data Izin')
            ->emptyStateDescription(
                'Belum ada pengajuan izin yang masuk ke sistem.'
            )
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated([15, 30, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIzins::route('/'),
            'create' => Pages\CreateIzin::route('/create'),
            'edit' => Pages\EditIzin::route('/{record}/edit'),
            'verifikasi' => Pages\VerifikasiIzin::route('verifikasi'),
            'daftar-pengajuan' => Pages\DaftarPengajuan::route(
                'daftar-pengajuan'
            ),
            'surat-persetujuan' => Pages\SuratPersetujuan::route(
                '/surat-persetujuan'
            ),
        ];
    }

    public static function sendEmailNotification(Izin $izin): void
    {
        try {
            // Pastikan user memiliki email sebelum mengirim
            if ($izin->user && $izin->user->email) {
                Mail::to($izin->user->email)->send(new IzinCreatedNotification($izin));
            } else {
                error_log('Cannot send email notification for Izin ID: '.$izin->id.'. User email is empty.');

                return;
            }

            // Kirim email ke admin (opsional)
            $adminEmail = config('mail.admin_email');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new IzinCreatedNotification($izin));
            }

        } catch (\Exception $e) {
            // Log error jika email gagal dikirim
            error_log('Failed to send email notification for Izin ID: '.$izin->id.'. Error: '.$e->getMessage());
        }
    }

    public static function downloadLaporanIzin(array $data)
    {
        $query = Izin::with([
            'user',
            'izinDokumen',
            'izinVisitasi',
            'izinPersetujuan',
            'izinDokumenPks',
        ])
            ->whereBetween('tanggal_pengajuan', [$data['tanggal_mulai'], $data['tanggal_selesai']]);

        // Filter berdasarkan kategori izin
        if ($data['kategori_izin'] !== 'semua') {
            $query->where('kategori_izin', $data['kategori_izin']);
        }

        // Filter berdasarkan status progress
        if ($data['status_progress'] !== 'semua') {
            switch ($data['status_progress']) {
                case 'menunggu_verifikasi':
                    $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 0));
                    break;
                case 'berkas_lengkap':
                    $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 1));
                    break;
                case 'berkas_tidak_lengkap':
                    $query->whereHas('izinDokumen', fn ($q) => $q->where('hasil', 2));
                    break;
                case 'visitasi_dijadwalkan':
                    $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('tanggal_visitasi'));
                    break;
                case 'disetujui':
                    $query->whereHas('izinPersetujuan', fn ($q) => $q->whereNotNull('file_persetujuan'));
                    break;
                case 'selesai':
                    $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('dokumen_pks_bkk'));
                    break;
            }
        }

        $laporan = $query->orderBy('tanggal_pengajuan', 'desc')->get();

        switch ($data['format']) {
            case 'excel':
                return static::downloadExcelIzin($laporan, $data);
            case 'csv':
                return static::downloadCsvIzin($laporan, $data);
            case 'pdf':
                return static::downloadPdfIzin($laporan, $data);
            default:
                return static::downloadExcelIzin($laporan, $data);
        }
    }

    protected static function downloadExcelIzin(Collection $data, array $params)
    {
        $filename = 'laporan_pengajuan_izin_'.date('Y-m-d_H-i-s').'.csv';

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
                'ID',
                'Pemohon',
                'Kota/Kabupaten',
                'Kategori Izin',
                'Nomor Surat',
                'Tanggal Pengajuan',
                'Tanggal Surat',
                'Status Progress',
                'Tanggal Update',
            ]);

            // Data
            foreach ($data as $row) {
                $statusProgress = static::getProgressStatus($row);

                fputcsv($output, [
                    $row->id,
                    $row->user->name ?? '',
                    $row->user->kotakab ?? '',
                    $row->kategori_izin ?? '',
                    $row->nomor_surat ?? '',
                    $row->tanggal_pengajuan ?? '',
                    $row->tanggal_surat ?? '',
                    $statusProgress,
                    $row->date_updated ?? '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected static function downloadCsvIzin(Collection $data, array $params)
    {
        return static::downloadExcelIzin($data, $params);
    }

    protected static function downloadPdfIzin(Collection $data, array $params)
    {
        // Install: composer require barryvdh/laravel-dompdf
        $pdf = \PDF::loadView('reports.laporan-izin', [
            'data' => $data,
            'tanggal_mulai' => $params['tanggal_mulai'],
            'tanggal_selesai' => $params['tanggal_selesai'],
            'kategori_izin' => $params['kategori_izin'],
            'status_progress' => $params['status_progress'],
        ]);

        $filename = 'laporan_pengajuan_izin_'.date('Y-m-d_H-i-s').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    protected static function getProgressStatus($record)
    {
        // Cek apakah sudah ada PKS
        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
            return 'Selesai';
        }

        // Cek apakah sudah disetujui
        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
            return 'Disetujui - Menunggu PKS';
        }

        // Cek apakah sudah dijadwalkan visitasi (dari izinDokumenPks)
        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
            return 'Visitasi Dijadwalkan';
        }

        // Cek apakah dokumen sudah diverifikasi
        if ($record->izinDokumen) {
            switch ($record->izinDokumen->hasil) {
                case 1:
                    return 'Berkas Lengkap';
                case 2:
                    return 'Berkas Tidak Lengkap';
                default:
                    return 'Menunggu Verifikasi';
            }
        }

        return 'Dokumen Diajukan';
    }
}
