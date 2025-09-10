<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanPemanfaatanIcvResource\Pages;
use App\Models\LaporanPemanfaatanIcv;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LaporanPemanfaatanIcvResource extends Resource
{
    protected static ?string $model = LaporanPemanfaatanIcv::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Pemanfaatan ICV';

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('user'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_klinik')
                    ->label('Klinik')
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
                        if (! $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                            return $user->id;
                        }

                        return null;
                    })
                    ->disabled(function () {
                        $user = Auth::user();

                        // Disable jika bukan admin (user hanya bisa pilih dirinya sendiri)
                        return ! $user->hasRole('admin') && ! $user->hasRole('super_admin');
                    })
                    ->searchable()
                    ->dehydrated(true) // Pastikan field ini tetap dikirim meski disabled
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->native(false)

                    ->required(),
                Forms\Components\TextInput::make('judul_laporan')
                    ->label('Judul Laporan')
                    ->required(),
                Forms\Components\FileUpload::make('file')
                    ->label('File')
                    ->directory('laporan-pemanfaatan-icv')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(1024 * 3) // Maksimal 3MB
                    ->helperText('Format PDF, maksimal 3MB')
                    ->getUploadedFileNameForStorageUsing(function ($file) {
                        return 'Laporan Pemanfaatan ICV - '.now()->format('Y-m-d').'.'.$file->getClientOriginalExtension();
                    })
                    ->required()
                    ->downloadable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Klinik')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('judul_laporan')
                    ->label('Judul Laporan')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('file')
                    ->label('File')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            return 'Download';
                        }

                        return 'Tidak ada file';
                    })
                    ->url(function ($record) {
                        if ($record->file) {
                            return asset('storage/'.$record->file);
                        }

                        return null;
                    })
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_klinik')
                    ->label('Klinik')
                    ->relationship('user', 'name')
                    ->visible(function () {
                        $user = Auth::user();

                        return $user->hasRole('admin') || $user->hasRole('super_admin');
                    }),

                DateRangeFilter::make('tanggal')
                    ->label('Tanggal'),

            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();

                        return $user->hasRole('admin') || $record->id_klinik == $user->id;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();

                        return $user->hasRole('admin') || $record->id_klinik == $user->id;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(function () {
                        $user = Auth::user();

                        return $user->hasRole('admin');
                    }),
            ])
            ->recordUrl(null) // Disable click row to edit
            ->defaultSort('tanggal', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Jika admin, tampilkan semua laporan
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return parent::getEloquentQuery();
        }

        // Jika user biasa, hanya tampilkan laporan miliknya sendiri
        return parent::getEloquentQuery()->where('id_klinik', $user->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanPemanfaatanIcvs::route('/'),
            'create' => Pages\CreateLaporanPemanfaatanIcv::route('/create'),
            'edit' => Pages\EditLaporanPemanfaatanIcv::route('/{record}/edit'),
        ];
    }
}
