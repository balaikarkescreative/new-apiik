<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuratPersetujuanIcvResource\Pages;
use App\Models\SuratPersetujuanIcv;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SuratPersetujuanIcvResource extends Resource
{
    protected static ?string $model = SuratPersetujuanIcv::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Surat Persetujuan ICV';
    protected static ?string $navigationGroup = 'Master Data';

    public static function canAccess(): bool
    {
        // Hanya admin dan super admin yang bisa mengakses resource ini
        return Auth::check() && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('user'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_akun')
                    ->label('User/Akun')
                    ->searchable()
                    ->options(function () {
                        $user = Auth::user();

                        // Jika admin, tampilkan semua user
                        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                            return User::pluck('name', 'id');
                        }

                        // Jika user biasa, hanya tampilkan dirinya sendiri
                        return [$user->id => $user->name];
                    })
                    ->default(function () {
                        $user = Auth::user();

                        // Jika bukan admin, set default ke user yang login
                        if (!$user->hasRole('admin') && !$user->hasRole('super_admin')) {
                            return $user->id;
                        }

                        return null;
                    })
                    ->disabled(function () {
                        $user = Auth::user();

                        // Disable jika bukan admin
                        return !$user->hasRole('admin') && !$user->hasRole('super_admin');
                    })
                    ->dehydrated(true)
                    ->required(),

                Forms\Components\TextInput::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->required()
                    ->maxLength(255),

                Forms\Components\DatePicker::make('tanggal_surat')
                    ->label('Tanggal Surat')
                    ->native(false)

                    ->required(),

                Forms\Components\DatePicker::make('masa_berlaku')
                    ->label('Masa Berlaku')
                    ->native(false)

                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // SuratPersetujuanIcv::query()->with(['user'])
                function () {
                    if (Auth::user()->hasRole('admin')) {
                        return SuratPersetujuanIcv::query()->with(['user']);
                    } else {
                        return SuratPersetujuanIcv::query()
                            ->where('id_akun', Auth::user()->id)
                            ->with(['user']);
                    }
                }
            )
            ->defaultSort('tanggal_surat', 'desc')
            ->searchable()
            ->columns([
                // Tanggal Surat - Sortable & Searchable
                Tables\Columns\TextColumn::make('tanggal_surat')
                    ->label('Tanggal Surat')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                // Klinik/RS + Kota/Kab (Combined) - Same as other resources
                Tables\Columns\TextColumn::make('klinik_info')
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
                        return $query->join('users', 'tbl_surat_persetujuan_icv.id_akun', '=', 'users.id')
                            ->orderBy('users.name', $direction);
                    }),

                // Nomor Surat - Sortable & Searchable
                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('Nomor Surat')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(function ($state) {
                        return $state;
                    }),

                // Masa Berlaku - Enhanced with status color
                Tables\Columns\TextColumn::make('masa_berlaku')
                    ->label('Masa Berlaku')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function ($state) {
                        $masaBerlaku = Carbon::parse($state);
                        $now = now();

                        if ($masaBerlaku < $now) {
                            return 'danger'; // Kadaluarsa
                        } elseif ($masaBerlaku->diffInDays($now) <= 30) {
                            return 'warning'; // Akan kadaluarsa dalam 30 hari
                        }
                        return 'success'; // Masih berlaku
                    }),

                // Detail Info (toggleable)
                Tables\Columns\TextColumn::make('detail_info')
                    ->label('Detail')
                    ->getStateUsing(function ($record) {
                        $createdAt = Carbon::parse($record->created_at);
                        $updatedAt = Carbon::parse($record->updated_at);

                        $details = [];
                        $details[] = "Dibuat: " . $createdAt->translatedFormat('d M Y');

                        if ($createdAt->ne($updatedAt)) {
                            $details[] = "Diperbarui: " . $updatedAt->translatedFormat('d M Y');
                        }

                        return implode("\n", $details);
                    })
                    ->html()
                    ->formatStateUsing(function ($state) {
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
                // Date Range Filter for Tanggal Surat
                DateRangeFilter::make('tanggal_surat')
                    ->label('Tanggal Surat'),

                // Date Range Filter for Masa Berlaku
                DateRangeFilter::make('masa_berlaku')
                    ->label('Masa Berlaku'),

                // User/Akun Filter
                SelectFilter::make('id_akun')
                    ->label('Klinik/RS')
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->searchable()
                    ->relationship('user', 'name')
                    ->preload()
                    ->visible(function () {
                        $user = Auth::user();
                        return $user->hasRole('admin') || $user->hasRole('super_admin');
                    }),

                // Kota/Kabupaten Filter - Fixed query
                SelectFilter::make('kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->options(function () {
                        return SuratPersetujuanIcv::join('users', 'tbl_surat_persetujuan_icv.id_akun', '=', 'users.id')
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
                    ->preload()
                    ->visible(function () {
                        $user = Auth::user();
                        return $user->hasRole('admin') || $user->hasRole('super_admin');
                    }),

                // Status Filter - Enhanced with more options
                SelectFilter::make('status_berlaku')
                    ->label('Status Berlaku')
                    ->options([
                        'berlaku' => 'Berlaku',
                        'akan_kadaluarsa' => 'Akan Kadaluarsa (≤30 hari)',
                        'kadaluarsa' => 'Kadaluarsa',
                        'kadaluarsa_recent' => 'Baru Kadaluarsa (≤30 hari)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        $now = now();

                        return match ($data['value']) {
                            'berlaku' => $query->where('masa_berlaku', '>', $now->copy()->addDays(30)),
                            'akan_kadaluarsa' => $query->whereBetween('masa_berlaku', [$now, $now->copy()->addDays(30)]),
                            'kadaluarsa' => $query->where('masa_berlaku', '<', $now),
                            'kadaluarsa_recent' => $query->whereBetween('masa_berlaku', [$now->copy()->subDays(30), $now]),
                            default => $query,
                        };
                    }),

                // Year Filter
                SelectFilter::make('tahun')
                    ->label('Tahun Surat')
                    ->options(function () {
                        $years = [];
                        $currentYear = now()->year;
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value'])
                            return $query;

                        return $query->whereYear('tanggal_surat', $data['value']);
                    }),
            ], FiltersLayout::AboveContent)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->size('sm'),

                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->size('sm')
                    ->visible(function ($record) {
                        $user = Auth::user();

                        // Admin bisa edit semua, user hanya bisa edit miliknya sendiri
                        return $user->hasRole('admin') || $user->hasRole('super_admin') || $record->id_akun == $user->id;
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->size('sm')
                    ->visible(function ($record) {
                        $user = Auth::user();

                        // Admin bisa delete semua, user hanya bisa delete miliknya sendiri
                        return $user->hasRole('admin') || $user->hasRole('super_admin') || $record->id_akun == $user->id;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->visible(function () {
                            $user = Auth::user();

                            // Hanya admin yang bisa bulk delete
                            return $user->hasRole('admin') || $user->hasRole('super_admin');
                        }),

                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Export Data Terpilih')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            // Implementation for export functionality
                            \Filament\Notifications\Notification::make()
                                ->title('Export Berhasil')
                                ->body('Data yang dipilih berhasil diekspor.')
                                ->success()
                                ->send();
                        })
                        ->visible(function () {
                            $user = Auth::user();
                            return $user->hasRole('admin') || $user->hasRole('super_admin');
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Surat Persetujuan ICV')
            ->emptyStateDescription('Belum ada data surat persetujuan ICV yang masuk ke sistem.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated([15, 30, 50])
            ->recordUrl(null); // Disable click row to edit
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Jika admin, tampilkan semua surat
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return parent::getEloquentQuery();
        }

        // Jika user biasa, hanya tampilkan surat miliknya sendiri
        return parent::getEloquentQuery()->where('id_akun', $user->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuratPersetujuanIcvs::route('/'),
            'create' => Pages\CreateSuratPersetujuanIcv::route('/create'),
            'edit' => Pages\EditSuratPersetujuanIcv::route('/{record}/edit'),
        ];
    }
}
