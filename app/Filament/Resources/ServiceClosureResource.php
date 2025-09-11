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
    protected static ?string $title = 'Kelola Layanan';
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
                Tables\Columns\BadgeColumn::make('status_text')
                    ->label('Status')
                    ->color(fn ($record) => $record->status_color)
                    ->sortable(['is_closed']),
                Tables\Columns\TextColumn::make('closure_reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->placeholder('Tidak ada alasan')
                    ->visible(fn ($record) => $record && $record->is_closed),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_closed ? 'Buka Layanan' : 'Tutup Layanan')
                    ->icon(fn ($record) => $record->is_closed ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn ($record) => $record->is_closed ? 'success' : 'danger')
                    ->form([
                        Forms\Components\Textarea::make('closure_reason')
                            ->label('Alasan (Opsional)')
                            ->placeholder('Masukkan alasan menutup layanan...')
                            ->visible(fn ($record) => !$record->is_closed)
                            ->maxLength(1000),
                    ])
                    ->action(function ($record, $data) {
                        if ($record->is_closed) {
                            ServiceClosure::openService($record->service_type);
                        } else {
                            ServiceClosure::closeService($record->service_type, $data['closure_reason'] ?? null);
                        }
                    }),
                // Hapus EditAction dan DeleteAction
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
