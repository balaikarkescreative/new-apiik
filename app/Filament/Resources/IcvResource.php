<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IcvResource\Pages;
use App\FileLabels;
use App\Models\Icv;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class IcvResource extends Resource
{
    use FileLabels;

    protected static ?string $model = Icv::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'ICV';

    protected static ?string $modelLabel = 'ICV';

    protected static ?string $pluralModelLabel = 'ICV';

    protected static ?string $slug = 'icv';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Generate custom filename for uploaded documents
     */
    private static function generateCustomFilename(
        string $documentType,
        string $extension,
        ?int $userId = null,
    ): string {
        // Determine user name based on role and provided userId
        if (auth()->user()->hasRole('admin') && $userId) {
            $userName = User::find($userId)?->name ?? 'Admin';
        } else {
            $userName = auth()->user()->name ?? 'Unknown';
        }

        $timestamp = date('dmyHi');

        // Clean filename from special characters
        $cleanDocumentType = preg_replace(
            "/[^A-Za-z0-9\s\-_]/",
            '',
            $documentType
        );
        $cleanUserName = preg_replace("/[^A-Za-z0-9\s\-_]/", '', $userName);

        return 'ICV '.
            $cleanDocumentType.
            ' '.
            $cleanUserName.
            ' ['.
            $timestamp.
            '].'.
            $extension;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Pengajuan')->schema([
                Forms\Components\Grid::make(1)->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Pemohon')
                        ->default(fn () => auth()->user()->hasRole('admin') ? null : auth()->id())
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
                            // Show more info for admin to distinguish users
                            if (auth()->user()->hasRole('admin')) {
                                return $record->name.' ('.($record->kotakab ?? 'Lokasi tidak diset').')';
                            }

                            return $record->name;
                        })
                        ->placeholder(function () {
                            if (auth()->user()->hasRole('admin')) {
                                return 'Pilih pemohon...';
                            }

                            return auth()->user()->name;
                        })
                        ->disabled(fn () => ! auth()->user()->hasRole('admin'))
                        ->dehydrated(true) // Ensure value is saved even when disabled
                        ->helperText(function () {
                            if (auth()->user()->hasRole('admin')) {
                                return 'Pilih user yang akan dibuatkan pengajuan ICV';
                            }

                            return 'Pengajuan akan dibuat atas nama Anda';
                        }),

                    Forms\Components\DatePicker::make('tanggal_pengajuan')
                        ->label('Tanggal Pengajuan')
                        ->native(false)
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->default(now()),

                    Forms\Components\Select::make('jumlah_icv')
                        ->label('Jumlah ICV')
                        ->options(function () {
                            $options = [];
                            for ($i = 25; $i <= 500; $i += 25) {
                                $options[$i] = $i;
                            }

                            return $options;
                        })
                        ->searchable()
                        ->required(),
                ]),
            ]),

            Forms\Components\Section::make('Catatan Hasil Verifikasi')
                ->schema([

                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan')
                        ->disabled()
                        ->rows(3)
                        ->formatStateUsing(function ($record) {
                            if (! $record) {
                                return '';
                            }

                            $icvCekDokumen = \App\Models\IcvCekDokumen::where('id_icv', $record->id)->first();

                            return $icvCekDokumen ? $icvCekDokumen->catatan : '';
                        })
                        ->columnSpanFull()
                        ->visible(function ($record) {
                            if (! $record) {
                                return false;
                            }

                            $icvCekDokumen = \App\Models\IcvCekDokumen::where('id_icv', $record->id)->first();

                            return $icvCekDokumen &&
                                $icvCekDokumen->catatan &&
                                $icvCekDokumen->hasil_verif === '2'; // Hanya tampil jika berkas tidak lengkap
                        }),
                ])
                ->visible(function ($record) {
                    if (! $record) {
                        return false;
                    }

                    $icvCekDokumen = \App\Models\IcvCekDokumen::where('id_icv', $record->id)->first();

                    return $icvCekDokumen && $icvCekDokumen->catatan;
                })
                ->collapsible()
                ->collapsed(false)
                ->icon('heroicon-o-chat-bubble-left-right'),

            Forms\Components\Section::make('Upload Dokumen')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\FileUpload::make('file1')
                        ->label(self::getIcvLabel('file1'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Surat Permohonan',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),

                    Forms\Components\FileUpload::make('file2')
                        ->label(self::getIcvLabel('file2'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Bukti Laporan',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),

                    Forms\Components\FileUpload::make('file3')
                        ->label(self::getIcvLabel('file3'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Rekap Pemakaian',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),

                    Forms\Components\FileUpload::make('file4')
                        ->label(self::getIcvLabel('file4'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Faktur Pembelian',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),

                    Forms\Components\FileUpload::make('file5')
                        ->label(self::getIcvLabel('file5'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Berita Acara',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),

                    Forms\Components\FileUpload::make('file6')
                        ->label(self::getIcvLabel('file6'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Daftar Penerima',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),

                    Forms\Components\FileUpload::make('file7')
                        ->label(self::getIcvLabel('file7'))
                        ->required()
                        ->helperText('Format PDF, maksimal 3MB.')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(3072)
                        ->directory('icv-documents')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file, callable $get): string => self::generateCustomFilename(
                                'Screenshot Sinkarkes',
                                $file->getClientOriginalExtension(),
                                $get('user_id') ?? auth()->id()
                            )
                        ),
                ]),
            ])
                ->description('Semua dokumen wajib diupload. Format PDF, maksimal 3MB per dokumen.')
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Icv::query()
                    ->with([
                        'user',
                        'icvCekDokumen',
                        'icvAlokasi',
                        'icvBilling',
                        'icvBuktiBayar',
                        'icvJadwal',
                        'icvSbbk',
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
                        Forms\Components\Select::make('progress_status')
                            ->label('Progress Status')
                            ->options([
                                'semua' => 'Semua',
                                'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                'berkas_lengkap' => 'Berkas Lengkap',
                                'berkas_tidak_lengkap' => 'Berkas Tidak Lengkap',
                                'dialokasikan' => 'ICV Dialokasikan',
                                'menunggu_pembayaran' => 'Menunggu Pembayaran',
                                'selesai' => 'Selesai',
                            ])
                            ->default('semua'),
                        Forms\Components\Select::make('format')
                            ->label('Format File')
                            ->options([
                                'excel' => 'Excel (.xlsx)',
                                'csv' => 'CSV (.csv)',
                            ])
                            ->default('excel')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        return static::downloadLaporanIcv($data);
                    }),
            ])
            ->columns([
                // Tanggal Pengajuan - Sortable & Searchable
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                // Klinik/RS + Kota/Kab (Combined) - Fixed searchable
                Tables\Columns\TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        $klinik = $record->user?->name ?? 'N/A';
                        $lokasi = $record->user?->kotakab ?? 'N/A';

                        return '<div>'.
                            '<div class="font-medium text-sm">'.e($klinik).'</div>'.
                            '<div class="text-xs text-gray-500">'.e($lokasi).'</div>'.
                            '</div>';
                    })
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('kotakab', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('users', 'tbl_icv.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction);
                    }),

                // Jumlah ICV - Sortable
                Tables\Columns\TextColumn::make('jumlah_icv')
                    ->label('Jumlah ICV')
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                // Progress Status - Filterable
                Tables\Columns\TextColumn::make('progress_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Cek jika ditolak
                        if ($record->status === 2) {
                            return 'Ditolak';
                        }

                        // Cek apakah sudah ada SBBK
                        if (
                            $record->icvSbbk &&
                            $record->icvSbbk->sinkarkes === 'Sudah'
                        ) {
                            return 'Selesai';
                        }

                        // Cek apakah ICV sudah diambil
                        if (
                            $record->icvJadwal &&
                            $record->icvJadwal->status === 1
                        ) {
                            return 'ICV Diambil';
                        }

                        // Cek apakah jadwal sudah ditentukan
                        if (
                            $record->icvJadwal &&
                            $record->icvJadwal->jadwal_pengambilan
                        ) {
                            return 'Jadwal Ditentukan';
                        }

                        // Cek apakah bukti bayar sudah diverifikasi
                        if (
                            $record->icvBuktiBayar &&
                            $record->icvBuktiBayar->status === 1
                        ) {
                            return 'Bayar Terverifikasi';
                        }

                        // Cek apakah sudah upload bukti bayar
                        if (
                            $record->icvBuktiBayar &&
                            $record->icvBuktiBayar->file_bukti_bayar
                        ) {
                            return 'Menunggu Verif Bayar';
                        }

                        // Cek apakah sudah ada billing
                        if ($record->icvBilling) {
                            return 'Menunggu Pembayaran';
                        }

                        // Cek apakah sudah dialokasi
                        if ($record->icvAlokasi) {
                            return 'Dialokasikan';
                        }

                        // Cek apakah dokumen sudah diverifikasi
                        if ($record->icvCekDokumen) {
                            if (
                                $record->icvCekDokumen->hasil_verif ===
                                'Berkas Lengkap'
                            ) {
                                return 'Berkas Lengkap';
                            } elseif (
                                $record->icvCekDokumen->hasil_verif ===
                                'Berkas Tidak Lengkap'
                            ) {
                                return 'Berkas Tidak Lengkap';
                            }
                        }

                        // Default: Menunggu Verifikasi
                        return 'Menunggu Verifikasi';
                    })
                    ->color(function ($state) {
                        return match (true) {
                            str_contains($state, 'Selesai') => 'success',
                            str_contains($state, 'Diambil') => 'success',
                            str_contains($state, 'Terverifikasi') => 'success',
                            str_contains($state, 'Berkas Lengkap') => 'success',
                            str_contains($state, 'Ditentukan') => 'info',
                            str_contains($state, 'Dialokasikan') => 'info',
                            str_contains($state, 'Berkas Tidak Lengkap') => 'danger',
                            str_contains($state, 'Ditolak') => 'danger',
                            str_contains($state, 'Pembayaran') => 'warning',
                            str_contains($state, 'Menunggu') => 'warning',
                            default => 'gray',
                        };
                    }),

                // Detail Info (toggleable)
                Tables\Columns\TextColumn::make('detail_info')
                    ->label('Detail')
                    ->getStateUsing(function ($record) {
                        $details = [];

                        if ($record->icvAlokasi) {
                            $details[] = 'Alokasi: '.number_format($record->icvAlokasi->icv_disetujui ?? 0);
                        }

                        if ($record->icvBilling) {
                            $details[] = 'Billing: '.($record->icvBilling->kode_billing ?? 'N/A');
                        }

                        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
                            $details[] = 'Jadwal: '.Carbon::parse($record->icvJadwal->jadwal_pengambilan)->translatedFormat('d M Y');
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

                // Filter::make('tanggal_pengajuan')
                //     ->form([
                //         DatePicker::make('tanggal_dari')
                //             ->label('Tanggal Dari'),
                //         DatePicker::make('tanggal_sampai')
                //             ->label('Tanggal Sampai'),
                //     ])
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['tanggal_dari'],
                //                 fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pengajuan', '>=', $date),
                //             )
                //             ->when(
                //                 $data['tanggal_sampai'],
                //                 fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pengajuan', '<=', $date),
                //             );
                //     }),

                // Klinik/RS Filter
                SelectFilter::make('user_id')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->relationship('user', 'name')
                    ->preload(),

                // Kota/Kabupaten Filter
                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->options(function () {
                        return User::whereHas('icvs')
                            ->distinct()
                            ->whereNotNull('kotakab')
                            ->orderBy('kotakab')
                            ->pluck('kotakab', 'kotakab');
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

                // Progress Status Filter
                SelectFilter::make('status_filter')
                    ->label('Status')
                    ->options([
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'berkas_lengkap' => 'Berkas Lengkap',
                        'berkas_tidak_lengkap' => 'Berkas Tidak Lengkap',
                        'dialokasikan' => 'Dialokasikan',
                        'menunggu_pembayaran' => 'Menunggu Pembayaran',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'menunggu_verifikasi' => $query->whereDoesntHave('icvCekDokumen')
                                ->orWhereHas('icvCekDokumen', fn ($q) => $q->whereNull('hasil_verif')),
                            'berkas_lengkap' => $query->whereHas(
                                'icvCekDokumen',
                                fn ($q) => $q->where('hasil_verif', 'Berkas Lengkap')
                            ),
                            'berkas_tidak_lengkap' => $query->whereHas(
                                'icvCekDokumen',
                                fn ($q) => $q->where('hasil_verif', 'Berkas Tidak Lengkap')
                            ),
                            'dialokasikan' => $query->whereHas('icvAlokasi'),
                            'menunggu_pembayaran' => $query->whereHas('icvBilling'),
                            'selesai' => $query->whereHas(
                                'icvSbbk',
                                fn ($q) => $q->where('sinkarkes', 'Sudah')
                            ),
                            default => $query,
                        };
                    }),

                // Jumlah ICV Filter
                SelectFilter::make('jumlah_icv_filter')
                    ->label('Jumlah ICV')
                    ->options([
                        '1-50' => '1-50',
                        '51-100' => '51-100',
                        '101-200' => '101-200',
                        '200+' => '200+',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            '1-50' => $query->whereBetween('jumlah_icv', [1, 50]),
                            '51-100' => $query->whereBetween('jumlah_icv', [51, 100]),
                            '101-200' => $query->whereBetween('jumlah_icv', [101, 200]),
                            '200+' => $query->where('jumlah_icv', '>', 200),
                            default => $query
                        };
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->size('sm'),
                Tables\Actions\EditAction::make()
                    ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
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
            'index' => Pages\ListIcvs::route('/'),
            'create' => Pages\CreateIcv::route('/create'),
            'daftar-pengajuan' => Pages\DaftarPengajuan::route('/daftar-pengajuan'),
            'verifikasi' => Pages\VerifikasiIcv::route('/verifikasi'),
            'edit' => Pages\EditIcv::route('/{record}/edit'),
            'penerbitan-billing' => Pages\PenerbitanKodeBilling::route('/penerbitan-billing'),
            'upload-sbbk' => Pages\UploadSbbk::route('/upload-sbbk'),
            'jadwal-distribusi' => Pages\JadwalDistribusiIcv::route('/jadwal-distribusi'),
            'verifikasi-dokumen' => Pages\VerifikasiDokumenIcv::route('/verifikasi-dokumen'),
        ];
    }

    public static function downloadLaporanIcv(array $data)
    {
        $query = Icv::with([
            'user',
            'icvCekDokumen',
            'icvAlokasi',
            'icvBilling',
            'icvBuktiBayar',
            'icvJadwal',
            'icvSbbk',
        ])
            ->whereBetween('tanggal_pengajuan', [$data['tanggal_mulai'], $data['tanggal_selesai']]);

        // Filter berdasarkan progress status
        if ($data['progress_status'] !== 'semua') {
            switch ($data['progress_status']) {
                case 'menunggu_verifikasi':
                    $query->whereDoesntHave('icvCekDokumen')
                        ->orWhereHas('icvCekDokumen', fn ($q) => $q->whereNull('hasil_verif'));
                    break;
                case 'berkas_lengkap':
                    $query->whereHas('icvCekDokumen', fn ($q) => $q->where('hasil_verif', 'Berkas Lengkap'));
                    break;
                case 'berkas_tidak_lengkap':
                    $query->whereHas('icvCekDokumen', fn ($q) => $q->where('hasil_verif', 'Berkas Tidak Lengkap'));
                    break;
                case 'dialokasikan':
                    $query->whereHas('icvAlokasi');
                    break;
                case 'menunggu_pembayaran':
                    $query->whereHas('icvBilling');
                    break;
                case 'selesai':
                    $query->whereHas('icvSbbk', fn ($q) => $q->where('sinkarkes', 'Sudah'));
                    break;
            }
        }

        $laporan = $query->orderBy('tanggal_pengajuan', 'desc')->get();

        switch ($data['format']) {
            case 'excel':
                return static::downloadExcelIcv($laporan, $data);
            case 'csv':
                return static::downloadCsvIcv($laporan, $data);
            default:
                return static::downloadExcelIcv($laporan, $data);
        }
    }

    protected static function downloadExcelIcv(Collection $data, array $params)
    {
        $filename = 'laporan_pengajuan_icv_'.date('Y-m-d_H-i-s').'.csv';

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
                'Tanggal Pengajuan',
                'Klinik/RS',
                'Kota/Kabupaten',
                'Jumlah ICV',
                'Progress Status',
                'Tanggal Update',
            ]);

            // Data
            foreach ($data as $row) {
                $progressStatus = static::getProgressStatusIcv($row);

                fputcsv($output, [
                    $row->id,
                    $row->tanggal_pengajuan,
                    $row->user->name ?? '',
                    $row->user->kotakab ?? '',
                    $row->jumlah_icv,
                    $progressStatus,
                    $row->updated_at ?? '',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected static function downloadCsvIcv(Collection $data, array $params)
    {
        return static::downloadExcelIcv($data, $params);
    }

    protected static function getProgressStatusIcv($record)
    {
        if ($record->icvSbbk && $record->icvSbbk->sinkarkes === 'Sudah') {
            return 'Selesai (SBBK Uploaded)';
        }

        if ($record->icvJadwal && $record->icvJadwal->status === 1) {
            return 'ICV Sudah Diambil';
        }

        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
            return 'Jadwal Pengambilan Ditentukan';
        }

        if ($record->icvBuktiBayar && $record->icvBuktiBayar->status === 1) {
            return 'Pembayaran Terverifikasi';
        }

        if ($record->icvBuktiBayar && $record->icvBuktiBayar->file_bukti_bayar) {
            return 'Menunggu Verifikasi Pembayaran';
        }

        if ($record->icvBilling) {
            return 'Menunggu Pembayaran';
        }

        if ($record->icvAlokasi) {
            return 'ICV Dialokasikan';
        }

        if ($record->icvCekDokumen) {
            if ($record->icvCekDokumen->hasil_verif === 'Berkas Lengkap') {
                return 'Berkas Lengkap - Menunggu Alokasi';
            } elseif ($record->icvCekDokumen->hasil_verif === 'Berkas Tidak Lengkap') {
                return 'Berkas Tidak Lengkap';
            }
        }

        return 'Menunggu Verifikasi';
    }
}
