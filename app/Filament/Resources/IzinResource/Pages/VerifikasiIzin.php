<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Models\IzinPersetujuan;
use App\Models\SuratPersetujuanIcv;
use Dom\Text;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\Page;
use App\Models\Izin;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Actions\Action as PageAction;
use App\Models\IzinDokumen;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Actions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\IzinVerifikasiNotification;
use App\Mail\IzinVisitasiNotification;
use App\Mail\IzinPersetujuanNotification;
use App\Mail\IzinPksNotification;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\FileLabels;

class VerifikasiIzin extends Page implements HasTable
{
    use InteractsWithTable, FileLabels;
    protected static string $resource = IzinResource::class;

    protected static string $model = Izin::class;

    protected static string $view = 'filament.resources.izin-resource.pages.verifikasi-izin';

    protected static ?string $title = 'Verifikasi Izin';

    protected static ?string $navigationLabel = 'Verifikasi';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['admin'])) {
            // abort(403, 'Akses ditolak. Hanya admin yang dapat mengelola verifikasi izin.');
            redirect()->to('/');

        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Izin::query()
                    ->with([
                        'user',
                        'izinDokumen',
                        'izinVisitasi',
                        'izinPersetujuan',
                        'izinDokumenPks'
                    ])
            )
            ->columns([
                // Tanggal Pengajuan - Sortable & Searchable
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
                        return $query->join('users', 'tbl_izin.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction);
                    }),

                // Kategori Izin - Sortable & Searchable
                TextColumn::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->badge()
                    ->color(
                        fn($state) => match ($state) {
                            "Izin Baru" => "success",
                            "Perpanjang Izin" => "warning",
                            default => "gray",
                        }
                    )
                    ->searchable()
                    ->sortable(),

                // Progress Overview
                TextColumn::make('progress_overview')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $steps = [
                            'Verif' => $record->izinDokumen?->hasil == 1 ? 1 : 0,
                            'Visitasi' => $record->izinDokumenPks?->tanggal_visitasi ? 1 : 0,
                            'Persetujuan' => $record->izinPersetujuan?->file_persetujuan ? 1 : 0,
                            'PKS' => $record->izinDokumenPks?->dokumen_pks_bkk ? 1 : 0,
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
                            $percentage >= 75 => 'warning',
                            $percentage >= 50 => 'info',
                            default => 'gray',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Custom sorting based on the completion of steps
                        return $query
                            ->leftJoin('tbl_izin_dokumen', 'tbl_izin.id', '=', 'tbl_izin_dokumen.izin_id')
                            ->leftJoin('tbl_izin_dokumen_pks', 'tbl_izin.id', '=', 'tbl_izin_dokumen_pks.id_izin')
                            ->leftJoin('tbl_izin_persetujuan', 'tbl_izin.id', '=', 'tbl_izin_persetujuan.id_izin')
                            ->select('tbl_izin.*')
                            ->selectRaw('
                                (CASE WHEN tbl_izin_dokumen.hasil = 1 THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_izin_dokumen_pks.tanggal_visitasi IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_izin_persetujuan.file_persetujuan IS NOT NULL THEN 1 ELSE 0 END) +
                                (CASE WHEN tbl_izin_dokumen_pks.dokumen_pks_bkk IS NOT NULL THEN 1 ELSE 0 END) as progress_score
                            ')
                            ->orderBy('progress_score', $direction);
                    }),

                // Current Step
                TextColumn::make('current_step')
                    ->label('Tahap Saat Ini')
                    ->suffix(function ($record) {
                        // Jika ada tanggal_perbaikan dan (belum ada izinDokumen atau hasil terakhir = 2), tampilkan (Perbaikan)
                        if ($record->tanggal_perbaikan) {
                            if (
                                !$record->izinDokumen
                            ) {
                                return ' (Perbaikan)';
                            }
                        }
                        return '';
                    })
                    ->getStateUsing(function ($record) {
                        return $this->getCurrentStep($record);
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        return $this->getCurrentStepColor($record);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        // Sort based on the step priority
                        return $query
                            ->leftJoin('tbl_izin_dokumen', 'tbl_izin.id', '=', 'tbl_izin_dokumen.izin_id')
                            ->leftJoin('tbl_izin_dokumen_pks', 'tbl_izin.id', '=', 'tbl_izin_dokumen_pks.id_izin')
                            ->leftJoin('tbl_izin_persetujuan', 'tbl_izin.id', '=', 'tbl_izin_persetujuan.id_izin')
                            ->select('tbl_izin.*')
                            ->selectRaw('
                                CASE
                                    WHEN tbl_izin_dokumen.hasil IS NULL OR tbl_izin_dokumen.hasil = 0 THEN 1
                                    WHEN tbl_izin_dokumen.hasil = 2 THEN 2
                                    WHEN tbl_izin_dokumen_pks.tanggal_visitasi IS NULL THEN 3
                                    WHEN tbl_izin_persetujuan.id_izin IS NULL THEN 4
                                    WHEN tbl_izin_dokumen_pks.dokumen_pks_bkk IS NULL THEN 5
                                    ELSE 6
                                END as step_order
                            ')
                            ->orderBy('step_order', $direction);
                    }),

                // Detail Status (toggleable)
                TextColumn::make('detail_status')
                    ->label('Detail Status')
                    ->getStateUsing(function ($record) {
                        $details = [];

                        if ($record->izinDokumen && $record->izinDokumen->hasil) {
                            $hasil = match ($record->izinDokumen->hasil) {
                                1 => 'Lengkap',
                                2 => 'Tidak Lengkap',
                                default => 'Pending'
                            };
                            $details[] = "Verifikasi: " . $hasil;
                        }

                        if ($record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi) {
                            $details[] = "Visitasi: " . Carbon::parse($record->izinDokumenPks->tanggal_visitasi)->translatedFormat('d M');
                        }

                        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
                            $details[] = "Persetujuan: " . Carbon::parse($record->izinPersetujuan->date_updated)->translatedFormat('d M');
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
                        return Izin::join('users', 'tbl_izin.user_id', '=', 'users.id')
                            ->distinct()
                            ->whereNotNull('users.kotakab')
                            ->orderBy('users.kotakab')
                            ->pluck('users.kotakab', 'users.kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

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
                SelectFilter::make('progress_status')
                    ->label('Status Progress')
                    ->options([
                        'verifikasi_dokumen' => 'Verifikasi Dokumen',
                        'perbaikan' => 'Perbaikan', // Tambahkan opsi ini
                        'perbaikan_dokumen' => 'Perbaikan Dokumen',
                        'jadwal_visitasi' => 'Jadwal Visitasi',
                        'proses_persetujuan' => 'Proses Persetujuan',
                        'upload_pks' => 'Upload PKS',
                        'selesai' => 'Selesai',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        $progressStatus = $data['value'];

                        switch ($progressStatus) {
                            case 'verifikasi_dokumen':
                                return $query->where(function ($q) {
                                    $q->whereDoesntHave('izinDokumen')
                                        ->orWhereHas('izinDokumen', function ($subQ) {
                                            $subQ->whereNull('hasil')->orWhere('hasil', 0);
                                        });
                                });

                            case 'perbaikan':
                                // Filter: ada tanggal_perbaikan dan (belum ada izinDokumen atau hasil terakhir = 2)
                                return $query->whereNotNull('tanggal_perbaikan')
                                    ->where(function ($q) {
                                        $q->whereDoesntHave('izinDokumen')
                                          ->orWhereHas('izinDokumen', fn($q2) => $q2->where('hasil', 2));
                                    });

                            case 'perbaikan_dokumen':
                                return $query->whereHas('izinDokumen', function ($q) {
                                    $q->where('hasil', 2);
                                });

                            case 'jadwal_visitasi':
                                return $query->whereHas('izinDokumen', function ($q) {
                                    $q->where('hasil', 1);
                                })->where(function ($q) {
                                    $q->whereDoesntHave('izinDokumenPks')
                                        ->orWhereHas('izinDokumenPks', function ($subQ) {
                                            $subQ->whereNull('tanggal_visitasi');
                                        });
                                });

                            case 'proses_persetujuan':
                                return $query->whereHas('izinDokumenPks', function ($q) {
                                    $q->whereNotNull('tanggal_visitasi');
                                })->whereDoesntHave('izinPersetujuan');

                            case 'upload_pks':
                                return $query->whereHas('izinPersetujuan')
                                    ->where(function ($q) {
                                        $q->whereDoesntHave('izinDokumenPks', function ($subQ) {
                                            $subQ->whereNotNull('dokumen_pks_bkk');
                                        })
                                            ->orWhereHas('izinDokumenPks', function ($subQ) {
                                                $subQ->whereNull('dokumen_pks_bkk');
                                            });
                                    });

                            case 'selesai':
                                return $query->whereHas('izinDokumenPks', function ($q) {
                                    $q->whereNotNull('dokumen_pks_bkk');
                                });

                            default:
                                return $query;
                        }
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
                            'lengkap' => $query->whereHas('izinDokumen', fn($q) => $q->where('hasil', 1)),
                            'tidak_lengkap' => $query->whereHas('izinDokumen', fn($q) => $q->where('hasil', 2)),
                            'belum_verifikasi' => $query->whereDoesntHave('izinDokumen')
                                ->orWhereHas('izinDokumen', fn($q) => $q->where('hasil', 0)),
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
                    ->modalHeading(fn($record) => 'Detail Verifikasi Izin - ' . $record->user->name)
                    ->modalWidth('8xl')
                    ->modalSubmitAction(false)
                    ->form($this->getDetailVerificationForm())
                    ->fillForm(function ($record) {
                        return [
                            // Basic info
                            'nama_pemohon' => $record->user->name,
                            'tanggal_pengajuan' => $record->tanggal_pengajuan->translatedFormat('d F Y'),
                            'kategori_izin' => $record->kategori_izin,
                            'nomor_surat' => $record->nomor_surat,
                            'tanggal_surat' => $record->tanggal_surat->translatedFormat('d F Y'),

                            // Step 1: Verifikasi Dokumen
                            'hasil' => $record->izinDokumen?->hasil ?? 0,
                            'catatan' => $record->izinDokumen?->catatan ?? '',
                            'tanggal_perbaikan' => $record->tanggal_perbaikan ? $record->tanggal_perbaikan : null,

                            // Step 2: Visitasi
                            'tanggal_visitasi' => $record->izinDokumenPks?->tanggal_visitasi,

                            // Step 3: Persetujuan
                            'tanggal_persetujuan' => $record->izinPersetujuan?->date_updated ?? now(),
                            'file_persetujuan' => $record->izinPersetujuan?->file_persetujuan ?? null,

                            // Step 4: PKS
                            'dokumen_pks_bkk' => $record->izinDokumenPks?->dokumen_pks_bkk ?? null,
                        ];
                    })
                    ->action(function (array $data, $record) {
                        $this->processVerificationSteps($data, $record);
                    })
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->striped()
            ->paginated([15, 30, 50]);
    }

    private function getActiveTab($record): int
    {
        if (!$record->izinDokumen || !$record->izinDokumen->hasil) {
            return 1;
        }
        if ($record->izinDokumen->hasil == 2) {
            return 1;
        }
        if (!$record->izinDokumenPks || !$record->izinDokumenPks->tanggal_visitasi) {
            return 2;
        }
        if (!$record->izinPersetujuan) {
            return 3;
        }
        if (!$record->izinDokumenPks || !$record->izinDokumenPks->dokumen_pks_bkk) {
            return 4;
        }
        return 1;
    }

    private function getCurrentStep($record): string
    {
        if (!$record->izinDokumen || !$record->izinDokumen->hasil) {
            return 'Verifikasi Dokumen';
        }
        if ($record->izinDokumen->hasil == 2) {
            return 'Perbaikan Dokumen';
        }
        if (!$record->izinDokumenPks || !$record->izinDokumenPks->tanggal_visitasi) {
            return 'Jadwal Visitasi';
        }
        if (!$record->izinPersetujuan) {
            return 'Proses Persetujuan';
        }
        if (!$record->izinDokumenPks || !$record->izinDokumenPks->dokumen_pks_bkk) {
            return 'Upload PKS';
        }
        return 'Selesai';
    }

    private function getCurrentStepColor($record): string
    {
        $state = $this->getCurrentStep($record);

        return match ($state) {
            'Selesai' => 'success',
            'Perbaikan Dokumen' => 'danger',
            'Jadwal Visitasi' => 'warning',
            default => 'info'
        };
    }

    private function processVerificationSteps(array $data, $record): void
    {
        $notifications = [];

        // Step 1: Verifikasi Dokumen
        if (isset($data['hasil'])) {
            $record->izinDokumen()->updateOrCreate(
                ['izin_id' => $record->id],
                [
                    'hasil' => (int) $data['hasil'],
                    'catatan' => $data['catatan'] ?? '',
                    'status' => (int) $data['hasil'],
                    'date_updated' => now(),
                ]
            );

            try {
                if ($record->user && $record->user->email) {
                    Mail::to($record->user->email)->send(
                        new IzinVerifikasiNotification(
                            $record,
                            (int) $data['hasil'],
                            $data['catatan'] ?? null
                        )
                    );
                    $notifications[] = 'Dokumen berhasil diverifikasi, email notifikasi terkirim';
                } else {
                    $notifications[] = 'Dokumen berhasil diverifikasi, email tidak terkirim (user tidak memiliki email)';
                }
            } catch (\Exception $e) {
                error_log('Failed to send verification email: ' . $e->getMessage());
                $notifications[] = 'Dokumen berhasil diverifikasi, email gagal terkirim';
            }
        }

        // Step 2: Jadwal Visitasi
        if (isset($data['tanggal_visitasi']) && $data['tanggal_visitasi']) {
            $record->izinDokumenPks()->updateOrCreate(
                ['id_izin' => $record->id],
                ['tanggal_visitasi' => $data['tanggal_visitasi']]
            );

            // Refresh the record to get the updated relationship
            $record->refresh();
            $record->load('izinDokumenPks');

            try {
                if ($record->user && $record->user->email) {
                    Mail::to($record->user->email)->send(
                        new IzinVisitasiNotification($record)
                    );
                    $notifications[] = 'Visitasi berhasil dijadwalkan, email notifikasi terkirim';
                } else {
                    $notifications[] = 'Visitasi berhasil dijadwalkan, email tidak terkirim (user tidak memiliki email)';
                }
            } catch (\Exception $e) {
                error_log('Failed to send visitasi email: ' . $e->getMessage());
                $notifications[] = 'Visitasi berhasil dijadwalkan, email gagal terkirim';
            }
        }

        // Step 3: Persetujuan
        if (isset($data['file_persetujuan']) && $data['file_persetujuan']) {
            $record->izinPersetujuan()->updateOrCreate(
                ['id_izin' => $record->id],
                [
                    'date_updated' => $data['tanggal_persetujuan'] ?? now(),
                    'file_persetujuan' => $data['file_persetujuan'],
                    'status' => 1,
                ]
            );

            // Refresh the record to get the updated relationships
            $record->refresh();
            $record->load(['izinPersetujuan', 'izinDokumen', 'izinDokumenPks']);

            try {
                if ($record->user && $record->user->email) {
                    Mail::to($record->user->email)->send(
                        new IzinPersetujuanNotification($record)
                    );
                    $notifications[] = 'Persetujuan berhasil diterbitkan, email notifikasi terkirim';
                } else {
                    $notifications[] = 'Persetujuan berhasil diterbitkan, email tidak terkirim (user tidak memiliki email)';
                }
            } catch (\Exception $e) {
                error_log('Failed to send persetujuan email: ' . $e->getMessage());
                $notifications[] = 'Persetujuan berhasil diterbitkan, email gagal terkirim';
            }
        }

        // Step 4: PKS
        if (isset($data['dokumen_pks_bkk']) && $data['dokumen_pks_bkk']) {
            $record->izinDokumenPks()->updateOrCreate(
                ['id_izin' => $record->id],
                [
                    'dokumen_pks_bkk' => $data['dokumen_pks_bkk'],
                    'status_dokumen' => 'sign both',
                ]
            );

            // Refresh the record to get the updated relationships
            $record->refresh();
            $record->load(['izinDokumenPks', 'izinPersetujuan', 'izinDokumen']);

            try {
                if ($record->user && $record->user->email) {
                    Mail::to($record->user->email)->send(
                        new IzinPksNotification($record)
                    );
                    $notifications[] = 'PKS berhasil diupload, email notifikasi terkirim';
                } else {
                    $notifications[] = 'PKS berhasil diupload, email tidak terkirim (user tidak memiliki email)';
                }
            } catch (\Exception $e) {
                error_log('Failed to send PKS email: ' . $e->getMessage());
                $notifications[] = 'PKS berhasil diupload, email gagal terkirim';
            }
        }

        // Show consolidated notification
        if (!empty($notifications)) {
            Notification::make()
                ->title('Verifikasi Izin Berhasil Diperbarui')
                ->success()
                ->body(implode('<br>', $notifications))
                ->send();
        }
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

                        TextInput::make('kategori_izin')
                            ->label('Kategori Izin')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('nomor_surat')
                            ->label('Nomor Surat')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('tanggal_surat')
                            ->label('Tanggal Surat')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                ])
                ->collapsible()
                ->collapsed(),

            // Documents Section
            Section::make('Dokumen Pengajuan')
                ->schema($this->getDocumentFields())
                ->collapsible()
                ->collapsed(),

            // Step-by-step Verification dengan Tabs
            Tabs::make('Tahapan Verifikasi')
                ->extraAttributes(['class' => 'flex-wrap'])
                ->activeTab(function ($record) {
                    return $this->getActiveTab($record);
                })
                ->tabs([
                    // Tab 1: Verifikasi Dokumen
                    Tabs\Tab::make('1_verifikasi_dokumen')
                        ->label('1. Verifikasi Dokumen')
                        ->icon('heroicon-o-document-check')
                        ->badge(function ($record) {
                            if (!$record->izinDokumen?->hasil)
                                return '';
                            return $record->izinDokumen->hasil == 1 ? '✓' : '✗';
                        })
                        ->badgeColor(function ($record) {
                            if (!$record->izinDokumen?->hasil)
                                return 'gray';
                            return $record->izinDokumen->hasil == 1 ? 'success' : 'danger';
                        })
                        ->schema([
                            Section::make('Riwayat Catatan')
                                ->collapsible()
                                ->visible(fn($record) => $record->riwayat_perbaikan ?? false)
                                ->schema(function ($record) {
                                    return $this->getRiwayatPerbaikanSchema($record->riwayat_perbaikan ?? []);
                                }),

                            Select::make('hasil')
                                ->label('Hasil Verifikasi')
                                ->options([
                                    '1' => 'Berkas Lengkap',
                                    '2' => 'Berkas Tidak Lengkap',
                                ])
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    if ($get('hasil') === '1') {
                                        $set('catatan', '');
                                    }
                                }),

                            Textarea::make('catatan')
                                ->label('Catatan')
                                ->rows(4)
                                ->visible(fn(Get $get): bool => $get('hasil') === 2 || $get('hasil') === '2')
                                ->required(fn(Get $get): bool => $get('hasil') === '2' || $get('hasil') === 2),

                            DatePicker::make('tanggal_perbaikan')
                                ->label('Tanggal Perbaikan')
                                // ->native(false)
                                ->displayFormat('d F Y H:i')
                                ->disabled()
                                ->visible(fn($state) => $state ? true : false)
                                ->dehydrated(false),

                            TextInput::make('verifikasi_date')
                                ->label('Tanggal Verifikasi')
                                ->disabled()
                                ->formatStateUsing(fn($record) => $record->izinDokumen?->date_updated?->translatedFormat('d F Y H:i') ?? now()->translatedFormat('d F Y H:i'))
                                ->dehydrated(false),

                            Actions::make([
                                Actions\Action::make('simpan_verifikasi')
                                    ->label('Simpan Verifikasi')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $hasil = $get('hasil');
                                        if (!$hasil) {
                                            Notification::make()
                                                ->title('Gagal Verifikasi')
                                                ->danger()
                                                ->body('Hasil verifikasi harus dipilih.')
                                                ->send();
                                            return;
                                        }

                                        $catatan = $get('catatan');
                                        if ($hasil == '2' && !$catatan) {
                                            Notification::make()
                                                ->title('Gagal Verifikasi')
                                                ->danger()
                                                ->body('Catatan harus diisi jika berkas tidak lengkap.')
                                                ->send();
                                            return;
                                        }

                                        $record->izinDokumen()->updateOrCreate(
                                            ['izin_id' => $record->id],
                                            [
                                                'hasil' => (int) $hasil,
                                                'catatan' => $catatan ?? '',
                                                'status' => (int) $hasil,
                                                'date_updated' => now(),
                                            ]
                                        );

                                        if ($catatan) {
                                            $record->tambahCatatanPerbaikan($catatan);
                                        }

                                        $record->refresh();
                                        $record->load('izinDokumen');

                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IzinVerifikasiNotification(
                                                        $record,
                                                        (int) $hasil,
                                                        $catatan ?? null
                                                    )
                                                );

                                                $emailStatus = ' Email notifikasi telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Failed to send verification email: ' . $e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                        }

                                        Notification::make()
                                            ->title('Verifikasi Dokumen Berhasil')
                                            ->success()
                                            ->body('Status verifikasi dokumen telah diperbarui.' . $emailStatus)
                                            ->send();

                                        $this->redirect(VerifikasiIzin::getUrl());
                                    }),
                                    
                                Actions\Action::make('hapus_verifikasi')
                                    ->label('Hapus Verifikasi')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn($record) => $record->izinDokumen && $record->izinDokumen->hasil)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus Verifikasi Dokumen')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus hasil verifikasi ini? Tindakan ini akan menghapus verifikasi, tanggal perbaikan, dan riwayat perbaikan. Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record) {
                                        if ($record->izinDokumen) {
                                            $record->izinDokumen->delete();
                                        }

                                        // Hapus juga tanggal perbaikan dan riwayat perbaikan
                                        $record->update([
                                            'tanggal_perbaikan' => null,
                                            'riwayat_perbaikan' => null,
                                        ]);

                                        $record->refresh();

                                        Notification::make()
                                            ->title('Verifikasi Berhasil Dihapus')
                                            ->success()
                                            ->body('Record verifikasi dokumen, tanggal perbaikan, dan riwayat perbaikan telah dihapus.')
                                            ->send();

                                        $this->redirect(VerifikasiIzin::getUrl());
                                    })
                            ])
                        ]),

                    // Tab 2: Visitasi
                    Tabs\Tab::make('2_jadwal_visitasi')
                        ->label('2. Jadwal Visitasi')
                        ->icon('heroicon-o-calendar')
                        ->disabled(function ($record) {
                            return !$record->izinDokumen || $record->izinDokumen->hasil != 1;
                        })
                        ->badge(function ($record) {
                            return $record->izinDokumenPks?->tanggal_visitasi ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->izinDokumenPks?->tanggal_visitasi ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('visitasi_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah dokumen diverifikasi dan lengkap.')
                                ->visible(function ($record) {
                                    return !$record->izinDokumen || $record->izinDokumen->hasil != 1;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('verification_status_info')
                                    ->label('Status Verifikasi')
                                    ->disabled()
                                    ->formatStateUsing(function ($record) {
                                        return match ($record->izinDokumen->hasil ?? 0) {
                                            1 => 'Berkas Lengkap',
                                            2 => 'Berkas Tidak Lengkap',
                                            default => 'Belum Diverifikasi'
                                        };
                                    })
                                    ->dehydrated(false),

                                TextInput::make('verification_date_info')
                                    ->label('Tanggal Verifikasi')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record->izinDokumen?->date_updated?->translatedFormat('d F Y H:i') ?? '-')
                                    ->dehydrated(false),
                            ]),

                            DatePicker::make('tanggal_visitasi')
                                ->label('Tanggal Visitasi')
                                // ->native(false)
                                ->displayFormat('d F Y'),


                            Actions::make([
                                Actions\Action::make('set_jadwal_visitasi')
                                    ->label('Tetapkan Jadwal')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $tanggalVisitasi = $get('tanggal_visitasi');
                                        if (!$tanggalVisitasi) {
                                            Notification::make()
                                                ->title('Gagal Menetapkan Jadwal')
                                                ->danger()
                                                ->body('Tanggal visitasi harus dipilih.')
                                                ->send();
                                            return;
                                        }

                                        $record->izinDokumenPks()->updateOrCreate(
                                            ['id_izin' => $record->id],
                                            ['tanggal_visitasi' => $tanggalVisitasi]
                                        );

                                        // Refresh the record to get the updated relationship
                                        $record->refresh();
                                        $record->load('izinDokumenPks');

                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IzinVisitasiNotification($record)
                                                );
                                                $emailStatus = ' Email penjadwalan visitasi telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Failed to send visitasi email: ' . $e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                        }

                                        $tanggalText = \Carbon\Carbon::parse($tanggalVisitasi)->translatedFormat('d F Y');
                                        Notification::make()
                                            ->title('Visitasi Berhasil Dijadwalkan')
                                            ->success()
                                            ->body("Tanggal visitasi telah ditetapkan pada {$tanggalText}." . $emailStatus)
                                            ->send();

                                        // Reset field
                                        $this->redirect(VerifikasiIzin::getUrl());

                                    }),
                                    
                                Actions\Action::make('hapus_jadwal_visitasi')
                                    ->label('Hapus Jadwal')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn($record) => $record->izinDokumenPks && $record->izinDokumenPks->tanggal_visitasi)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus Jadwal Visitasi')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus jadwal visitasi ini? Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record) {
                                        if ($record->izinDokumenPks) {
                                            // Hapus hanya tanggal visitasi, bukan seluruh record
                                            $record->izinDokumenPks->update([
                                                'tanggal_visitasi' => null
                                            ]);
                                        }

                                        $record->refresh();

                                        Notification::make()
                                            ->title('Jadwal Visitasi Berhasil Dihapus')
                                            ->success()
                                            ->body('Jadwal visitasi telah dihapus.')
                                            ->send();

                                        $this->redirect(VerifikasiIzin::getUrl());
                                    })
                            ])
                        ]),

                    // Tab 3: Persetujuan
                    Tabs\Tab::make('3_persetujuan')
                        ->label('3. Proses Persetujuan')
                        ->icon('heroicon-o-check-circle')
                        ->disabled(function ($record) {
                            return !$record->izinDokumenPks || !$record->izinDokumenPks->tanggal_visitasi;
                        })
                        ->badge(function ($record) {
                            return $record->izinPersetujuan ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->izinPersetujuan ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('persetujuan_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah jadwal visitasi ditetapkan.')
                                ->visible(function ($record) {
                                    return !$record->izinDokumenPks || !$record->izinDokumenPks->tanggal_visitasi;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('verifikasi_info')
                                    ->label('Status Verifikasi')
                                    ->disabled()
                                    ->formatStateUsing(function ($record) {
                                        return match ($record->izinDokumen->hasil ?? 0) {
                                            1 => 'Berkas Lengkap',
                                            2 => 'Berkas Tidak Lengkap',
                                            default => 'Belum Diverifikasi'
                                        };
                                    })
                                    ->dehydrated(false),

                                TextInput::make('visitasi_info')
                                    ->label('Tanggal Visitasi')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record->izinDokumenPks?->tanggal_visitasi?->translatedFormat('d F Y') ?? 'Belum dijadwalkan')
                                    ->dehydrated(false),
                            ]),

                            DatePicker::make('tanggal_persetujuan')
                                ->label('Tanggal Persetujuan')
                                // ->native(false)
                                ->displayFormat('d F Y')
                                ->default(now()),

                            FileUpload::make('file_persetujuan')
                                ->label('Surat Persetujuan')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(3 * 1024) // Maksimal 3MB
                                ->directory('surat-persetujuan')
                                ->getUploadedFileNameForStorageUsing(function ($file) {
                                    $extension = $file->getClientOriginalExtension();
                                    return "Surat Persetujuan [" . date("dmyHis") . "]." . $extension;
                                })
                                ->openable()
                                ->helperText('Upload file surat persetujuan dalam format PDF (Maksimal 3MB)'),

                            TextInput::make('nomor_surat')
                                ->label('Nomor Surat Persetujuan')
                                ->formatStateUsing(function ($record) {
                                    return SuratPersetujuanIcv::where('id_akun', $record->user->id)->first()->nomor_surat ?? '';
                                })
                                ->required(),

                            DatePicker::make('masa_berlaku')
                                ->label('Masa Berlaku Izin')
                                ->formatStateUsing(function ($record) {
                                    return SuratPersetujuanIcv::where('id_akun', $record->user->id)->first()->masa_berlaku ?? '';
                                })
                                // ->native(false)
                                ->required()
                                ->displayFormat('d F Y'),

                            Actions::make([
                                Actions\Action::make('simpan_persetujuan')
                                    ->label('Simpan Persetujuan')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $filePersetujuan = $get('file_persetujuan');
                                        $tanggalPersetujuan = $get('tanggal_persetujuan');
                                        $nomorSurat = $get('nomor_surat');
                                        $masaBerlaku = $get('masa_berlaku');

                                        if (!$filePersetujuan) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('File surat persetujuan harus diupload.')
                                                ->send();
                                            return;
                                        }

                                        if (!$nomorSurat) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('Nomor surat persetujuan harus diisi.')
                                                ->send();
                                            return;
                                        }

                                        if (!$masaBerlaku) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('Masa berlaku izin harus diisi.')
                                                ->send();
                                            return;
                                        }

                                        $userId = $record->user_id;

                                        // Save to SuratPersetujuanIcv table
                                        SuratPersetujuanIcv::updateOrCreate(
                                            ['id_akun' => $userId],
                                            [
                                                'nomor_surat' => $nomorSurat,
                                                'tanggal_surat' => $tanggalPersetujuan,
                                                'masa_berlaku' => $masaBerlaku,
                                            ]
                                        );

                                        // Handle file upload properly
                                        $filePath = null;

                                        // Check if file_persetujuan is an array (new upload) or string (existing file)
                                        if (is_array($filePersetujuan)) {
                                            $file_uuid = array_key_first($filePersetujuan);
                                            $uploadedFile = $filePersetujuan[$file_uuid];

                                            if (is_string($uploadedFile)) {
                                                // Sudah berupa path file lama
                                                $filePath = $uploadedFile;
                                            } elseif ($uploadedFile && method_exists($uploadedFile, 'getClientOriginalExtension')) {
                                                // File baru diupload
                                                $filePath = \Storage::disk('public')
                                                    ->putFileAs(
                                                        'surat-persetujuan',
                                                        $uploadedFile,
                                                        'Surat Persetujuan ' . $record->user->name . ' [' . now()->format('dmYHis') . '].' . $uploadedFile->getClientOriginalExtension()
                                                    );
                                            }
                                        } else if (is_string($filePersetujuan)) {
                                            // Existing file path
                                            $filePath = $filePersetujuan;
                                        }

                                        if (!$filePath) {
                                            Notification::make()
                                                ->title('Gagal Menyimpan Persetujuan')
                                                ->danger()
                                                ->body('Terjadi kesalahan saat memproses file.')
                                                ->send();
                                            return;
                                        }

                                        // Save to IzinPersetujuan table
                                        $record->izinPersetujuan()->updateOrCreate(
                                            ['id_izin' => $record->id],
                                            [
                                                'date_updated' => $tanggalPersetujuan ?? now(),
                                                'file_persetujuan' => $filePath,
                                                'status' => 1,
                                            ]
                                        );

                                        // Refresh the record to get the updated relationships
                                        $record->refresh();
                                        $record->load(['izinPersetujuan', 'izinDokumen', 'izinDokumenPks']);

                                        // Send email notification
                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IzinPersetujuanNotification($record)
                                                );
                                                $emailStatus = ' Email persetujuan telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            $emailStatus = ' Email gagal dikirim.';
                                            error_log('Failed to send approval email: ' . $e->getMessage());
                                        }

                                        Notification::make()
                                            ->title('Persetujuan Berhasil')
                                            ->success()
                                            ->body('Izin telah disetujui dan surat persetujuan telah diupload.' . $emailStatus)
                                            ->send();

                                        // Redirect to refresh the page
                                        $this->redirect(VerifikasiIzin::getUrl());
                                    }),
                                    
                                Actions\Action::make('hapus_persetujuan')
                                    ->label('Hapus Persetujuan')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn($record) => $record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus Persetujuan')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus persetujuan ini? Tindakan ini akan menghapus data persetujuan dan data terkait di Surat Persetujuan ICV. Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record, Get $get) {
                                        $userId = $record->user_id;

                                        // Hapus file persetujuan dari storage jika ada
                                        if ($record->izinPersetujuan && $record->izinPersetujuan->file_persetujuan) {
                                            try {
                                                \Storage::disk('public')->delete($record->izinPersetujuan->file_persetujuan);
                                            } catch (\Exception $e) {
                                                error_log('Failed to delete approval file: ' . $e->getMessage());
                                            }
                                        }
                                        // Hapus atau reset data terkait di SuratPersetujuanIcv
                                        $suratPersetujuan = SuratPersetujuanIcv::where('id_akun', $userId)->where('nomor_surat', $get('nomor_surat'))->first();
                                        if ($suratPersetujuan) {
                                            $suratPersetujuan->delete();
                                        }

                                        // Hapus record dari tabel IzinPersetujuan
                                        if ($record->izinPersetujuan) {
                                            $record->izinPersetujuan->delete();
                                        }


                                        $record->refresh();

                                        Notification::make()
                                            ->title('Persetujuan Berhasil Dihapus')
                                            ->success()
                                            ->body('Data persetujuan dan file terkait telah dihapus.')
                                            ->send();

                                        $this->redirect(VerifikasiIzin::getUrl());
                                    })
                            ])
                        ]),

                    // Tab 4: PKS
                    Tabs\Tab::make('4_pks')
                        ->label('4. Upload PKS')
                        ->icon('heroicon-o-document-arrow-up')
                        ->disabled(function ($record) {
                            return !$record->izinPersetujuan;
                        })
                        ->badge(function ($record) {
                            return $record->izinDokumenPks?->dokumen_pks_bkk ? '✓' : '';
                        })
                        ->badgeColor(function ($record) {
                            return $record->izinDokumenPks?->dokumen_pks_bkk ? 'success' : 'gray';
                        })
                        ->schema([
                            \Filament\Forms\Components\Placeholder::make('pks_disabled_notice')
                                ->content('⚠️ Tab ini akan aktif setelah persetujuan diproses.')
                                ->visible(function ($record) {
                                    return !$record->izinPersetujuan;
                                }),

                            Grid::make(2)->schema([
                                TextInput::make('persetujuan_info')
                                    ->label('Tanggal Persetujuan')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record->izinPersetujuan?->date_updated?->translatedFormat('d F Y') ?? 'Belum disetujui')
                                    ->dehydrated(false),

                                TextInput::make('file_persetujuan_info')
                                    ->label('File Persetujuan')
                                    ->disabled()
                                    ->formatStateUsing(function ($record) {
                                        return $record->izinPersetujuan?->file_persetujuan ?
                                            basename($record->izinPersetujuan->file_persetujuan) : 'Belum ada file';
                                    })
                                    ->hintAction(
                                        FormAction::make('download_persetujuan')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->label('Unduh File Persetujuan')
                                            ->url(fn($record) => $record->izinPersetujuan?->file_persetujuan ?
                                                asset('storage/' . $record->izinPersetujuan->file_persetujuan) : '#')
                                            ->openUrlInNewTab()
                                            ->visible(fn($record) => $record->izinPersetujuan?->file_persetujuan !== null)
                                    )
                                    ->dehydrated(false),
                            ]),

                            FileUpload::make('dokumen_pks_bkk')
                                ->label('File Perjanjian Kerjasama')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(3 * 1024) // Maksimal 3MB
                                ->directory('perjanjian-kerjasama')
                                ->getUploadedFileNameForStorageUsing(function ($file, $record) {
                                    $extension = $file->getClientOriginalExtension();
                                    return "PKS " . $record->user->name . " [" . date("dmyHis") . "]." . $extension;
                                })
                                ->openable()
                                ->helperText('Upload file PKS dalam format PDF (Maksimal 3MB)'),

                            Actions::make([
                                Actions\Action::make('simpan_pks')
                                    ->label('Simpan PKS')
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set, $record) {
                                        $pks = $get('dokumen_pks_bkk');

                                        if (!$pks) {
                                            Notification::make()
                                                ->title('Gagal Upload PKS')
                                                ->danger()
                                                ->body('File PKS harus diupload.')
                                                ->send();
                                            return;
                                        }

                                        $file_uuid = array_key_first($pks);
                                        $pksFile = $pks[$file_uuid];

                                        if (is_string($pksFile)) {
                                            // Sudah berupa path file lama
                                            $filePath = $pksFile;
                                        } elseif (is_object($pksFile) && method_exists($pksFile, 'getClientOriginalExtension')) {
                                            // File baru diupload
                                            $filePath = Storage::disk('public')
                                                ->putFileAs(
                                                    'perjanjian-kerjasama',
                                                    $pksFile,
                                                    'PKS ' . $record->user->name . ' [' . now()->format('dmYHis') . '].' . $pksFile->getClientOriginalExtension()
                                                );
                                        } else {
                                            $filePath = null;
                                        }

                                        if (!$filePath) {
                                            Notification::make()
                                                ->title('Gagal Upload PKS')
                                                ->danger()
                                                ->body('Terjadi kesalahan saat memproses file PKS.')
                                                ->send();
                                            return;
                                        }

                                        $record->izinDokumenPks()->updateOrCreate(
                                            ['id_izin' => $record->id],
                                            [
                                                'dokumen_pks_bkk' => $filePath,
                                                'status_dokumen' => 'sign both',
                                            ]
                                        );

                                        // Refresh the record to get the updated relationships
                                        $record->refresh();
                                        $record->load(['izinDokumenPks', 'izinPersetujuan', 'izinDokumen']);

                                        try {
                                            if ($record->user && $record->user->email) {
                                                Mail::to($record->user->email)->send(
                                                    new IzinPksNotification($record)
                                                );
                                                $emailStatus = ' Email notifikasi PKS telah dikirim.';
                                            } else {
                                                $emailStatus = ' Email tidak dikirim (user tidak memiliki email).';
                                            }
                                        } catch (\Exception $e) {
                                            error_log('Failed to send PKS email: ' . $e->getMessage());
                                            $emailStatus = ' Email gagal dikirim.';
                                        }

                                        Notification::make()
                                            ->title('PKS Berhasil Diupload')
                                            ->success()
                                            ->body('File Perjanjian Kerjasama telah berhasil diupload.' . $emailStatus)
                                            ->send();

                                        // Reset field
                                        $this->redirect(VerifikasiIzin::getUrl());

                                    }),
                                    
                                Actions\Action::make('hapus_pks')
                                    ->label('Hapus PKS')
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->visible(fn($record) => $record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk)
                                    ->requiresConfirmation()
                                    ->modalHeading('Hapus PKS')
                                    ->modalDescription('Apakah Anda yakin ingin menghapus file PKS ini? Tindakan ini akan menghapus file PKS dan mengatur ulang status dokumen. Tindakan ini tidak dapat dibatalkan.')
                                    ->modalSubmitActionLabel('Ya, Hapus')
                                    ->action(function ($record) {
                                        // Hapus file PKS dari storage jika ada
                                        if ($record->izinDokumenPks && $record->izinDokumenPks->dokumen_pks_bkk) {
                                            try {
                                                \Storage::disk('public')->delete($record->izinDokumenPks->dokumen_pks_bkk);
                                            } catch (\Exception $e) {
                                                error_log('Failed to delete PKS file: ' . $e->getMessage());
                                            }
                                        }

                                        // Hapus field dokumen_pks_bkk dan reset status_dokumen
                                        if ($record->izinDokumenPks) {
                                            $record->izinDokumenPks->update([
                                                'dokumen_pks_bkk' => null,
                                                'status_dokumen' => null,
                                            ]);
                                        }

                                        $record->refresh();

                                        Notification::make()
                                            ->title('PKS Berhasil Dihapus')
                                            ->success()
                                            ->body('File PKS telah dihapus dan status dokumen direset.')
                                            ->send();

                                        $this->redirect(VerifikasiIzin::getUrl());
                                    })
                            ])
                        ]),
                ])
        ];
    }

    private function getRiwayatPerbaikanSchema(array $riwayat): array
    {
        error_log('Riwayat Perbaikan: ' . json_encode($riwayat));
        $schema = [];
        foreach ($riwayat as $index => $item) {
            $schema[] = Placeholder::make("riwayat_{$index}")
                ->label($item['tanggal'])
                ->content($item['catatan'] ?? 'Tidak ada catatan');
        }

        return $schema;
    }

    private function getDocumentFields(): array
    {
        $fields = [];

        for ($i = 1; $i <= 14; $i++) {
            $fields[] = TextInput::make("file{$i}")
                ->label(fn() => self::getIzinLabel("file{$i}"))
                ->readOnly()
                ->prefix("{$i}")
                ->formatStateUsing(function ($record) use ($i) {
                    $fileField = "file{$i}";
                    if ($record->$fileField) {
                        return substr($record->$fileField, strrpos($record->$fileField, '/') + 1);
                    } else {
                        return 'Tidak ada file';
                    }
                })
                ->hintAction(
                    FormAction::make("unduh_file{$i}")
                        ->icon('heroicon-o-arrow-down-tray')
                        ->label(fn() => "Unduh " . self::getIzinLabel("file{$i}"))
                        ->url(fn($record) => $record->{"file{$i}"} ? asset('storage/' . $record->{"file{$i}"}) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn($record) => $record->{"file{$i}"} !== null)
                )
                ->dehydrated(false);
        }

        return $fields;
    }
}
