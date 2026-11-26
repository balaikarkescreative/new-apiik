<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IzinResource\Pages;
use App\FileLabels;
use App\Mail\IzinCreatedNotification;
use App\Models\Izin;
use App\Models\User;
use App\Services\Export\IzinExportService;
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

                            // Hidden field untuk use_new_labels
                            // Data baru = true (format baru), data lama = tetap gunakan nilai yang ada
                            Forms\Components\Hidden::make('use_new_labels')
                                ->default(fn ($record) => $record ? (bool) $record->use_new_labels : true)
                                ->dehydrated()
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if (! $record) {
                                        $component->state(true);
                                    }
                                }),
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
                                ($izinDokumen->hasil === 2 || $izinDokumen->hasil === 3); // Tampil jika berkas tidak lengkap atau ditolak
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
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file1') : self::getIzinLabel('file1'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file1') : self::getIzinLabel('file1'),
                                $file->getClientOriginalExtension(),
                                // get user ID if available from the form data
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file2')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file2') : self::getIzinLabel('file2'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file2') : self::getIzinLabel('file2'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file3')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file3') : self::getIzinLabel('file3'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file3') : self::getIzinLabel('file3'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file4')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file4') : self::getIzinLabel('file4'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file4') : self::getIzinLabel('file4'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file5')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file5') : self::getIzinLabel('file5'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file5') : self::getIzinLabel('file5'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file6')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file6') : self::getIzinLabel('file6'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file6') : self::getIzinLabel('file6'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file7')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file7') : self::getIzinLabel('file7'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file7') : self::getIzinLabel('file7'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file8')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file8') : self::getIzinLabel('file8'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file8') : self::getIzinLabel('file8'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file9')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file9') : self::getIzinLabel('file9'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file9') : self::getIzinLabel('file9'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file10')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file10') : self::getIzinLabel('file10'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file10') : self::getIzinLabel('file10'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file11')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file11') : self::getIzinLabel('file11'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file11') : self::getIzinLabel('file11'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file12')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file12') : self::getIzinLabel('file12'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file12') : self::getIzinLabel('file12'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file13')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file13') : self::getIzinLabel('file13'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file13') : self::getIzinLabel('file13'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file14')
                        ->label(fn ($record, callable $get) => ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file14') : self::getIzinLabel('file14'))
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
                                TemporaryUploadedFile $file, callable $get, $record
                            ): string => self::generateCustomFilename(
                                ($record ? $record->use_new_labels : ($get('use_new_labels') ?? true)) ? self::getNewIzinLabel('file14') : self::getIzinLabel('file14'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id

                            )
                        ),

                    Forms\Components\FileUpload::make('file15')
                        ->label(self::getNewIzinLabel('file15'))
                        ->helperText('Format PDF, maksimal 3MB. Hanya untuk format dokumen terbaru.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required(fn (callable $get) => $get('use_new_labels'))
                        ->visible(fn (callable $get) => $get('use_new_labels'))
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getNewIzinLabel('file15'),
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->user()->id
                            )
                        ),

                    Forms\Components\FileUpload::make('file16')
                        ->label(self::getNewIzinLabel('file16'))
                        ->helperText('Format PDF, maksimal 3MB. Hanya untuk format dokumen terbaru.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('izin-documents')
                        ->downloadable()
                        ->openable()
                        ->required(fn (callable $get) => $get('use_new_labels'))
                        ->visible(fn (callable $get) => $get('use_new_labels'))
                        ->getUploadedFileNameForStorageUsing(
                            fn (
                                TemporaryUploadedFile $file, callable $get
                            ): string => self::generateCustomFilename(
                                self::getNewIzinLabel('file16'),
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
                    ->modalHeading('Export Laporan Pengajuan Izin')
                    ->modalSubmitActionLabel('Download')
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
                                'pks_tersedia' => 'PKS Tersedia - Menunggu Persetujuan',
                                'disetujui' => 'Disetujui',
                                'selesai' => 'Selesai',
                            ])
                            ->default('semua'),
                        Forms\Components\Select::make('kotakab')
                            ->label('Kota/Kabupaten')
                            ->options(fn () => User::whereNotNull('kotakab')->where('kotakab', '!=', '')->distinct()->orderBy('kotakab')->pluck('kotakab', 'kotakab')->toArray())
                            ->searchable()
                            ->placeholder('Semua Kota/Kabupaten'),
                        Forms\Components\Select::make('user_id')
                            ->label('Faskes / Klinik')
                            ->options(fn () => User::whereNotNull('name')->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->placeholder('Semua Faskes / Klinik'),
                        Forms\Components\Radio::make('tipe_laporan')
                            ->label('Tipe Laporan')
                            ->options([
                                'lengkap' => 'Laporan Lengkap (Semua Detail & Riwayat)',
                                'ringkas' => 'Laporan Ringkas (Data Utama)',
                            ])
                            ->default('lengkap')
                            ->required(),
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
                    ->action(function (array $data, \Livewire\Component $livewire) {
                        $count = IzinExportService::countRecords($data);

                        if ($count <= 25 || ($data['format'] ?? 'excel') === 'csv') {
                            return IzinExportService::export($data);
                        }

                        $batchInfo = IzinExportService::initBatch($data);
                        $livewire->dispatch('open-export-progress-modal', $batchInfo);
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
                        // Cek apakah sudah disetujui (step terakhir)
                        if (
                            $record->izinPersetujuan &&
                            $record->izinPersetujuan->file_persetujuan
                        ) {
                            return 'Selesai';
                        }

                        // Cek apakah sudah ada PKS
                        if (
                            $record->izinDokumenPks &&
                            $record->izinDokumenPks->dokumen_pks_bkk
                        ) {
                            return 'PKS Tersedia - Menunggu Persetujuan';
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
                                case 3:
                                    return 'Ditolak';
                                default:
                                    return 'Menunggu Verifikasi';
                            }
                        }

                        return 'Dokumen Diajukan';
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            'Selesai' => 'success',
                            'PKS Tersedia - Menunggu Persetujuan' => 'info',
                            'Visitasi Dijadwalkan' => 'warning',
                            'Berkas Lengkap' => 'success',
                            'Berkas Tidak Lengkap' => 'danger',
                            'Ditolak' => 'danger',
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
                                    WHEN tbl_izin_persetujuan.file_persetujuan IS NOT NULL THEN 6
                                    WHEN tbl_izin_dokumen_pks.dokumen_pks_bkk IS NOT NULL THEN 5
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
                                3 => 'Ditolak',
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
                        'pks_tersedia' => 'PKS Tersedia - Menunggu Persetujuan',
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
                            'pks_tersedia' => $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('dokumen_pks_bkk'))
                                ->whereDoesntHave('izinPersetujuan', fn ($q) => $q->whereNotNull('file_persetujuan')),
                            'selesai' => $query->whereHas('izinPersetujuan', fn ($q) => $q->whereNotNull('file_persetujuan')),
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
            'izin-persetujuan' => Pages\DaftarIzinPersetujuan::route(
                '/izin-persetujuan'
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
        return IzinExportService::export($data);
    }

    protected static function downloadExcelIzin(Collection $data, array $params)
    {
        return IzinExportService::downloadExcel($data, $params);
    }

    protected static function downloadCsvIzin(Collection $data, array $params)
    {
        return IzinExportService::downloadCsv($data, $params);
    }

    protected static function downloadPdfIzin(Collection $data, array $params)
    {
        return IzinExportService::downloadPdf($data, $params);
    }

    public static function getProgressStatus($record): string
    {
        return IzinExportService::getProgressStatus($record);
    }
}
