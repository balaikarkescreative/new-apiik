<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiwayatBlokirFaskesResource\Pages;
use App\Models\RiwayatBlokirFaskes;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatBlokirFaskesResource extends Resource
{
    protected static ?string $model = RiwayatBlokirFaskes::class;

    protected static ?string $navigationLabel = 'Riwayat Blokir';

    protected static ?string $modelLabel = 'Riwayat Blokir';

    protected static ?string $pluralModelLabel = 'Riwayat Blokir Faskes';

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationGroup = 'Pengaturan User';

    protected static ?int $navigationSort = 96;

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
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal & Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Faskes / Klinik')
                    ->getStateUsing(function ($record) {
                        $faskes = $record->user?->name ?? 'Faskes Dihapus';
                        $kotakab = $record->user?->kotakab ?? '-';

                        return '<div>'.
                            '<div class="font-medium text-sm text-gray-900 dark:text-white">'.e($faskes).'</div>'.
                            '<div class="text-xs text-gray-500 dark:text-gray-400">'.e($kotakab).'</div>'.
                            '</div>';
                    })
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('kotakab', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('users as faskes_users', 'riwayat_blokir_faskes.user_id', '=', 'faskes_users.id')
                            ->orderBy('faskes_users.name', $direction)
                            ->select('riwayat_blokir_faskes.*');
                    }),

                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'blokir' => 'Diblokir',
                        'buka_blokir' => 'Buka Blokir',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'blokir' => 'danger',
                        'buka_blokir' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('alasan')
                    ->label('Alasan / Keterangan')
                    ->searchable()
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn ($state) => strlen($state) > 80 ? $state : null),

                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Dilakukan Oleh')
                    ->formatStateUsing(fn ($state) => $state ?: 'Admin / Sistem')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Rentang Tanggal'),

                SelectFilter::make('action')
                    ->label('Status Aksi')
                    ->options([
                        'blokir' => 'Diblokir',
                        'buka_blokir' => 'Buka Blokir',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Faskes / Klinik')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return User::whereHas('roles', fn ($q) => $q->where('name', 'user'))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),
            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->modalHeading('Detail Riwayat Blokir Faskes'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export Data (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('date_from')
                                    ->label('Dari Tanggal')
                                    ->native(false),
                                \Filament\Forms\Components\DatePicker::make('date_until')
                                    ->label('Sampai Tanggal')
                                    ->native(false),
                            ]),
                        \Filament\Forms\Components\Select::make('action')
                            ->label('Status Aksi')
                            ->options([
                                'all' => 'Semua',
                                'blokir' => 'Diblokir',
                                'buka_blokir' => 'Buka Blokir',
                            ])
                            ->default('all'),
                        \Filament\Forms\Components\Select::make('user_id')
                            ->label('Faskes / Klinik')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return ['all' => 'Semua Faskes'] + User::whereHas('roles', fn ($q) => $q->where('name', 'user'))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default('all'),
                    ])
                    ->action(function (array $data): StreamedResponse {
                        return static::exportToExcel($data);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Terpilih (CSV)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(fn (Collection $records) => static::streamCsvResponse($records)),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Riwayat Blokir')
            ->emptyStateDescription('Belum ada data riwayat pemblokiran atau pembukaan blokir faskes.')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->striped()
            ->paginated([15, 30, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Riwayat Blokir')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu Aksi')
                            ->dateTime('d F Y, H:i:s'),

                        Infolists\Components\TextEntry::make('action')
                            ->label('Aksi')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'blokir' => 'Diblokir',
                                'buka_blokir' => 'Buka Blokir',
                                default => ucfirst(str_replace('_', ' ', $state)),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'blokir' => 'danger',
                                'buka_blokir' => 'success',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Nama Faskes / Klinik'),

                        Infolists\Components\TextEntry::make('user.kotakab')
                            ->label('Kota / Kabupaten')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('user.alamat')
                            ->label('Alamat Faskes')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('admin.name')
                            ->label('Dilakukan Oleh')
                            ->formatStateUsing(fn ($state) => $state ?: 'Admin / Sistem')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('alasan')
                            ->label('Alasan / Keterangan')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => nl2br(e($state)))
                            ->html(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function exportToExcel(array $filters): StreamedResponse
    {
        $query = RiwayatBlokirFaskes::query()->with(['user', 'admin'])->latest('created_at');

        if (! empty($filters['action']) && $filters['action'] !== 'all') {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id']) && $filters['user_id'] !== 'all') {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_until'])) {
            $query->whereDate('created_at', '<=', $filters['date_until']);
        }

        return static::streamCsvResponse($query->get());
    }

    public static function streamCsvResponse($data): StreamedResponse
    {
        $filename = 'riwayat_blokir_faskes_'.date('Y-m-d_H-i-s').'.csv';

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
                'No.',
                'Tanggal & Waktu',
                'Nama Faskes / Klinik',
                'Kota / Kabupaten',
                'Alamat Faskes',
                'Aksi',
                'Alasan / Keterangan',
                'Dilakukan Oleh',
            ]);

            $no = 1;
            foreach ($data as $row) {
                fputcsv($output, [
                    $no++,
                    $row->created_at?->format('d/m/Y H:i:s'),
                    $row->user?->name ?? 'Faskes Dihapus',
                    $row->user?->kotakab ?? '-',
                    $row->user?->alamat ?? '-',
                    $row->getActionLabel(),
                    $row->alasan ?? '-',
                    $row->admin?->name ?? 'Admin / Sistem',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiwayatBlokirFaskes::route('/'),
        ];
    }
}
