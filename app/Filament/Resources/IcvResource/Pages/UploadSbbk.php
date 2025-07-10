<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Models\Icv;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Placeholder;
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
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
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

    protected static ?string $title = 'Update Sinkarkes';

    protected static ?string $navigationLabel = 'Update Sinkarkes';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['verifikator1'])) {
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
                        $query->whereNotNull('file_bukti_bayar');
                    })
                    // Custom ordering: yang belum update sinkarkes di atas
                    ->leftJoin('tbl_icv_sbbk', 'tbl_icv.id', '=', 'tbl_icv_sbbk.id_icv')
                    ->orderByRaw('CASE
                    WHEN tbl_icv_sbbk.sinkarkes IS NULL OR tbl_icv_sbbk.sinkarkes = "Belum" THEN 0
                    ELSE 1
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
                    ->label('Jadwal Distribusi ICV')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('sinkarkes_status')
                    ->label('Status Sinkarkes')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->icvSbbk?->sinkarkes || $record->icvSbbk->sinkarkes === 'Belum') {
                            return 'Belum Update';
                        }
                        return 'Sudah Update';
                    })
                    ->color(function ($record) {
                        if (!$record->icvSbbk?->sinkarkes || $record->icvSbbk->sinkarkes === 'Belum') {
                            return 'warning';
                        }
                        return 'success';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('tbl_icv_sbbk as sb', 'tbl_icv.id', '=', 'sb.id_icv')
                            ->orderByRaw('CASE
                            WHEN sb.sinkarkes IS NULL OR sb.sinkarkes = "Belum" THEN 0
                            ELSE 1
                        END ' . $direction)
                            ->select('tbl_icv.*');
                    }),
            ])
            ->actions([
                Action::make('update_sinkarkes')
                    ->label('Update Sinkarkes')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Update Status Sinkarkes - ' . $record->user->name)
                    ->modalWidth('xl')
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
                                            ->label('Jadwal Distribusi ICV')
                                            ->default($record->icvJadwal?->jadwal_pengambilan?->translatedFormat('d F Y'))
                                            ->disabled()
                                            ->dehydrated(false),

                                        Placeholder::make('distribusi_icv')
                                            ->label('Distribusi ICV')
                                            ->content('Distribusi e-ICV melalui aplikasi Sinkarkes')

                                    ]),
                                ])
                                ->collapsible()
                                ->collapsed(),

                            // Update Sinkarkes Section
                            Section::make('Update Status Sinkarkes')
                                ->schema([
                                    Select::make('sinkarkes')
                                        ->label('Status Sinkarkes')
                                        ->options([
                                            'Sudah' => 'Sudah',
                                            'Belum' => 'Belum',
                                        ])
                                        ->default($record->icvSbbk?->sinkarkes ?? 'Belum')
                                        ->disabled(fn() => $record->icvSbbk?->sinkarkes === 'Sudah')
                                        ->helperText('Update status input data ke sistem Sinkarkes')
                                        ->required(),

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

                                                // Update atau create record SBBK
                                                $record->icvSbbk()->updateOrCreate(
                                                    ['id_icv' => $record->id],
                                                    [
                                                        'sinkarkes' => $sinkarkes,
                                                        'date_updated' => now(),
                                                    ]
                                                );

                                                Notification::make()
                                                    ->title('Status Sinkarkes Berhasil Diupdate')
                                                    ->success()
                                                    ->body("Status Sinkarkes berhasil diupdate ke {$sinkarkes}.")
                                                    ->send();

                                                // Refresh halaman
                                                $this->redirect(UploadSbbk::getUrl());
                                            })
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

                // Filter berdasarkan User/Pemohon
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
                                $q->whereNotNull('file_bukti_bayar');
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
                                $q->whereNotNull('file_bukti_bayar');
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

                // Filter status sinkarkes
                SelectFilter::make('status_sinkarkes')
                    ->label('Status Sinkarkes')
                    ->options([
                        'belum' => 'Belum Update',
                        'sudah' => 'Sudah Update',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'belum',
                            fn(Builder $query): Builder => $query->where(function ($q) {
                                $q->whereDoesntHave('icvSbbk')
                                    ->orWhereHas('icvSbbk', function (Builder $subquery) {
                                        $subquery->where(function ($sq) {
                                            $sq->whereNull('sinkarkes')
                                                ->orWhere('sinkarkes', 'Belum');
                                        });
                                    });
                            })
                        )->when(
                            $data['value'] === 'sudah',
                            fn(Builder $query): Builder => $query->whereHas('icvSbbk', function (Builder $subquery) {
                                $subquery->where('sinkarkes', 'Sudah');
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
            'status_sinkarkes' => 'belum',
        ];
    }
}
