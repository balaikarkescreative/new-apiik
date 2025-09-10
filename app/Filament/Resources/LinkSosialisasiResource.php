<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LinkSosialisasiResource\Pages;
use App\Models\LinkSosialisasi;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class LinkSosialisasiResource extends Resource
{
    protected static ?string $model = LinkSosialisasi::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $label = 'Link Sosialisasi';
    protected static ?string $slug = 'link-sosialisasi';
    protected static ?string $pluralLabel = 'Link Sosialisasi';
    protected static ?int $navigationSort = 4;

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
                            return $query->whereHas('roles', fn(Builder $q) => $q->where('name', 'user'))
                                ->where(function (Builder $q) use ($record) {
                                // It selects users who do not have a related 'linkSosialisasi',
                                $q->whereDoesntHave('linkSosialisasi');

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
                    ->visible(fn() => auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2'])),

                Forms\Components\TextInput::make('link')
                    ->label('Link Sosialisasi')
                    ->url()
                    ->required()
                    ->rules(['url', 'required'])
                    ->helperText('Masukkan link sosialisasi yang dapat diakses.')
                    ->placeholder('https://...')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Klinik/RS')
                    ->searchable()
                    ->visible(fn() => auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2'])),
                Tables\Columns\TextColumn::make('link')
                    ->label('Link Sosialisasi')
                    ->limit(60)
                    ->url(fn($record) => $record->link),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Klinik/RS')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->visible(fn() => auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2'])),
                // ...
            ], FiltersLayout::AboveContent)
            ->modifyQueryUsing(function ($query) {
                if (!auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2'])) {
                    $query->where('user_id', auth()->id());
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLinkSosialisasis::route('/'),
            'create' => Pages\CreateLinkSosialisasi::route('/create'),
            'edit' => Pages\EditLinkSosialisasi::route('/{record}/edit'),
        ];
    }
}
