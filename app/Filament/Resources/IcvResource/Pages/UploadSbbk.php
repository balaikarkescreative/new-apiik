<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Models\Icv;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Storage;
use App\Mail\IcvSbbkNotification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\Mail;
use Filament\Tables\Filters\SelectFilter;

use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Get;
use Filament\Forms\Set;

class UploadSbbk extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IcvResource::class;

    protected static string $view = 'filament.resources.icv-resource.pages.upload-sbbk';

    protected static ?string $title = 'Surat Bukti Barang Keluar';

    protected static ?string $navigationLabel = 'Surat Bukti Barang Keluar';

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['verifikator1'])) {
            // abort(403, 'Akses ditolak. Hanya verifikator yang dapat mengelola upload SBBK ICV.');
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
                        'icvJadwal',
                        'icvSbbk',
                        'icvBuktiBayar',
                    ])
                    // Hanya tampilkan yang sudah ada jadwal dan pembayaran sudah diverifikasi
                    ->whereHas('icvJadwal', function (Builder $query) {
                        $query->whereNotNull('jadwal_pengambilan');
                    })
                    ->whereHas('icvBuktiBayar', function (Builder $query) {
                        $query->whereNotNull('distribusi_icv');
                    })
                    // Custom ordering: yang belum upload SBBK di atas
                    ->leftJoin('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
                    ->orderByRaw('CASE
                    WHEN tbl_icv_sbbk.file_sbbk IS NULL THEN 0
                    WHEN tbl_icv_sbbk.sinkarkes IS NULL OR tbl_icv_sbbk.sinkarkes = "Belum" THEN 1
                    ELSE 2
                END')
                    ->orderBy('tbl_icv.tanggal_pengajuan', 'desc')
                    ->select('tbl_icv.*')
            )
            ->columns([
                TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->date()
                    ->formatStateUsing(function ($state) {
                        return $state ? $state->translatedFormat('d/m/Y') : '-';
                    })
                    ->sortable()
                    ->searchable(),

                // Enhanced Klinik/RS & Lokasi column (consistent with PenerbitanKodeBilling)
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
                                    ->orderBy('users.name', $direction)
                                    ->select('tbl_icv.*');
                    }),

                TextColumn::make('icvAlokasi.icv_disetujui')
                    ->label('ICV Disetujui')
                    ->sortable(),

                TextColumn::make('icvJadwal.jadwal_pengambilan')
                    ->label('Jadwal Pengambilan/Pengiriman')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('icvBuktiBayar.distribusi_icv')
                    ->label('Metode Distribusi')
                    ->badge()
                    ->color(function ($record) {
                        return $record->icvBuktiBayar?->distribusi_icv === 'Diambil' ? 'info' : 'success';
                    }),

                TextColumn::make('upload_sbbk_status')
                    ->label('Status SBBK')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->icvSbbk || !$record->icvSbbk->file_sbbk) {
                            return 'Belum Upload SBBK';
                        }

                        if (!$record->icvSbbk->sinkarkes || $record->icvSbbk->sinkarkes === 'Belum') {
                            return 'Belum Update Sinkarkes';
                        }

                        return 'Selesai';
                    })
                    ->color(function ($record) {
                        if (!$record->icvSbbk || !$record->icvSbbk->file_sbbk) {
                            return 'danger';
                        }

                        if (!$record->icvSbbk->sinkarkes || $record->icvSbbk->sinkarkes === 'Belum') {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('tbl_icv_sbbk as sb', 'tbl_icv.id', '=', 'sb.id_icv')
                            ->orderByRaw('CASE
                            WHEN sb.file_sbbk IS NULL THEN 0
                            WHEN sb.sinkarkes IS NULL OR sb.sinkarkes = "Belum" THEN 1
                            ELSE 2
                        END ' . $direction)
                            ->select('tbl_icv.*');
                    }),
            ])
            ->actions([
                Action::make('kelola_sbbk')
                    ->label('Kelola')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Upload SBBK & Update Sinkarkes - ' . $record->user->name)
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->form(function ($record) {
                        return [
                            // Basic information section
                            Section::make('Informasi Pengajuan')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('nama_pemohon')
                                            ->label('Nama Pemohon')
                                            ->default($record->user->name)
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('tanggal_pengajuan')
                                            ->label('Tanggal Pengajuan')
                                            ->default($record->tanggal_pengajuan?->translatedFormat('d F Y'))
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('jadwal_pengambilan')
                                            ->label('Jadwal Pengambilan/Pengiriman')
                                            ->default($record->icvJadwal?->jadwal_pengambilan?->translatedFormat('d F Y'))
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('metode_distribusi')
                                            ->label('Metode Distribusi')
                                            ->default($record->icvBuktiBayar?->distribusi_icv)
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                                ])
                                ->collapsible(),

                            // Step-by-step tabs
                            Tabs::make('Tahapan SBBK')
                                ->extraAttributes(['class' => 'flex-wrap'])
                                ->activeTab(function ($record) {
                                    if (!$record->icvSbbk || !$record->icvSbbk->file_sbbk) {
                                        return 1;
                                    }

                                    return 2;
                                })
                                ->tabs([
                                    // Tab 1: Upload SBBK
                                    Tabs\Tab::make(1)
                                        ->label('1. Upload SBBK')
                                        ->icon('heroicon-o-document-plus')
                                        ->badge(function ($record) {
                                            return $record->icvSbbk?->file_sbbk ? '✓' : '';
                                        })
                                        ->badgeColor(function ($record) {
                                            return $record->icvSbbk?->file_sbbk ? 'success' : 'gray';
                                        })
                                        ->schema([
                                            FileUpload::make('file_sbbk')
                                                ->label('File SBBK (Surat Bukti Barang Keluar)')
                                                ->acceptedFileTypes(['application/pdf'])
                                                ->maxSize(1024 * 3)
                                                ->directory('icv/sbbk')
                                                ->getUploadedFileNameForStorageUsing(function ($file) use ($record) {
                                                    return 'SBBK ' . $record->user->name . ' [' . now()->format('dmYHis') . '].' . $file->getClientOriginalExtension();
                                                })
                                                ->downloadable()
                                                ->openable()
                                                ->disabled(fn() => $record->icvSbbk?->file_sbbk !== null)
                                                ->default($record->icvSbbk?->file_sbbk)
                                                ->helperText('Upload SBBK dalam format PDF. Maksimal ukuran file 3MB.'),

                                            TextInput::make('tanggal_upload_sbbk')
                                                ->label('Tanggal Upload')
                                                ->disabled()
                                                ->formatStateUsing(fn() => $record->icvSbbk?->date_updated?->translatedFormat('d F Y H:i') ?? now()->translatedFormat('d F Y H:i'))
                                                ->dehydrated(false),

                                            Actions::make([
                                                Actions\Action::make('simpan_sbbk')
                                                    ->label('Upload SBBK')
                                                    ->icon('heroicon-o-cloud-arrow-up')
                                                    ->color('primary')
                                                    ->visible(fn() => $record->icvSbbk?->file_sbbk === null)
                                                    ->action(function (Get $get, Set $set, $record) {
                                                        $fileSbbk = $get('file_sbbk');

                                                        if (!$fileSbbk) {
                                                            Notification::make()
                                                                ->title('Gagal Upload SBBK')
                                                                ->danger()
                                                                ->body('File SBBK harus diupload.')
                                                                ->send();
                                                            return;
                                                        }

                                                        $file_uuid = array_key_first($fileSbbk);
                                                        $fileSbbk = $fileSbbk[$file_uuid];

                                                        $file = Storage::disk('public')
                                                            ->putFileAs('icv/sbbk', $fileSbbk, 'SBBK ' . $record->user->name . ' [' . now()->format('dmYHis') . '].' . $fileSbbk->getClientOriginalExtension());

                                                        // Update atau buat record SBBK
                                                        $record->icvSbbk()->updateOrCreate(
                                                            ['id_icv' => $record->id],
                                                            [
                                                                'file_sbbk' => $file,
                                                                'date_updated' => now(),
                                                            ]
                                                        );

                                                        // Refresh the record to get the latest SBBK data
                                                        $record = $record->fresh(['user', 'icvSbbk']);

                                                        // Kirim email notifikasi SBBK
                                                        try {
                                                            if ($record->user && $record->user->email) {
                                                                Mail::to($record->user->email)->send(
                                                                    new IcvSbbkNotification($record, $file)
                                                                );
                                                            }
                                                        } catch (\Exception $e) {
                                                            error_log('Gagal mengirim email SBBK ICV: ' . $e->getMessage());
                                                        }

                                                        Notification::make()
                                                            ->title('SBBK Berhasil Diupload')
                                                            ->success()
                                                            ->body('File SBBK berhasil diupload dan email notifikasi telah dikirim.')
                                                            ->send();

                                                        // Refresh record
                                                        $this->redirect(UploadSbbk::getUrl());
                                                    })
                                            ]),
                                        ]),

                                    // Tab 2: Update Sinkarkes
                                    Tabs\Tab::make(2)
                                        ->label('2. Update Sinkarkes')
                                        ->icon('heroicon-o-pencil-square')
                                        ->disabled(function ($record) {
                                            return !$record->icvSbbk || !$record->icvSbbk->file_sbbk;
                                        })
                                        ->badge(function ($record) {
                                            return $record->icvSbbk?->sinkarkes === 'Sudah' ? '✓' : '';
                                        })
                                        ->badgeColor(function ($record) {
                                            return $record->icvSbbk?->sinkarkes === 'Sudah' ? 'success' : 'gray';
                                        })
                                        ->schema([
                                            \Filament\Forms\Components\Placeholder::make('sinkarkes_disabled_notice')
                                                ->content('⚠️ Tab ini akan aktif setelah file SBBK diupload.')
                                                ->visible(function ($record) {
                                                    return !$record->icvSbbk || !$record->icvSbbk->file_sbbk;
                                                }),

                                            TextInput::make('file_sbbk_info')
                                                ->label('File SBBK')
                                                ->disabled()
                                                ->formatStateUsing(function ($record) {
                                                    return $record->icvSbbk?->file_sbbk ?
                                                        basename($record->icvSbbk->file_sbbk) : 'Belum upload SBBK';
                                                })
                                                ->hintAction(
                                                    FormAction::make('download_sbbk')
                                                        ->icon('heroicon-o-arrow-down-tray')
                                                        ->label('Download SBBK')
                                                        ->url(fn($record) => $record->icvSbbk?->file_sbbk ?
                                                            asset('storage/' . $record->icvSbbk->file_sbbk) : '#')
                                                        ->openUrlInNewTab()
                                                        ->visible(fn($record) => $record->icvSbbk?->file_sbbk !== null)
                                                )
                                                ->dehydrated(false),

                                            Select::make('sinkarkes')
                                                ->label('Status Sinkarkes')
                                                ->options([
                                                    'Sudah' => 'Sudah',
                                                    'Belum' => 'Belum',
                                                ])
                                                ->default($record->icvSbbk?->sinkarkes)
                                                ->disabled(fn() => $record->icvSbbk?->sinkarkes === 'Sudah')
                                                ->helperText('Update status input data ke sistem Sinkarkes'),

                                            Actions::make([
                                                Actions\Action::make('update_sinkarkes')
                                                    ->label('Update Sinkarkes')
                                                    ->icon('heroicon-o-check')
                                                    ->color('success')
                                                    ->visible(fn() => $record->icvSbbk?->sinkarkes !== 'Sudah')
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

                                                        // Update record SBBK
                                                        $record->icvSbbk->update([
                                                            'sinkarkes' => $sinkarkes,
                                                            'date_updated' => now(),
                                                        ]);

                                                        Notification::make()
                                                            ->title('Status Sinkarkes Berhasil Diupdate')
                                                            ->success()
                                                            ->body("Status Sinkarkes berhasil diupdate ke {$sinkarkes}.")
                                                            ->send();

                                                        // Refresh record
                                                        $record->refresh();
                                                    })
                                            ]),
                                        ]),
                                ]),
                        ];
                    })
            ])
            ->striped()
            ->filters([
                DateRangeFilter::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->defaultLast7Days(),

                // Filter berdasarkan User/Pemohon (consistent with PenerbitanKodeBilling)
                SelectFilter::make('user')
                    ->label('Pemohon/Klinik')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->whereHas('icvs', function ($query) {
                $query->whereHas('icvJadwal', function ($q) {
                    $q->whereNotNull('jadwal_pengambilan');
                })
                ->whereHas('icvBuktiBayar', function ($q) {
                    $q->whereNotNull('distribusi_icv');
                });
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($user) {
                $kotakab = $user->kotakab ? " ({$user->kotakab})" : '';
                return [$user->id => $user->name . $kotakab];
            });
        })
        ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) return $query;

            return $query->where('user_id', $data['value']);
        }),

    // Filter berdasarkan Kota/Kabupaten
    SelectFilter::make('kotakab')
        ->label('Kota/Kabupaten')
        ->searchable()
        ->preload()
        ->options(function () {
            return \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->whereHas('icvs', function ($query) {
                $query->whereHas('icvJadwal', function ($q) {
                    $q->whereNotNull('jadwal_pengambilan');
                })
                ->whereHas('icvBuktiBayar', function ($q) {
                    $q->whereNotNull('distribusi_icv');
                });
            })
            ->whereNotNull('kotakab')
            ->distinct()
            ->orderBy('kotakab')
            ->pluck('kotakab', 'kotakab');
        })
        ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) return $query;

            return $query->whereHas('user', function (Builder $query) use ($data) {
                $query->where('kotakab', $data['value']);
            });
        }),

    // Enhanced status SBBK filter
    SelectFilter::make('status_sbbk')
        ->label('Status SBBK')
        ->options([
            'belum_upload' => 'Belum Upload SBBK',
            'belum_sinkarkes' => 'Belum Update Sinkarkes',
            'selesai' => 'Selesai',
        ])
        ->query(function (Builder $query, array $data): Builder {
            return $query->when(
                $data['value'] === 'belum_upload',
                fn(Builder $query): Builder => $query->whereDoesntHave('icvSbbk')
                    ->orWhereHas('icvSbbk', function (Builder $subquery) {
                        $subquery->whereNull('file_sbbk');
                    })
            )->when(
                $data['value'] === 'belum_sinkarkes',
                fn(Builder $query): Builder => $query->whereHas('icvSbbk', function (Builder $subquery) {
                    $subquery->whereNotNull('file_sbbk')
                        ->where(function ($q) {
                            $q->whereNull('sinkarkes')
                                ->orWhere('sinkarkes', 'Belum');
                        });
                })
            )->when(
                $data['value'] === 'selesai',
                fn(Builder $query): Builder => $query->whereHas('icvSbbk', function (Builder $subquery) {
                    $subquery->whereNotNull('file_sbbk')
                        ->where('sinkarkes', 'Sudah');
                })
            );
        }),

    // Filter berdasarkan Metode Distribusi
    SelectFilter::make('metode_distribusi')
        ->label('Metode Distribusi')
        ->options([
            'Dikirim' => 'Dikirim',
            'Diambil' => 'Diambil',
        ])
        ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) return $query;

            return $query->whereHas('icvBuktiBayar', function (Builder $query) use ($data) {
                $query->where('distribusi_icv', $data['value']);
            });
        }),

    // Filter berdasarkan Range ICV Disetujui
    SelectFilter::make('icv_range')
        ->label('Range ICV Disetujui')
        ->options([
            '1-50' => '1 - 50 ICV',
            '51-100' => '51 - 100 ICV',
            '101-200' => '101 - 200 ICV',
            '201+' => '200+ ICV',
        ])
        ->query(function (Builder $query, array $data): Builder {
            if (!$data['value']) return $query;

            return $query->whereHas('icvAlokasi', function (Builder $query) use ($data) {
                switch ($data['value']) {
                    case '1-50':
                        $query->whereBetween('icv_disetujui', [1, 50]);
                        break;
                    case '51-100':
                        $query->whereBetween('icv_disetujui', [51, 100]);
                        break;
                    case '101-200':
                        $query->whereBetween('icv_disetujui', [101, 200]);
                        break;
                    case '201+':
                        $query->where('icv_disetujui', '>', 200);
                        break;
                }
            });
        }),
], FiltersLayout::AboveContent);
    }

    public function getTableFiltersFormData(): array
{
    return [
        'status_sbbk' => 'belum_upload',
    ];
}

}
