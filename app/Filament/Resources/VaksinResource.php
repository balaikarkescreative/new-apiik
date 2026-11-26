<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VaksinResource\Pages;
use App\Models\Vaksin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VaksinResource extends Resource
{
    protected static ?string $model = Vaksin::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Jenis Vaksin';

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $pluralLabel = 'Jenis Vaksin';
    protected static ?string $label = 'Jenis Vaksin';
    protected static ?string $slug = 'jenis-vaksin';

    protected static ?int $navigationSort = 9;


    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }/*  */

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('jenis_vaksin')
                    ->label('Jenis Vaksin')
                    ->required()
                    ->maxLength(128),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('jenis_vaksin')->label('Jenis Vaksin')->searchable(),
                // Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
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
            'index' => Pages\ListVaksins::route('/'),
            'create' => Pages\CreateVaksin::route('/create'),
            'edit' => Pages\EditVaksin::route('/{record}/edit'),
        ];
    }
}
