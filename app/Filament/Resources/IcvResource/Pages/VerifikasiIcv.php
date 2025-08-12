<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\FileLabels;
use App\Models\Icv;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Tabs;
use App\Mail\IcvVerifikasiNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\IcvAlokasiNotification;
use App\Mail\IcvBillingNotification;
use App\Mail\IcvJadwalNotification;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;

use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;
use App\Models\Izin;
use App\Models\LaporanPenggunaanIcv;

class VerifikasiIcv extends Page implements HasTable
{
    use InteractsWithTable, FileLabels;

    protected static string $resource = IcvResource::class;

    protected static string $model = Icv::class;

    protected static string $view = 'filament.resources.icv-resource.pages.verifikasi-icv';

    protected static ?string $title = 'Verifikasi ICV';

    protected static ?string $navigationLabel = 'Verifikasi';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['admin'])) {
            // abort(403, 'Akses ditolak. Hanya admin yang dapat mengelola pengajuan ICV.');
            redirect()->to('/');

        }
    }

    public function table(Table $table): Table
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
            ->columns([
                // Tanggal Pengajuan
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                // Klinik/RS + Kota/Kab (Combined) - Same as IcvResource
                TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        $klinik = $record->user?->name ?? 'N/A';
                        $lokasi = $record->user?->kotakab ?? 'N/A';

                        return '<div>' .
                            '<div class="font-medium text-sm">' . e($klinik) . '</div>' .
                            '<div class="text-xs text-gray-500">' . e($lokasi) . '</div>' .
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

                // Jumlah ICV
                TextColumn::make("jumlah_icv")
                    ->label("Jumlah ICV")
                    ->numeric()
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                // Progress Overview
                TextColumn::make('progress_overview')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $steps = [
                            'Verif' => $record->icvCekDokumen?->hasil_verif == '1' ? 1 : 0,
                            'Alokasi' => $record->icvAlokasi?->icv_disetujui ? 1 : 0,
                            'Billing' => $record->icvBilling?->kode_billing ? 1 : 0,
                            'Bayar' => $record->icvBuktiBayar?->file_bukti_bayar ? 1 : 0,
                            'Jadwal' => $record->icvJadwal?->jadwal_pengambilan ? 1 : 0,
                            'Sinkarkes' => $record->icvSbbk?->sinkarkes === 'Sudah' ? 1 : 0,
                        ];

                        $completed = array_sum($steps);
                        $total = count($steps);
                        $percentage = round(($completed / $total) * 100);

                        return "{$completed}/{$total} ({$percentage}%)";
                    })
                    ->badge()
                    ->color(function ($state) {
                        $percentage = (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT);
                        return match (true) {
                            $percentage === 100 => 'success',
                            $percentage >= 70 => 'warning',
                            $percentage >= 40 => 'info',
                            default => 'gray',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting based on progress completion
                        return $query
                            ->leftJoin('tbl_icv_cek_dokumen', 'tbl_icv.id', '=', 'tbl_icv_cek_dokumen.id_icv')
                            ->leftJoin('tbl_icv_alokasi', 'tbl_icv.id', '=', 'tbl_icv_alokasi.id_icv')
                            ->leftJoin('tbl_icv_billing', 'tbl_icv.id', '=', 'tbl_icv_billing.id_icv')
                            ->leftJoin('tbl_icv_bukti_bayar', 'tbl_icv.id', '=', 'tbl_icv_bukti_bayar.id_icv')
                            ->leftJoin('tbl_icv_jadwal', 'tbl_icv.id', '=', 'tbl_icv_jadwal.id_icv')
                            ->leftJoin('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
                            ->select('tbl_icv.*')
                            ->selectRaw('
                                (CASE WHEN tbl_icv_cek_dokumen.hasil_verif = "1" THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_icv_alokasi.icv_disetujui IS NOT NULL AND tbl_icv_alokasi.icv_disetujui > 0 THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_icv_billing.kode_billing IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_icv_bukti_bayar.file_bukti_bayar IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_icv_jadwal.jadwal_pengambilan IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_icv_sbbk.sinkarkes = "Sudah" THEN 1 ELSE 0 END) as progress_score
                            ')
                            ->orderBy('progress_score', $direction);
                    }),

                // Current Step
                TextColumn::make('current_step')
                    ->label('Tahap Saat Ini')
                    ->suffix(function($record){
                        if ($record->tanggal_perbaikan) {
                            if ($record->icvCekDokumen) {
                                return '';
                            }
                            return ' (Perbaikan)';
                        }
                    })
                    ->getStateUsing(function ($record) {
                        if (!$record->icvCekDokumen || !$record->icvCekDokumen->hasil_verif) {
                            return 'Verifikasi Dokumen';
                        }
                        if ($record->icvCekDokumen->hasil_verif == '2') {
                            return 'Perbaikan Dokumen';
                        }
                        if (!$record->icvAlokasi || !$record->icvAlokasi->icv_disetujui) {
                            return 'Alokasi ICV';
                        }
                        if (!$record->icvBilling || !$record->icvBilling->kode_billing) {
                            return 'Penerbitan Billing';
                        }
                        if (!$record->icvBuktiBayar || !$record->icvBuktiBayar->file_bukti_bayar) {
                            return 'Menunggu Pembayaran';
                        }
                        if (!$record->icvBuktiBayar->file_bukti_bayar) {
                            return 'Verifikasi Pembayaran';
                        }
                        if (!$record->icvJadwal || !$record->icvJadwal->jadwal_pengambilan) {
                            return 'Penjadwalan Distribusi';
                        }
                        if (!$record->icvSbbk) {
                            return 'Update Sinkarkes';
                        }
                        if ($record->icvSbbk->sinkarkes !== 'Sudah') {
                            return 'Update Sinkarkes';
                        }
                        return 'Selesai';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'Selesai' => 'success',
                            'Perbaikan Dokumen' => 'danger',
                            'Menunggu Pembayaran' => 'warning',
                            default => 'info'
                        };
                    }),

                // Detail Status (toggleable)
                TextColumn::make('detail_status')
                    ->label('Detail Status')
                    ->getStateUsing(function ($record) {
                        $details = [];

                        if ($record->icvAlokasi?->icv_disetujui) {
                            $details[] = "Alokasi: " . number_format($record->icvAlokasi->icv_disetujui);
                        }

                        if ($record->icvBilling?->kode_billing) {
                            $details[] = "Billing: " . $record->icvBilling->kode_billing;
                        }

                        if ($record->icvJadwal?->jadwal_pengambilan) {
                            $details[] = "Jadwal: " . Carbon::parse($record->icvJadwal->jadwal_pengambilan)->translatedFormat('d M');
                        }

                        return implode("\n", $details) ?: 'Belum ada detail';
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
                        if ($state === 'Belum ada detail') {
                            return '<span class="text-xs text-gray-400">' . $state . '</span>';
                        }

                        $lines = explode("\n", $state);
                        $formatted = array_map(function ($line) {
                            return '<div class="text-xs">' . $line . '</div>';
                        }, $lines);

                        return '<div class="space-y-1">' . implode('', $formatted) . '</div>';
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                // Date Range Filter
                DateRangeFilter::make('tanggal_pengajuan')
                    ->defaultLast7Days()
                    ->label('Tanggal Pengajuan'),

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
                        return \App\Models\User::whereHas('icvs')
                            ->distinct()
                            ->whereNotNull('kotakab')
                            ->orderBy('kotakab')
                            ->pluck('kotakab', 'kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        return $query->whereHas('user', function (Builder $query) use ($data) {
                            $query->where('kotakab', $data['value']);
                        });
                    })
                    ->preload(),

                // Progress Status Filter
                SelectFilter::make('progress_status')
                    ->label('Status Progress')
                    ->options([
                        'verifikasi' => 'Verifikasi Dokumen',
                        'perbaikan' => 'Perbaikan', // Tambahkan opsi ini
                        'alokasi' => 'Alokasi ICV',
                        'billing' => 'Penerbitan Billing',
                        'pembayaran' => 'Menunggu Pembayaran',
                        'jadwal' => 'Penjadwalan',
                        'sinkarkes' => 'Update Sinkarkes',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        return match ($data['value']) {
                            'verifikasi' => $query->whereDoesntHave('icvCekDokumen')
                                ->orWhereHas('icvCekDokumen', fn($q) => $q->whereNull('hasil_verif')),
                            'perbaikan' => $query
                                ->whereNotNull('tanggal_perbaikan')
                                ->whereDoesntHave('icvCekDokumen'), // Filter perbaikan
                            'alokasi' => $query->whereHas('icvCekDokumen', fn($q) => $q->where('hasil_verif', '1'))
                                ->whereDoesntHave('icvAlokasi'),
                            'billing' => $query->whereHas('icvAlokasi')
                                ->whereDoesntHave('icvBilling'),
                            'pembayaran' => $query->whereHas('icvBilling')
                                ->whereDoesntHave('icvBuktiBayar'),
                            'jadwal' => $query->whereHas('icvBuktiBayar', fn($q) => $q->whereNotNull('file_bukti_bayar'))
                                ->whereDoesntHave('icvJadwal'),
                            'sinkarkes' => $query->whereHas('icvJadwal')
                                ->whereDoesntHave('icvSbbk', fn($q) => $q->where('sinkarkes', 'Sudah')),
                            'selesai' => $query->whereHas('icvSbbk', fn($q) => $q->where('sinkarkes', 'Sudah')),
                            default => $query,
                        };
                    }),

                // Jumlah ICV Filter
                SelectFilter::make('jumlah_icv_range')
                    ->label('Jumlah ICV')
                    ->options([
                        '1-50' => '1-50',
                        '51-100' => '51-100',
                        '101-200' => '101-200',
                        '200+' => '200+',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        return match ($data['value']) {
                            '1-50' => $query->whereBetween('jumlah_icv', [1, 50]),
                            '51-100' => $query->whereBetween('jumlah_icv', [51, 100]),
                            '101-200' => $query->whereBetween('jumlah_icv', [101, 200]),
                            '200+' => $query->where('jumlah_icv', '>', 200),
                            default => $query,
                        };
                    }),

                // Verification Result Filter
                SelectFilter::make('verification_result')
                    ->label('Hasil Verifikasi')
                    ->options([
                        'lengkap' => 'Berkas Lengkap',
                        'tidak_lengkap' => 'Berkas Tidak Lengkap',
                        'belum_verifikasi' => 'Belum Verifikasi',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        return match ($data['value']) {
                            'lengkap' => $query->whereHas('icvCekDokumen', fn($q) => $q->where('hasil_verif', '1')),
                            'tidak_lengkap' => $query->whereHas('icvCekDokumen', fn($q) => $q->where('hasil_verif', '2')),
                            'belum_verifikasi' => $query->whereDoesntHave('icvCekDokumen')
                                ->orWhereHas('icvCekDokumen', fn($q) => $q->whereNull('hasil_verif')),
                            default => $query,
                        };
                    }),
            ], FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Action::make('detail_verifikasi')
                    ->label('Detail')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->size('sm')
                    ->modalHeading(fn($record) => 'Detail Verifikasi ICV - ' . $record->user->name)
                    ->modalWidth('8xl')
                    ->modalSubmitAction(false)
                    ->form($this->getDetailVerificationForm())
                    ->fillForm(function ($record) {

                        $sisa_icv = LaporanPenggunaanIcv::where('id_user', $record->user_id)
                            ->where('id_jenis_vaksin', 2)
                            ->where('tgl_pelaporan', '<=', $record->tanggal_pengajuan)
                            ->orderBy('tgl_pelaporan', 'desc')
                            ->first()->sisa_icv ?? 0;

                        $vaccineCategories = [1, 2, 3, 4, 12]; // Define vaccine categories
                        $sisa_vaksin = 0;

                        foreach ($vaccineCategories as $jenisVaksin) {
                            $latestReport = LaporanPenggunaanIcv::where('id_user', $record->user_id)
                                ->where('id_jenis_vaksin', $jenisVaksin)
                                ->where('tgl_pelaporan', '<=', $record->tanggal_pengajuan)
                                ->orderBy('tgl_pelaporan', 'desc')
                                ->first();

                            if ($latestReport) {
                                $sisa_vaksin += $latestReport->sisa_vaksin;
                            }
                        }

                        return [
                            // Basic info
                            'nama_pemohon' => $record->user->name,
                            'tanggal_pengajuan' => $record->tanggal_pengajuan?->translatedFormat('d F Y'),
                            'jumlah_icv_diminta' => $record->jumlah_icv,
                            'sisa_icv' => $sisa_icv ?? 0,
                            'sisa_vaksin' => $sisa_vaksin ?? 0,

                            // Step 1: Verifikasi
                            'hasil_verif' => $record->icvCekDokumen?->hasil_verif,
                            'catatan_verif' => $record->icvCekDokumen?->catatan,
                            'stok_vaksin' => $record->icvCekDokumen?->stok_vaksin,
                            'tanggal_perbaikan' => $record->tanggal_perbaikan ?? null,

                            // Step 2: Alokasi
                            'icv_disetujui' => $record->icvAlokasi?->icv_disetujui,

                            // Step 3: Billing
                            'kode_billing' => $record->icvBilling?->kode_billing,
                            'nominal' => $record->icvBilling?->nominal ?? ($record->icvAlokasi?->icv_disetujui * 25000),

                            // Step 4: Pembayaran
                            // 'distribusi_icv' => $record->icvBuktiBayar?->distribusi_icv,
                            'catatan_bayar' => $record->icvBuktiBayar?->catatan,

                            // Step 5: Jadwal
                            'jadwal_pengambilan' => $record->icvJadwal?->jadwal_pengambilan,

                            // Step 6: SBBK
                            // 'file_sbbk' => $record->icvSbbk?->file_sbbk,

                            // Step 7: Sinkarkes
                            'sinkarkes' => $record->icvSbbk?->sinkarkes ?? 'Belum',
                        ];
                    })
                    ->action(function (array $data, $record) {
                        // Simpan catatan perbaikan jika ada
                        if (!empty($data['catatan_verif'])) {
                            $record->tambahCatatanPerbaikan($data['catatan_verif']);
                            // $record->tanggal_perbaikan = now();
                            $record->save();
                        }
                        // Refresh record dengan eager loading sebelum kirim email
                        $record = $record->fresh(['user', 'icvCekDokumen']);

                        // Kirim email notifikasi ke user
                        try {
                            if ($record->user && $record->user->email) {
                                Mail::to($record->user->email)->send(
                                    new IcvVerifikasiNotification(
                                        $record,
                                        $data['hasil_verif'],
                                        $data['catatan_verif'] ?? null
                                    )
                                );
                            }
                        } catch (\Exception $e) {
                            error_log('Gagal mengirim email verifikasi ICV: ' . $e->getMessage());
                        }

                        Notification::make()
                            ->title('Verifikasi Berhasil Disimpan')
                            ->icon('heroicon-o-check-circle')
                            ->success()
                            ->body('Verifikasi dokumen berhasil disimpan dan email notifikasi telah dikirim.')
                            ->send();

                        $this->redirect(VerifikasiIcv::getUrl());
                    })
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->striped()
            ->paginated([15, 30, 50]);
    }

    private function getDocumentViewFields(): array
    {
        $fields = [];

        for ($i = 1; $i <= 7; $i++) {
            $fileField = "file{$i}";
            $fields[] = TextInput::make($fileField . '_info')
                ->label(fn($record) => $record->getFileLabels()[$fileField] ?? "File {$i}")
                ->disabled()
                ->formatStateUsing(function ($record) use ($fileField) {
                    if ($record->$fileField) {
                        return basename($record->$fileField);
                    } else {
                        return 'Tidak ada file';
                    }
                })
                ->hintAction(
                    FormAction::make("download_{$fileField}")
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label(fn($record) => "Unduh " . self::getIcvLabel($fileField))
                        ->url(fn($record) => $record->$fileField ? asset('storage/' . $record->$fileField) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record->$fileField !== null)
                )
                ->dehydrated(false);
        }

        return $fields;
    }

    private function getDetailVerificationForm(): array
    {
        return [
            // Basic Information Section
            Section::make('Informasi Pengajuan')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('nama_pemohon')
                            ->label('Nama Pemohon')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('tanggal_pengajuan')
                            ->label('Tanggal Pengajuan')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('jumlah_icv_diminta')
                            ->label('Jumlah ICV Diminta')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('sisa_icv')
                            ->label('Sisa Stok ICV')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('sisa_vaksin')
                            ->label('Sisa Stok Vaksin')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('laporan_geotagging_link')
                            ->label('Link Laporan Geotagging')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $link = $record->user->laporanGeotagging?->google_drive_link;
                                return $link ?: 'Belum ada';
                            })
                            ->extraInputAttributes(function ($record) {
                                $link = $record->user->laporanGeotagging?->google_drive_link;
                                return $link ? ['onclick' => "window.open('{$link}', '_blank')"] : [];
                            })
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('open_geotagging')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(function ($record) {
                                        return $record->user->laporanGeotagging?->google_drive_link;
                                    })
                                    ->openUrlInNewTab()
                                    ->visible(function ($record) {
                                        return !empty($record->user->laporanGeotagging?->google_drive_link);
                                    })
                            ),

                        TextInput::make('link_sosialisasi_link')
                            ->label('Link Sosialisasi')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $link = $record->user->linkSosialisasi?->link;
                                return $link ?: 'Belum ada';
                            })
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('open_sosialisasi')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(function ($record) {
                                        return $record->user->linkSosialisasi?->link;
                                    })
                                    ->openUrlInNewTab()
                                    ->visible(function ($record) {
                                        return !empty($record->user->linkSosialisasi?->link);
                                    })
                            ),
                    ]),
                    Grid::make(1)->schema([
                        TextInput::make('pakta_integritas_file')
                            ->label('File Pakta Integritas')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($record) {
                                $file = $record->user->paktaIntegritas?->file;
                                return $file ? basename($file) : 'Belum ada';
                            })
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('open_pakta_integritas')
                                    ->icon('heroicon-m-arrow-down-tray')
                                    ->url(function ($record) {
                                        $file = $record->user->paktaIntegritas?->file;
                                        return $file ? asset('storage/' . $file) : null;
                                    })
                                    ->openUrlInNewTab()
                                    ->visible(function ($record) {
                                        return !empty($record->user->paktaIntegritas?->file);
                                    })
                            ),
                    ]),
                    PdfViewerField::make('file')
                        ->label('File Persetujuan Izin')
                        // ->minHeight('40svh')
                        ->fileUrl(function ($record) {
                            $IzinPersetujuan = Izin::where('user_id', $record->user_id)
                                ->where('tanggal_pengajuan', '<=', $record->tanggal_pengajuan)
                                ->whereHas('izinPersetujuan', function ($query) {
                                    $query->whereNotNull('file_persetujuan')
                                        ->where('file_persetujuan', '!=', '');
                                })
                                ->with([
                                    'izinPersetujuan' => function ($query) {
                                        $query->select('id', 'id_izin', 'file_persetujuan', 'status', 'date_updated');
                                    }
                                ])
                                ->orderBy('tanggal_pengajuan', 'desc')
                                ->first();

                            return Storage::disk('public')->url($IzinPersetujuan?->izinPersetujuan?->file_persetujuan ?? '');
                        })
                ])
                ->collapsible()
                ->collapsed(),

            // Documents Section
            Section::make('Dokumen Pengajuan')
                ->schema($this->getDocumentViewFields())
                ->collapsible()
                ->collapsed(),

            // Step-by-step Verification dengan conditional disabled
            Tabs::make('Tahapan Verifikasi')
                ->schema($this->getDocumentViewFields())
                ->extraAttributes(['class' => 'flex-wrap'])
                ->activeTab(function ($record) {
                    // Aktifkan tab sesuai status terakhir
                    if (!$record->icvCekDokumen || !$record->icvCekDokumen->hasil_verif) {
                        return 1;
                    }
                    if ($record->icvCekDokumen->hasil_verif == '2') {
                        return 1;
                    }
                    if (!$record->icvAlokasi || !$record->icvAlokasi->icv_disetujui) {
                        return 2;
                    }
                    if (!$record->icvBilling || !$record->icvBilling->kode_billing) {
                        return 3;
                    }
                    if (!$record->icvBuktiBayar || !$record->icvBuktiBayar->file_bukti_bayar) {
                        return 4;
                    }
                    if (!$record->icvJadwal || !$record->icvJadwal->jadwal_pengambilan) {
                        return 5;
                    }
                    if ($record->icvSbbk?->sinkarkes !== 'Sudah') {
                        return 6;
                    }
                    return 1;
                })
                ->tabs([
                    // Tab 1: Document Verification - Always enabled
                    Tabs\Tab::make('1_verifikasi_dokumen')
                        ->label('1. Verifikasi Dokumen')
                        ->icon('heroicon-o-document-check')
                        ->badge(function ($record) {
                            if (!$record->icvCekDokumen?->hasil_verif)
                                return '';
                            return $record->icvCekDokumen->hasil_verif == '1' ? '✓' : '✗';
                        })
                        ->badgeColor(function ($record) {
                            if (!$record->icvCekDokumen?->hasil_verif)
                                return 'gray';
                            return $record->icvCekDokumen->hasil_verif == '1' ? 'success' : 'danger';
                        })
                        ->schema([
                            Select::make('hasil_verif')
                                ->label('Hasil Verifikasi')
                                ->options([
                                    '1' => 'Berkas Lengkap',
                                    '2' => 'Berkas Tidak Lengkap',
                                ])
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    if ($get('hasil_verif') === '2') {
                                        $set('stok_vaksin', 0);
                                    }
                                    if ($get('hasil_verif') === '1') {
                                        $set('catatan_verif', null); // <-- Tambahkan baris ini
                                    }
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('stok_vaksin')
                                    ->label('Stok Vaksin Tersedia')
                                    ->numeric()
                                    ->minValue(0)
                                    ->visible(fn(Get $get): bool => $get('hasil_verif') === '1')
                                    ->required(fn(Get $get): bool => $get('hasil_verif') === '1'),

                                TextInput::make('date_verif')
                                    ->label('Tanggal Verifikasi')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record->icvCekDokumen?->date_updated?->translatedFormat('d F Y H:i'))
                                    ->dehydrated(false),
                            ]),

                            Section::make('Riwayat Catatan Perbaikan')
                                ->collapsible()
                                ->visible(fn($record) => !empty($record->riwayat_perbaikan))
                                ->schema(function ($record) {
                                    $schema = [];
                                    foreach (($record->riwayat_perbaikan ?? []) as $index => $item) {
                                        $schema[] = Placeholder::make("riwayat_{$index}")
                                            ->label($item['tanggal'] ?? '-')
                                            ->content($item['catatan'] ?? 'Tidak ada catatan');
                                    }
                                    return $schema;
                                }),

                            Textarea::make('catatan_verif')
                                ->label('Catatan Perbaikan')
                                ->rows(3)
                                ->visible(fn(Get $get): bool => $get('hasil_verif') === '2')
                                ->required(fn(Get $get): bool => $get('hasil_verif') === '2'),

                            DatePicker::make('tanggal_perbaikan')
                                ->label('Tanggal Perbaikan')
                                ->native(false)
                                ->displayFormat('d F Y H:i')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn($state) => $state ?? false),

                            Actions::make([
                                FormAction::make('verifikasi_dokumen')
                                    ->label('Simpan')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $hasil = $get('hasil_verif');
                                        $catatan = $get('catatan_verif');

                                        // Simpan hasil verifikasi dokumen
                                        $record->icvCekDokumen()->updateOrCreate([
                                            'id_icv' => $record->id,
                                        ], [
                                            'hasil_verif' => $hasil,
                                            'catatan' => $catatan ?? '',
                                            'stok_vaksin' => $get('stok_vaksin') ?? 0,
                                            'jumlah_icv' => $record->jumlah_icv,
                                            'status' => 1,
                                            'date_updated' => now(),
                                        ]);

                                        // Simpan riwayat perbaikan jika hasil = 2 (tidak lengkap) dan ada catatan
                                        if ($hasil === '2' && $catatan) {
                                            $record->tambahCatatanPerbaikan($catatan);
                                            // $record->save();
                                        }

                                        // Refresh record dengan eager loading sebelum kirim email
                                        $record = $record->fresh(['user', 'icvCekDokumen']);

                                        // Kirim email notifikasi ke user
                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IcvVerifikasiNotification(
                                                        $record,
                                                        $get('hasil_verif'),
                                                        $get('catatan_verif') ?? null
                                                    )
                                                );
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Gagal mengirim email verifikasi ICV: ' . $e->getMessage());
                                        }

                                        Notification::make()
                                            ->title('Verifikasi Berhasil Disimpan')
                                            ->icon('heroicon-o-check-circle')
                                            ->success()
                                            ->body('Verifikasi dokumen berhasil disimpan dan email notifikasi telah dikirim.')
                                            ->send();

                                        $this->redirect(VerifikasiIcv::getUrl());
                                    })

                            ])
                        ]),

                    // Tab 2: ICV Allocation - Disabled jika verifikasi belum selesai atau tidak lengkap
                    Tabs\Tab::make('2_alokasi_icv')
                        ->disabled(function ($record) {
                            // Disable if document verification is not complete or not approved
                            return !$record->icvCekDokumen ||
                                !$record->icvCekDokumen->hasil_verif ||
                                $record->icvCekDokumen->hasil_verif !== '1';
                        })
                        ->label('2. Alokasi ICV')
                        ->icon('heroicon-o-squares-plus')
                        ->badge(function ($record) {
                            return $record->icvAlokasi?->icv_disetujui ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvAlokasi?->icv_disetujui ? 'success' : 'gray';
                        })
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('stok_tersedia_info')
                                    ->label('Stok Tersedia')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => number_format($record->icvCekDokumen?->stok_vaksin ?? 0))
                                    ->dehydrated(false),

                                TextInput::make('jumlah_diminta_info')
                                    ->label('Jumlah Diminta')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => number_format($record->jumlah_icv))
                                    ->dehydrated(false),

                                TextInput::make('icv_disetujui')
                                    ->label('ICV yang Disetujui')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Masukkan jumlah ICV yang akan dialokasikan'),

                            ]),

                            TextInput::make('date_alokasi')
                                ->label('Tanggal Alokasi')
                                ->disabled()
                                ->formatStateUsing(fn($record) => $record->icvAlokasi?->date_updated?->translatedFormat('d F Y H:i') ?? now()->translatedFormat('d F Y H:i'))
                                ->dehydrated(false),

                            Actions::make([
                                Actions\Action::make('alokasi_icv')
                                    ->label('Alokasikan ICV')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $icvDisetujui = $get('icv_disetujui');
                                        if ($icvDisetujui <= 0) {
                                            Notification::make()
                                                ->title('Gagal Alokasikan ICV')
                                                ->danger()
                                                ->body('Jumlah ICV yang disetujui harus lebih dari 0.')
                                                ->send();
                                            return;
                                        }

                                        // Update or create alokasi
                                        $record->icvAlokasi()->updateOrCreate([
                                            'id_icv' => $record->id,
                                        ], [
                                            'icv_disetujui' => $icvDisetujui,
                                            'status' => 1,
                                            'date_updated' => now(),
                                        ]);

                                        $record = $record->fresh(['user', 'icvAlokasi']);


                                        // Kirim email notifikasi alokasi
                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IcvAlokasiNotification($record, $icvDisetujui)
                                                );
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Gagal mengirim email alokasi ICV: ' . $e->getMessage());
                                        }

                                        Notification::make()
                                            ->title('ICV Berhasil Dialokasikan')
                                            ->success()
                                            ->body("ICV berhasil dialokasikan sebanyak {$icvDisetujui} unit. Email notifikasi telah dikirim.")
                                            ->send();

                                        // Refresh page to reflect changes
                                        $this->redirect(VerifikasiIcv::getUrl());

                                    }),
                            ])
                        ]),

                    // Tab 3: Billing - Disabled jika alokasi belum selesai
                    Tabs\Tab::make('3_penerbitan_billing')
                        ->label('3. Penerbitan Billing')
                        ->icon('heroicon-o-banknotes')
                        ->disabled(function ($record) {
                            return !$record->icvAlokasi ||
                                !$record->icvAlokasi->icv_disetujui ||
                                $record->icvAlokasi->icv_disetujui <= 0;
                        })
                        ->badge(function ($record) {
                            return $record->icvBilling?->kode_billing ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvBilling?->kode_billing ? 'success' : 'gray';
                        })
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('kode_billing')
                                    ->label('Kode Billing')
                                    ->numeric()
                                    ->required(fn(Get $get): bool => (bool) $get('icv_disetujui'))
                                    ->helperText('Masukkan kode billing unik'),

                                TextInput::make('nominal')
                                    ->label('Nominal Pembayaran')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(fn(Get $get): bool => (bool) $get('icv_disetujui'))
                                    ->helperText('Nominal yang harus dibayar'),
                            ]),

                            TextInput::make('date_billing')
                                ->label('Tanggal Penerbitan')
                                ->disabled()
                                ->formatStateUsing(fn($record) => $record->icvBilling?->date_updated?->translatedFormat('d F Y H:i') ?? now()->translatedFormat('d F Y H:i'))
                                ->dehydrated(false),

                            Actions::make([
                                Actions\Action::make('terbitkan_billing')
                                    ->label('Terbitkan Billing')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $kodeBilling = $get('kode_billing');
                                        $nominal = $get('nominal');

                                        if (!$kodeBilling || !$nominal) {
                                            Notification::make()
                                                ->title('Gagal Terbitkan Billing')
                                                ->danger()
                                                ->body('Kode billing dan nominal harus diisi.')
                                                ->send();
                                            return;
                                        }

                                        // Update or create billing
                                        $record->icvBilling()->updateOrCreate([
                                            'id_icv' => $record->id,
                                        ], [
                                            'kode_billing' => $kodeBilling,
                                            'nominal' => $nominal,
                                            'status' => 1,
                                            'bayar' => 0,
                                            'date_updated' => now(),
                                        ]);

                                        $record = $record->fresh(['user', 'icvBilling']);

                                        // Kirim email notifikasi billing
                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IcvBillingNotification($record, $kodeBilling, $nominal)
                                                );
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Gagal mengirim email billing ICV: ' . $e->getMessage());
                                        }

                                        Notification::make()
                                            ->title('Billing Berhasil Diterbitkan')
                                            ->success()
                                            ->body("Kode billing {$kodeBilling} berhasil diterbitkan. Email notifikasi telah dikirim.")
                                            ->send();

                                        $this->redirect(VerifikasiIcv::getUrl());



                                    }),
                            ]),
                        ]),

                    // Tab 4: Payment Verification - Disabled jika billing belum ada dan bukti bayar belum upload
                    Tabs\Tab::make('4_verifikasi_pembayaran')
                        ->label('4. Verifikasi Pembayaran')
                        ->icon('heroicon-o-credit-card')
                        ->disabled(function ($record) {
                            return !$record->icvBilling ||
                                !$record->icvBilling->kode_billing ||
                                !$record->icvBuktiBayar ||
                                !$record->icvBuktiBayar->file_bukti_bayar;
                        })
                        ->badge(function ($record) {
                            return $record->icvBuktiBayar ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvBuktiBayar ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('payment_disabled_notice')
                                ->label('Perhatian')
                                ->content(function ($record) {
                                    if (!$record->icvBuktiBayar || !$record->icvBuktiBayar->file_bukti_bayar) {
                                        return '⚠️ Pemohon belum upload bukti bayar.';
                                    }
                                    return '';
                                })
                                ->visible(function ($record) {
                                    return !$record->icvBilling ||
                                        !$record->icvBilling->kode_billing ||
                                        !$record->icvBuktiBayar ||
                                        !$record->icvBuktiBayar->file_bukti_bayar;
                                }),

                            TextInput::make('file_bukti_bayar_info')
                                ->label('File Bukti Bayar')
                                ->disabled()
                                ->formatStateUsing(function ($record) {
                                    return $record->icvBuktiBayar?->file_bukti_bayar ?
                                        basename($record->icvBuktiBayar->file_bukti_bayar) : 'Belum ada file';
                                })
                                ->hintAction(
                                    FormAction::make('download_bukti')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->label(fn($record) => $record->icvBuktiBayar?->file_bukti_bayar ?
                                            'Unduh ' . basename($record->icvBuktiBayar->file_bukti_bayar) : 'Tidak ada file')
                                        ->url(fn($record) => $record->icvBuktiBayar?->file_bukti_bayar ?
                                            asset('storage/' . $record->icvBuktiBayar->file_bukti_bayar) : '#')
                                        ->openUrlInNewTab()
                                        ->visible(fn($record) => $record->icvBuktiBayar?->file_bukti_bayar !== null)
                                )
                                ->dehydrated(false),

                            Grid::make(2)->schema([
                                TextInput::make('info_billing')
                                    ->label('Kode Billing')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record->icvBilling?->kode_billing ?? '-')
                                    ->dehydrated(false),

                                TextInput::make('info_nominal')
                                    ->label('Nominal')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => 'Rp ' . number_format($record->icvBilling?->nominal ?? 0, 0, ',', '.'))
                                    ->dehydrated(false),
                            ]),

                            TextInput::make('date_bayar')
                                ->label('Tanggal Pembayaran')
                                ->disabled()
                                ->formatStateUsing(fn($record) => $record->icvJadwal?->jadwal_pengambilan->translatedFormat('d F Y') ?? 'Belum ada tanggal')
                                ->dehydrated(false),

                            Textarea::make('catatan_bayar')
                                ->label('Catatan')
                                ->rows(2),
                        ]),

                    // Tab 5: Distribution Schedule - Disabled jika pembayaran belum diverifikasi
                    Tabs\Tab::make('5_jadwal_distribusi')
                        ->label('5. Jadwal Distribusi')
                        ->icon('heroicon-o-calendar-days')
                        ->disabled(function ($record) {
                            return !$record->icvBuktiBayar ||
                                !$record->icvBuktiBayar->file_bukti_bayar;
                            ;
                        })
                        ->badge(function ($record) {
                            return $record->icvJadwal?->jadwal_pengambilan ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvJadwal?->jadwal_pengambilan ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('schedule_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah pembayaran diverifikasi dan metode distribusi ditentukan.')
                                ->visible(function ($record) {
                                    return !$record->icvBuktiBayar || !$record->icvBuktiBayar->file_bukti_bayar;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('jumlah_icv_distribusi')
                                    ->label('Jumlah ICV Disetujui')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => number_format($record->icvAlokasi?->icv_disetujui ?? 0))
                                    ->dehydrated(false),

                                Placeholder::make('distribusi_info')
                                    ->label('Distribusi ICV')
                                    ->content("Distribusi e-ICV melalui aplikasi Sinkarkes")
                            ]),

                            \Filament\Forms\Components\DatePicker::make('jadwal_pengambilan')
                                ->label('Jadwal Pengambilan/Pengiriman')
                                ->minDate(now()->addDay())
                                ->native(false)
                                ->displayFormat('d F Y')
                                ->helperText('Pilih tanggal pengambilan atau pengiriman ICV'),

                            Actions::make([
                                Actions\Action::make('set_jadwal_distribusi')
                                    ->label('Tetapkan Jadwal')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $jadwalPengambilan = $get('jadwal_pengambilan');
                                        if (!$jadwalPengambilan) {
                                            Notification::make()
                                                ->title('Gagal Tetapkan Jadwal')
                                                ->danger()
                                                ->body('Jadwal pengambilan harus diisi.')
                                                ->send();
                                            return;
                                        }

                                        // Update or create jadwal
                                        $record->icvJadwal()->updateOrCreate([
                                            'id_icv' => $record->id,
                                        ], [
                                            'jadwal_pengambilan' => $jadwalPengambilan,
                                            'status' => 1,
                                            'date_updated' => now(),
                                        ]);

                                        $tanggalText = \Carbon\Carbon::parse($jadwalPengambilan)->translatedFormat('d F Y');
                                        Notification::make()
                                            ->title('Jadwal Distribusi Berhasil Ditetapkan')
                                            ->success()
                                            ->body("Jadwal distribusi berhasil ditetapkan pada {$tanggalText}.")
                                            ->send();

                                        $this->redirect(VerifikasiIcv::getUrl());
                                    }),
                            ]),
                        ]),

                    // Tab 6: Update Sinkarkes - Disabled jika jadwal belum ditentukan
                    Tabs\Tab::make('6_update_sinkarkes')
                        ->label('6. Update Sinkarkes')
                        ->icon('heroicon-o-pencil-square')
                        ->disabled(function ($record) {
                            return !$record->icvJadwal ||
                                !$record->icvJadwal->jadwal_pengambilan;
                        })
                        ->badge(function ($record) {
                            return $record->icvSbbk?->sinkarkes === 'Sudah' ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->icvSbbk?->sinkarkes === 'Sudah' ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('sinkarkes_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah jadwal distribusi ditetapkan.')
                                ->visible(function ($record) {
                                    return !$record->icvJadwal || !$record->icvJadwal->jadwal_pengambilan;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('jadwal_info')
                                    ->label('Jadwal Distribusi')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record->icvJadwal?->jadwal_pengambilan?->translatedFormat('d F Y') ?? 'Belum dijadwalkan')
                                    ->dehydrated(false),

                                Placeholder::make('distribusi_info')
                                    ->label('Distribusi ICV')
                                    ->content("Distribusi e-ICV melalui aplikasi Sinkarkes")

                            ]),


                            Select::make('sinkarkes')
                                ->label('Status Sinkarkes')
                                ->options([
                                    'Sudah' => 'Sudah',
                                    'Belum' => 'Belum',
                                ])
                                ->helperText('Update status input data ke sistem Sinkarkes'),

                            Actions::make([
                                FormAction::make('submit_sinkarkes')
                                    ->label('Update Sinkarkes')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $sinkarkes = $get('sinkarkes');
                                        if (!$sinkarkes) {
                                            Notification::make()
                                                ->title('Gagal Update Sinkarkes')
                                                ->danger()
                                                ->body('Status Sinkarkes harus dipilih.')
                                                ->send();
                                            return;
                                        }

                                        // Update SBBK record
                                        $record->icvSbbk()->updateOrCreate([
                                            'id_icv' => $record->id,
                                        ], [
                                            'sinkarkes' => $sinkarkes,
                                            'date_updated' => now(),
                                        ]);

                                        Notification::make()
                                            ->title('Status Sinkarkes Berhasil Diupdate')
                                            ->success()
                                            ->body("Status Sinkarkes berhasil diupdate ke {$sinkarkes}.")
                                            ->send();

                                        $this->redirect(VerifikasiIcv::getUrl());
                                    }),
                            ]),
                        ]),

                    // Tab 6 sudah diubah menjadi Update Sinkarkes
                ])
        ];
    }

    private static function getVerificationForms(): array
    {
        return [
            Section::make('Detail ICV')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('tanggal_pengajuan')
                                ->label('Tanggal Pengajuan')
                                ->readOnly()
                                ->formatStateUsing(fn($record) => $record->tanggal_pengajuan?->translatedFormat('d F Y'))
                                ->dehydrated(false),

                            TextInput::make('pemohon')
                                ->label('Pemohon')
                                ->readOnly()
                                ->formatStateUsing(fn($record) => $record->user->name)
                                ->dehydrated(false),

                            TextInput::make('jumlah_icv_pengajuan')
                                ->label('Jumlah ICV Pengajuan')
                                ->readOnly()
                                ->formatStateUsing(fn($record) => $record->jumlah_icv)
                                ->dehydrated(false),
                        ]),
                ])
                ->collapsible(),

            Section::make('Berkas yang Diupload')
                ->schema(self::getDocumentFields())
                ->collapsible(),

            Section::make('Verifikasi ICV')
                ->schema([
                    Select::make('hasil_verif')
                        ->label('Hasil Verifikasi')
                        ->options([
                            '1' => 'Berkas Lengkap',
                            '2' => 'Berkas Tidak Lengkap',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            if ($get('hasil_verif') === '2') {
                                $set('stok_vaksin', 0);
                                $set('jumlah_icv', 0);
                            }
                        }),

                    TextInput::make('stok_vaksin')
                        ->label('Stok Vaksin')
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn(Get $get): bool => $get('hasil_verif') === '1')
                        ->required(fn(Get $get): bool => $get('hasil_verif') === '1'),

                    // TextInput::make('jumlah_icv')
                    //     ->label('Jumlah ICV yang Disetujui')
                    //     ->numeric()
                    //     ->minValue(0)
                    //     ->visible(fn(Get $get): bool => $get('hasil_verif') === '1')
                    //     ->required(fn(Get $get): bool => $get('hasil_verif') === '1'),

                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(4)
                        ->visible(fn(Get $get): bool => $get('hasil_verif') === '2')
                        ->required(fn(Get $get): bool => $get('hasil_verif') === '2'),

                    TextInput::make('date_updated')
                        ->label('Tanggal Verifikasi')
                        ->readOnly()
                        ->dehydrated(false),
                ])
                ->columns(1),
        ];
    }

    private static function getDocumentFields(): array
    {

        for ($i = 1; $i <= 7; $i++) {
            $fileField = "file{$i}";
            $fields[] = TextInput::make($fileField)
                ->label(fn($record) => $record->getFileLabels()[$fileField] ?? "File {$i}")
                ->readOnly()
                ->prefix($i)
                ->formatStateUsing(function ($record) use ($fileField) {
                    if ($record->$fileField) {
                        return substr($record->$fileField, strrpos($record->$fileField, '/') + 1);
                    } else {
                        return 'Tidak ada file';
                    }
                })
                ->hintAction(
                    FormAction::make("download_{$fileField}")
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label(fn($record) => "Unduh " . self::getIcvLabel($fileField))
                        ->url(fn($record) => $record->$fileField ? asset('storage/' . $record->$fileField) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record->$fileField !== null)
                )
                ->dehydrated(false);
        }

        return $fields;
    }

    // Tambahkan method untuk menampilkan riwayat perbaikan
    private function getRiwayatPerbaikanSchema(array $riwayat): array
    {
        $schema = [];
        foreach ($riwayat as $index => $item) {
            $schema[] = Placeholder::make("riwayat_{$index}")
                ->label($item['tanggal'] ?? '-')
                ->content($item['catatan'] ?? 'Tidak ada catatan');
        }
        return $schema;
    }
}
