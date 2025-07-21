<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KotakabResource\Pages;
use App\Filament\Resources\KotakabResource\RelationManagers;
use App\Models\Kotakab;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KotakabResource extends Resource
{
    protected static ?string $model = Kotakab::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $slug = 'kotakab';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kota/Kabupaten';

    protected static ?int $navigationSort = 5;


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin']);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_kotakab')
                    ->label('Kota/Kabupaten')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ])
                    ->default('aktif')
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_kotakab')
                    ->label('Kota/Kabupaten')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Aktif')
                    // data from database is 'aktif' or 'nonaktif'
                    ->state(fn($record) => $record->status === 'aktif')
                    ->updateStateUsing(function ($record, $state) {
                        $record->update([
                            'status' => $state ? 'aktif' : 'nonaktif'
                        ]);
                    })
                    ->sortable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                    ]),
                ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKotakabs::route('/'),
            'create' => Pages\CreateKotakab::route('/create'),
            'edit' => Pages\EditKotakab::route('/{record}/edit'),
        ];
    }
}
