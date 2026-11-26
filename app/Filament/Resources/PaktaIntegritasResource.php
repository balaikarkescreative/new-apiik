<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaktaIntegritasResource\Pages;
use App\Models\PaktaIntegritas;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaktaIntegritasResource extends Resource
{
    protected static ?string $model = PaktaIntegritas::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $label = 'Pakta Integritas';

    protected static ?string $slug = 'pakta-integritas';

    protected static ?string $pluralLabel = 'Pakta Integritas';

    protected static ?int $navigationSort = 5;

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
                                    // It selects users who do not have a related 'paktaIntegritas',
                                    $q->whereDoesntHave('paktaIntegritas');

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

                Forms\Components\FileUpload::make('file')
                    ->label('File Pakta Integritas')
                    ->directory('pakta-integritas')
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(1024 * 3) // 3MB
                    ->required()
                    ->helperText('Upload file pakta integritas dalam format PDF, DOC, atau DOCX (maksimal 3MB).')
                    ->downloadable()
                    ->previewable()
                    ->storeFileNamesIn('original_filename'),
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
                Tables\Columns\TextColumn::make('file')
                    ->label('File Pakta Integritas')
                    ->formatStateUsing(fn ($state) => $state ? basename($state) : 'Tidak ada file')
                    ->url(fn ($record) => $record->file ? asset('storage/'.$record->file) : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
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
            'index' => Pages\ListPaktaIntegritas::route('/'),
            'create' => Pages\CreatePaktaIntegritas::route('/create'),
            'edit' => Pages\EditPaktaIntegritas::route('/{record}/edit'),
        ];
    }
}
