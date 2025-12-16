<?php

namespace App\Filament\Resources\IzinResource\Pages;

use App\Filament\Resources\IzinResource;
use App\Models\Izin;
use Filament\Tables\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DaftarIzinPersetujuan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IzinResource::class;

    protected static string $view = 'filament.resources.izin-resource.pages.daftar-izin-persetujuan';

    protected static ?string $title = 'File Surat Persetujuan ICV';

    protected static ?string $navigationLabel = 'File Surat Persetujuan ICV';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Izin::query()
                    ->with(['user', 'izinPersetujuan', 'izinDokumenPks'])
                    ->join('tbl_izin_persetujuan', 'tbl_izin.id', '=', 'tbl_izin_persetujuan.id_izin')
                    ->whereNotNull('tbl_izin_persetujuan.file_persetujuan')
                    ->when(auth()->user()?->hasRole('user'), fn ($query) => $query->where('user_id', auth()->id()))
                    ->select('tbl_izin.*')
            )
            ->columns([
                TextColumn::make('klinik_info')
                    ->label('Klinik/RS & Lokasi')
                    ->getStateUsing(function ($record) {
                        $klinik = Str::limit($record->user?->name ?? 'N/A', 30);
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
                        return $query->join('users', 'tbl_izin.user_id', '=', 'users.id')
                            ->orderBy('users.name', $direction)
                            ->select('tbl_izin.*');
                    }),

                TextColumn::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->badge()
                    ->color(
                        fn ($state) => match ($state) {
                            'Izin Baru' => 'success',
                            'Perpanjang Izin' => 'warning',
                            default => 'gray',
                        }
                    )
                    ->sortable()
                    ->searchable(),

                TextColumn::make('izinPersetujuan.date_updated')
                    ->label('Tanggal Persetujuan')
                    ->date('d M Y')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('tbl_izin_persetujuan.date_updated', $direction)),

                TextColumn::make('izinPersetujuan.status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Disetujui' : 'Draft')
                    ->color(fn ($state) => $state === 1 ? 'success' : 'warning'),
            ])
            ->filters([
                DateRangeFilter::make('tanggal_persetujuan')
                    ->label('Tanggal Persetujuan')
                    ->useColumn('tbl_izin_persetujuan.date_updated'),

                SelectFilter::make('kategori_izin')
                    ->label('Kategori Izin')
                    ->options(function () {
                        return Izin::whereHas('izinPersetujuan', function ($query) {
                            $query->whereNotNull('file_persetujuan');
                        })
                            ->whereNotNull('kategori_izin')
                            ->distinct()
                            ->orderBy('kategori_izin')
                            ->pluck('kategori_izin', 'kategori_izin');
                    }),

                SelectFilter::make('user_id')
                    ->label('Pemohon/Klinik')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => ! auth()->user()?->hasRole('user'))
                    ->options(function () {
                        $userQuery = \App\Models\User::query()
                            ->whereHas('izins', function ($query) {
                                $query->whereHas('izinPersetujuan', function ($subQuery) {
                                    $subQuery->whereNotNull('file_persetujuan');
                                });
                            });

                        if (auth()->user()?->hasRole('user')) {
                            $userQuery->where('id', auth()->id());
                        }

                        return $userQuery
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                $kotakab = $user->kotakab ? " ({$user->kotakab})" : '';

                                return [$user->id => $user->name.$kotakab];
                            });
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where('user_id', $data['value']);
                    }),

                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->visible(fn () => ! auth()->user()?->hasRole('user'))
                    ->options(function () {
                        $userQuery = \App\Models\User::query()
                            ->whereHas('izins', function ($query) {
                                $query->whereHas('izinPersetujuan', function ($subQuery) {
                                    $subQuery->whereNotNull('file_persetujuan');
                                });
                            });

                        if (auth()->user()?->hasRole('user')) {
                            $userQuery->where('id', auth()->id());
                        }

                        return $userQuery
                            ->whereNotNull('kotakab')
                            ->distinct()
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
                    }),

                SelectFilter::make('status_pks')
                    ->label('Status PKS')
                    ->options([
                        'belum_pks' => 'Perlu Upload PKS',
                        'selesai' => 'PKS Selesai',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'belum_pks' => $query->where(function ($q) {
                                $q->whereDoesntHave('izinDokumenPks')
                                    ->orWhereHas('izinDokumenPks', fn ($sub) => $sub->whereNull('dokumen_pks_bkk'));
                            }),
                            'selesai' => $query->whereHas('izinDokumenPks', fn ($q) => $q->whereNotNull('dokumen_pks_bkk')),
                            default => $query,
                        };
                    }),
            ], FiltersLayout::AboveContent)
            ->actions([
                Action::make('download_persetujuan')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn ($record) => $record->izinPersetujuan?->file_persetujuan ? Storage::disk('public')->url($record->izinPersetujuan->file_persetujuan) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->izinPersetujuan?->file_persetujuan)),
            ])
            ->persistFiltersInSession()
            ->defaultSort('tbl_izin_persetujuan.date_updated', 'desc')
            ->striped()
            ->paginated([15, 30, 50]);
    }
}
