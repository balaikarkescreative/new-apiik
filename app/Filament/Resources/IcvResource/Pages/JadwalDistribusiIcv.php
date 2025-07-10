<?php

namespace App\Filament\Resources\IcvResource\Pages;

use App\Filament\Resources\IcvResource;
use App\Models\Icv;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\IcvJadwalNotification;
use Carbon\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Get;
use Filament\Forms\Set;

class JadwalDistribusiIcv extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IcvResource::class;

    protected static string $view = 'filament.resources.icv-resource.pages.jadwal-distribusi-icv';

    protected static ?string $title = 'Jadwal Distribusi ICV';

    protected static ?string $navigationLabel = 'Jadwal Distribusi';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2;

    public function mountCanAuthorizeAccess(): void
    {
        parent::mountCanAuthorizeAccess();

        // Cek apakah user memiliki peran yang sesuai
        if (!auth()->user()->hasRole(['verifikator1', 'pos'])) {
            // abort(403, 'Akses ditolak. Hanya verifikator dan petugas POS yang dapat mengelola jadwal distribusi ICV.');
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
                        'icvBuktiBayar',
                        'icvJadwal',
                        'icvAlokasi',
                    ])
                    // Tampilkan yang pembayaran sudah diverifikasi (baik yang sudah ada jadwal maupun belum)
                    ->whereHas('icvBuktiBayar', function (Builder $query) {
                        $query->whereNotNull('file_bukti_bayar');
                    })
                    // Custom ordering: yang belum dijadwalkan di atas
                    ->leftJoin('tbl_icv_jadwal', 'tbl_icv.id', '=', 'tbl_icv_jadwal.id_icv')
                    ->orderByRaw('CASE
                    WHEN tbl_icv_jadwal.jadwal_pengambilan IS NULL THEN 0
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
                    ->label('Jumlah ICV')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('jadwal_distribusi')
                    ->label('Jadwal Distribusi')
                    ->getStateUsing(function ($record) {
                        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
                            return $record->icvJadwal->jadwal_pengambilan->translatedFormat('d F Y');
                        }
                        return 'Belum Dijadwalkan';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->icvJadwal || !$record->icvJadwal->jadwal_pengambilan) {
                            return 'danger';
                        }

                        $jadwal = $record->icvJadwal->jadwal_pengambilan;
                        $today = now()->startOfDay();

                        if ($jadwal->isToday()) {
                            return 'warning';
                        } elseif ($jadwal->isTomorrow()) {
                            return 'info';
                        } elseif ($jadwal->gt($today)) {
                            return 'success';
                        } else {
                            return 'gray';
                        }
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('tbl_icv_jadwal as jd', 'tbl_icv.id', '=', 'jd.id_icv')
                            ->orderBy('jd.jadwal_pengambilan', $direction)
                            ->select('tbl_icv.*');
                    }),

                TextColumn::make('status_jadwal')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->icvJadwal || !$record->icvJadwal->jadwal_pengambilan) {
                            return 'Belum Dijadwalkan';
                        }

                        $jadwal = $record->icvJadwal->jadwal_pengambilan;
                        $today = now()->startOfDay();

                        if ($jadwal->isToday()) {
                            return 'Hari Ini';
                        } elseif ($jadwal->isTomorrow()) {
                            return 'Besok';
                        } elseif ($jadwal->gt($today)) {
                            return 'Akan Datang';
                        } else {
                            return 'Sudah Lewat';
                        }
                    })
                    ->color(function ($record) {
                        if (!$record->icvJadwal || !$record->icvJadwal->jadwal_pengambilan) {
                            return 'danger';
                        }

                        $jadwal = $record->icvJadwal->jadwal_pengambilan;
                        $today = now()->startOfDay();

                        if ($jadwal->isToday()) {
                            return 'warning';
                        } elseif ($jadwal->isTomorrow()) {
                            return 'info';
                        } elseif ($jadwal->gt($today)) {
                            return 'primary';
                        } else {
                            return 'gray';
                        }
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('tbl_icv_jadwal as jd2', 'tbl_icv.id', '=', 'jd2.id_icv')
                            ->orderByRaw('CASE
                            WHEN jd2.jadwal_pengambilan IS NULL THEN 0
                            ELSE 1
                        END ' . $direction)
                            ->select('tbl_icv.*');
                    }),
            ])
            ->actions([
                Action::make('jadwalkan')
                    ->label(function ($record) {
                        if ($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan) {
                            return 'Edit Jadwal';
                        }
                        return 'Jadwalkan';
                    })
                    ->icon('heroicon-o-calendar')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Jadwal Distribusi ICV - ' . $record->user->name)
                    ->modalWidth('xl')
                    ->modalSubmitAction(false)
                    ->form(function ($record) {
                        return [
                            // Basic information section
                            Section::make('Informasi Pengajuan')
                                ->schema([
                                    Grid::make(3)->schema([
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

                                        TextInput::make('jumlah_icv')
                                            ->label('Jumlah ICV')
                                            ->default($record->icvAlokasi?->icv_disetujui ?? 0)
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),

                                    Placeholder::make('distribusi_icv')
                                        ->label('Distribusi ICV')
                                        ->content('Distribusi e-ICV melalui aplikasi Sinkarkes'),
                                ])
                                ->collapsible(),

                            // Jadwal distribusi form
                            Section::make('Jadwal Distribusi ICV')
                                ->schema([
                                    DatePicker::make('jadwal_pengambilan')
                                        ->label('Tanggal Distribusi ICV')
                                        ->native(false)
                                        ->displayFormat('d F Y')
                                        ->minDate(now()->addDay())
                                        ->default($record->icvJadwal?->jadwal_pengambilan)
                                        ->required(),

                                    Actions::make([
                                        Actions\Action::make('simpan_jadwal')
                                            ->label($record->icvJadwal && $record->icvJadwal->jadwal_pengambilan ? 'Update Jadwal' : 'Simpan Jadwal')
                                            ->icon('heroicon-o-check')
                                            ->color('success')
                                            ->action(function (Get $get, Set $set, $record) {
                                                $jadwalPengambilan = $get('jadwal_pengambilan');

                                                if (!$jadwalPengambilan) {
                                                    Notification::make()
                                                        ->title('Gagal Menetapkan Jadwal')
                                                        ->danger()
                                                        ->body('Tanggal harus diisi.')
                                                        ->send();
                                                    return;
                                                }

                                                // Update atau buat jadwal
                                                $record->icvJadwal()->updateOrCreate(
                                                    ['id_icv' => $record->id],
                                                    [
                                                        'jadwal_pengambilan' => $jadwalPengambilan,
                                                        'date_updated' => now(),
                                                    ]
                                                );

                                                $record = $record->fresh([
                                                    'icvJadwal',
                                                    'user',
                                                ]);

                                                if (is_string($jadwalPengambilan)) {
                                                    $jadwalPengambilan = Carbon::parse($jadwalPengambilan);
                                                }

                                                // Kirim email notifikasi jadwal
                                                $emailStatus = '';
                                                try {
                                                    if ($record->user && $record->user->email) {
                                                        Mail::to($record->user->email)->send(
                                                            new IcvJadwalNotification($record, $jadwalPengambilan)
                                                        );
                                                        $emailStatus = ' Email notifikasi telah dikirim.';
                                                    } else {
                                                        $emailStatus = ' Email tidak dapat dikirim (email user tidak valid).';
                                                    }
                                                } catch (\Exception $e) {
                                                    error_log('Gagal mengirim email jadwal ICV: ' . $e->getMessage());
                                                    $emailStatus = ' Email gagal dikirim.';
                                                }

                                                $isUpdate = $record->icvJadwal && $record->icvJadwal->jadwal_pengambilan;
                                                $actionText = $isUpdate ? 'diperbarui' : 'ditetapkan';

                                                Notification::make()
                                                    ->title('Jadwal Berhasil ' . ucfirst($actionText))
                                                    ->success()
                                                    ->body('Jadwal distribusi telah berhasil ' . $actionText . ' pada ' .
                                                        $jadwalPengambilan->translatedFormat('d F Y') . $emailStatus)
                                                    ->send();

                                                // Refresh halaman setelah simpan
                                                $this->redirect(JadwalDistribusiIcv::getUrl());
                                            })
                                    ]),
                                ]),
                        ];
                    }),
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->striped()
            ->filters([
                DateRangeFilter::make('tanggal_pengajuan')
                    ->defaultLast7Days()
                    ->label('Tanggal Pengajuan'),

                // Filter berdasarkan User/Pemohon (consistent with other pages)
                SelectFilter::make('user')
                    ->label('Pemohon/Klinik')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'user');
                        })
                            ->whereHas('icvs', function ($query) {
                                $query->whereHas('icvBuktiBayar', function ($q) {
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
                        if (!$data['value'])
                            return $query;

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
                                $query->whereHas('icvBuktiBayar', function ($q) {
                                    $q->whereNotNull('distribusi_icv');
                                });
                            })
                            ->whereNotNull('kotakab')
                            ->distinct()
                            ->orderBy('kotakab')
                            ->pluck('kotakab', 'kotakab');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        return $query->whereHas('user', function (Builder $query) use ($data) {
                            $query->where('kotakab', $data['value']);
                        });
                    }),

                // Enhanced status jadwal filter
                SelectFilter::make('status_jadwal')
                    ->label('Status Jadwal')
                    ->options([
                        'belum_dijadwalkan' => 'Belum Dijadwalkan',
                        'sudah_dijadwalkan' => 'Sudah Dijadwalkan',
                        'hari_ini' => 'Hari Ini',
                        'besok' => 'Besok',
                        'akan_datang' => 'Akan Datang',
                        'sudah_lewat' => 'Sudah Lewat',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] === 'belum_dijadwalkan',
                            fn(Builder $query): Builder => $query->whereDoesntHave('icvJadwal')
                                ->orWhereHas('icvJadwal', function (Builder $subquery) {
                                    $subquery->whereNull('jadwal_pengambilan');
                                })
                        )->when(
                                $data['value'] === 'sudah_dijadwalkan',
                                fn(Builder $query): Builder => $query->whereHas('icvJadwal', function (Builder $subquery) {
                                    $subquery->whereNotNull('jadwal_pengambilan');
                                })
                            )->when(
                                $data['value'] === 'hari_ini',
                                fn(Builder $query): Builder => $query->whereHas('icvJadwal', function (Builder $subquery) {
                                    $subquery->whereDate('jadwal_pengambilan', now()->toDateString());
                                })
                            )->when(
                                $data['value'] === 'besok',
                                fn(Builder $query): Builder => $query->whereHas('icvJadwal', function (Builder $subquery) {
                                    $subquery->whereDate('jadwal_pengambilan', now()->addDay()->toDateString());
                                })
                            )->when(
                                $data['value'] === 'akan_datang',
                                fn(Builder $query): Builder => $query->whereHas('icvJadwal', function (Builder $subquery) {
                                    $subquery->where('jadwal_pengambilan', '>', now()->addDay()->endOfDay());
                                })
                            )->when(
                                $data['value'] === 'sudah_lewat',
                                fn(Builder $query): Builder => $query->whereHas('icvJadwal', function (Builder $subquery) {
                                    $subquery->where('jadwal_pengambilan', '<', now()->startOfDay());
                                })
                            );
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
                        if (!$data['value'])
                            return $query;

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

                // Filter berdasarkan Rentang Jadwal
                DateRangeFilter::make('jadwal_pengambilan')
                    ->label('Jadwal Distribusi')

            ], FiltersLayout::AboveContent);
    }

    public function getTableFiltersFormData(): array
    {
        return [
            'status_jadwalkan' => 'belum_dijadwalkan',
        ];
    }
}
