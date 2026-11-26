<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanGeotaggingResource\Pages;
use App\Models\LaporanGeotagging;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanGeotaggingResource extends Resource
{
    protected static ?string $model = LaporanGeotagging::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $label = 'Laporan Geotagging';

    protected static ?string $slug = 'laporan-geotagging';

    protected static ?string $pluralLabel = 'Laporan Geotagging';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->check() && (auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2', 'user', 'dokter']));
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()->hasRole('dokter');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! auth()->user()->hasRole('dokter');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! auth()->user()->hasRole('dokter');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Klinik/RS')
                    // Use the `relationship` method with `modifyQueryUsing` for efficient and clean query modifications.
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, ?User $record) {
                            // This single query correctly handles both 'create' and 'edit' scenarios.
                            return $query->whereHas('roles', fn (Builder $q) => $q->where('name', 'user'))
                                ->where(function (Builder $q) use ($record) {
                                    // It selects users who do not have a related 'laporanGeotagging',
                                    $q->whereDoesntHave('laporanGeotagging');

                                    // OR, if editing a record, it ensures the currently assigned user
                                    // is always included in the options list.
                                    if ($record) {
                                        $q->orWhere('id', $record->id);
                                    }
                                });
                        }
                    )
                    ->searchable()
                    ->required()
                    ->hiddenOn('edit') // Hide on edit, as the user should not change
                    // You only need `disabled`. If a user doesn't have the role, the field will be visible but not editable.
                    // The previous `visible` check was redundant with this logic.
                    ->visible(fn () => auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2', 'dokter'])),

                Forms\Components\TextInput::make('google_drive_link')
                    ->label('Link Google Drive')
                    ->url()
                    ->required()
                    ->rules(['url', 'required'])
                    ->helperText('Pastikan akses Google Drive Anda dapat diakses publik atau oleh orang yang memiliki link.')
                    ->placeholder('https://drive.google.com/...'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2', 'dokter'])),
                Tables\Columns\TextColumn::make('google_drive_link')
                    ->label('Link Google Drive')
                    ->limit(60)
                    ->url(fn ($record) => $record->google_drive_link),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => ! auth()->user()->hasRole('dokter')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => ! auth()->user()->hasRole('dokter')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Klinik/RS')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2', 'dokter'])),
                // ...
            ], FiltersLayout::AboveContent)
            ->modifyQueryUsing(function ($query) {
                if (! auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2', 'dokter'])) {
                    $query->where('user_id', auth()->id());
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanGeotaggings::route('/'),
            'create' => Pages\CreateLaporanGeotagging::route('/create'),
            'edit' => Pages\EditLaporanGeotagging::route('/{record}/edit'),
        ];
    }
}
