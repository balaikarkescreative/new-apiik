<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceClosureResource\Pages;
use App\Filament\Resources\ServiceClosureResource\RelationManagers;
use App\Models\ServiceClosure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class ServiceClosureResource extends Resource
{
    protected static ?string $model = ServiceClosure::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'Kelola Layanan';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $slug = 'layanan';
    protected static ?int $navigationSort = 901;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin']);
    }

    public static function canCreate(): bool
    {
        return false; // Nonaktifkan create
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Nonaktifkan edit
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Nonaktifkan delete
    }

    public static function canDeleteAny(): bool
    {
        return false; // Nonaktifkan bulk delete
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak diperlukan karena resource hanya untuk toggle status
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service_name')
                    ->label('Jenis Layanan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('closure_reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->placeholder('Tidak ada alasan')
                    ->visible(fn ($record) => $record && $record->is_closed),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_closed')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => !$record->is_closed) // Balik logic: true = buka, false = tutup
                    ->beforeStateUpdated(function ($record, $state) {
                        // $state true = akan buka layanan, false = akan tutup layanan
                        return true;
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        if ($state) {
                            // Toggle ON = Buka layanan
                            ServiceClosure::openService($record->service_type);
                        } else {
                            // Toggle OFF = Tutup layanan
                            ServiceClosure::closeService($record->service_type, null);
                        }
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Jenis Layanan')
                    ->options(ServiceClosure::SERVICE_TYPES),
                Tables\Filters\TernaryFilter::make('is_closed')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Tutup')
                    ->falseLabel('Buka'),
            ])
            ->actions([
                // Tidak perlu action lagi karena sudah menggunakan ToggleColumn
            ])
            ->bulkActions([
                // Hapus semua bulk actions
            ])
            ->defaultSort('service_type');
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
            'index' => Pages\ListServiceClosures::route('/'),
            // Hapus create dan edit pages
        ];
    }
}
