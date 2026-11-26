<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginHistoryResource\Pages;
use App\Models\LoginHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoginHistoryResource extends Resource
{
    protected static ?string $model = LoginHistory::class;

    protected static ?string $navigationLabel = 'Riwayat Login';

    protected static ?string $modelLabel = 'Riwayat Login';

    protected static ?string $pluralModelLabel = 'Riwayat Login';

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'Pengaturan User';

    protected static ?int $navigationSort = 95;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('login_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('login_at')
                    ->label('Waktu Login')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'admin' => 'danger',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match (strtolower((string) $state)) {
                        'admin' => 'Admin',
                        'user' => 'RS / Klinik',
                        default => $state ? ucfirst($state) : '-',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                        'warning' => 'master_password',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'success',
                        'heroicon-m-x-circle' => 'failed',
                        'heroicon-m-key' => 'master_password',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Berhasil',
                        'failed' => 'Gagal',
                        'master_password' => 'Master Password',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->copyable()
                    ->copyMessage('Alamat IP disalin')
                    ->searchable(),

                Tables\Columns\TextColumn::make('location_display')
                    ->label('Lokasi')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (! $state || strlen((string) $state) <= 40) {
                            return null;
                        }

                        return (string) $state;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->where('address', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('region', 'like', "%{$search}%")
                                ->orWhere('country', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('device_display')
                    ->label('Perangkat & Browser')
                    ->toggleable(),
            ])
            ->filters([
                DateRangeFilter::make('login_at')
                    ->label('Rentang Waktu'),

                SelectFilter::make('status')
                    ->label('Status Login')
                    ->options([
                        'success' => 'Berhasil',
                        'failed' => 'Gagal',
                        'master_password' => 'Master Password',
                    ]),

                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'RS / Klinik',
                    ]),

                TernaryFilter::make('is_gps')
                    ->label('Tipe Lokasi')
                    ->placeholder('Semua')
                    ->trueLabel('GPS Perangkat')
                    ->falseLabel('Estimasi IP'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date_from')
                                    ->label('Dari Tanggal')
                                    ->native(false),
                                Forms\Components\DatePicker::make('date_until')
                                    ->label('Sampai Tanggal')
                                    ->native(false),
                            ]),
                        Forms\Components\Select::make('status')
                            ->label('Status Login')
                            ->options([
                                'all' => 'Semua',
                                'success' => 'Berhasil',
                                'failed' => 'Gagal',
                                'master_password' => 'Master Password',
                            ])
                            ->default('all'),
                        Forms\Components\Select::make('role')
                            ->label('Role Pengguna')
                            ->options([
                                'all' => 'Semua',
                                'admin' => 'Admin',
                                'user' => 'RS / Klinik',
                            ])
                            ->default('all'),
                    ])
                    ->action(function (array $data): StreamedResponse {
                        return static::exportToExcel($data);
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->striped()
            ->paginated([15, 30, 50])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->size('sm')
                    ->color('info')
                    ->modalHeading('Rincian Riwayat Login')
                    ->infolist(fn (Infolist $infolist): Infolist => static::infolist($infolist)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected')
                    ->label('Export Terpilih')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records): StreamedResponse {
                        return static::exportCollectionToExcel($records);
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Sesi Login')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('login_at')
                                ->label('Waktu Login')
                                ->dateTime('d F Y, H:i:s T'),

                            Infolists\Components\TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->colors([
                                    'success' => 'success',
                                    'danger' => 'failed',
                                    'warning' => 'master_password',
                                ])
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'success' => 'Berhasil',
                                    'failed' => 'Gagal',
                                    'master_password' => 'Master Password Override',
                                    default => ucfirst($state),
                                }),

                            Infolists\Components\TextEntry::make('failure_reason')
                                ->label('Alasan Gagal')
                                ->placeholder('-')
                                ->visible(fn (?LoginHistory $record): bool => $record?->status === 'failed'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Informasi Pengguna')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->label('Nama Pengguna')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('login_identifier')
                                ->label('Username / Email Input')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('role')
                                ->label('Role')
                                ->badge()
                                ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                                    'admin' => 'danger',
                                    'user' => 'info',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (?string $state): string => match (strtolower((string) $state)) {
                                    'admin' => 'Admin',
                                    'user' => 'RS / Klinik',
                                    default => $state ? ucfirst($state) : '-',
                                }),
                        ]),
                    ]),

                Infolists\Components\Section::make('Jaringan & Lokasi')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('ip_address')
                                ->label('Alamat IP')
                                ->copyable(),

                            Infolists\Components\TextEntry::make('is_gps')
                                ->label('Sumber Lokasi')
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'GPS Perangkat (Presisi)' : 'Estimasi Alamat IP'),

                            Infolists\Components\TextEntry::make('country_code')
                                ->label('Kode Negara')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('address')
                                ->label('Alamat Fisik (Reverse Geocoding)')
                                ->columnSpanFull()
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('location_display')
                                ->label('Ringkasan Lokasi')
                                ->columnSpanFull(),

                            Infolists\Components\TextEntry::make('latitude')
                                ->label('Latitude')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('longitude')
                                ->label('Longitude')
                                ->placeholder('-'),

                            Infolists\Components\Actions::make([
                                Infolists\Components\Actions\Action::make('open_in_google_maps')
                                    ->label('Buka di Google Maps')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->color('primary')
                                    ->url(fn (LoginHistory $record): ?string => $record->google_maps_url)
                                    ->openUrlInNewTab()
                                    ->visible(fn (LoginHistory $record): bool => (bool) $record->google_maps_url),
                            ]),
                        ]),
                    ]),

                Infolists\Components\Section::make('Perangkat & Browser')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('device')
                                ->label('Tipe Perangkat')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('platform')
                                ->label('Sistem Operasi')
                                ->placeholder('-'),

                            Infolists\Components\TextEntry::make('browser')
                                ->label('Browser')
                                ->placeholder('-'),
                        ]),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('Raw User Agent')
                            ->columnSpanFull()
                            ->copyable(),
                    ]),
            ]);
    }

    public static function exportToExcel(array $filters): StreamedResponse
    {
        $query = LoginHistory::query()->latest('login_at');

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('login_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_until'])) {
            $query->whereDate('login_at', '<=', $filters['date_until']);
        }

        return static::streamCsvResponse($query->get());
    }

    public static function exportCollectionToExcel(Collection $records): StreamedResponse
    {
        return static::streamCsvResponse($records);
    }

    protected static function streamCsvResponse($data): StreamedResponse
    {
        $filename = 'riwayat_login_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($data) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, [
                'Waktu Login',
                'Nama Pengguna',
                'Username / Email Input',
                'Role',
                'Status',
                'Alasan Gagal',
                'Tipe Lokasi',
                'Alamat Lengkap',
                'Latitude',
                'Longitude',
                'Alamat IP',
                'Kota',
                'Provinsi / Region',
                'Negara',
                'Perangkat',
                'Sistem Operasi',
                'Browser',
                'User Agent',
            ]);

            foreach ($data as $row) {
                fputcsv($output, [
                    $row->login_at?->format('Y-m-d H:i:s'),
                    $row->name ?: '-',
                    $row->login_identifier ?: '-',
                    $row->role ?: '-',
                    match ($row->status) {
                        'success' => 'Berhasil',
                        'failed' => 'Gagal',
                        'master_password' => 'Master Password',
                        default => $row->status,
                    },
                    $row->failure_reason ?: '-',
                    $row->is_gps ? 'GPS Perangkat' : 'IP Geolocation',
                    $row->address ?: ($row->location_display ?: '-'),
                    $row->latitude ?: '-',
                    $row->longitude ?: '-',
                    $row->ip_address ?: '-',
                    $row->city ?: '-',
                    $row->region ?: '-',
                    $row->country ?: '-',
                    $row->device ?: '-',
                    $row->platform ?: '-',
                    $row->browser ?: '-',
                    $row->user_agent ?: '-',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginHistories::route('/'),
        ];
    }
}
