<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Resources\FaqResource\RelationManagers;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Kelola FAQ';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        // Hanya admin yang bisa akses kelola admin
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi FAQ')
                    ->description('Masukkan pertanyaan dan jawaban yang akan ditampilkan')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->label('Pertanyaan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan pertanyaan yang sering diajukan')
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                $set('question', ucfirst($state));
                            }),

                        Forms\Components\RichEditor::make('answer')
                            ->label('Jawaban')
                            ->required()
                            ->placeholder('Berikan jawaban yang jelas dan lengkap')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'link',
                            ])
                            ->maxLength(5000),
                    ]),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Aktifkan untuk menampilkan FAQ di halaman publik'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label('Pertanyaan')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->tooltip(function ($record): ?string {
                        $question = $record->question;
                        return strlen($question) > 60 ? $question : null;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (Faq $record): string => $record->is_active ? 'Aktif' : 'Tidak Aktif'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Hanya Aktif')
                    ->falseLabel('Hanya Tidak Aktif')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('moveUp')
                    ->label('Naik')
                    ->icon('heroicon-s-arrow-up')
                    ->color('success')
                    ->size('sm')
                    ->action(function (Faq $record): void {
                        $higherRecord = Faq::where('order', '<', $record->order)
                            ->orderBy('order', 'desc')
                            ->first();
                        
                        if ($higherRecord) {
                            $tempOrder = $record->order;
                            $record->update(['order' => $higherRecord->order]);
                            $higherRecord->update(['order' => $tempOrder]);
                        }
                    })
                    ->visible(fn (Faq $record): bool => 
                        Faq::where('order', '<', $record->order)->exists()
                    )
                    ->tooltip('Pindahkan ke atas'),

                Tables\Actions\Action::make('moveDown')
                    ->label('Turun')
                    ->icon('heroicon-s-arrow-down')
                    ->color('warning')
                    ->size('sm')
                    ->action(function (Faq $record): void {
                        $lowerRecord = Faq::where('order', '>', $record->order)
                            ->orderBy('order', 'asc')
                            ->first();
                        
                        if ($lowerRecord) {
                            $tempOrder = $record->order;
                            $record->update(['order' => $lowerRecord->order]);
                            $lowerRecord->update(['order' => $tempOrder]);
                        }
                    })
                    ->visible(fn (Faq $record): bool => 
                        Faq::where('order', '>', $record->order)->exists()
                    )
                    ->tooltip('Pindahkan ke bawah'),

                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_active' => true]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_active' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('order', 'asc')
            ->paginated([10, 25, 50, 100])
            ->poll('10s')
            ->emptyStateHeading('Belum ada FAQ')
            ->emptyStateDescription('Buat FAQ pertama untuk membantu pengguna menemukan jawaban.')
            ->emptyStateIcon('heroicon-o-question-mark-circle')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat FAQ Baru'),
                Tables\Actions\Action::make('bulk-create')
                    ->label('Tambah FAQ Massal')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn (): string => static::getUrl('bulk-create')),
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
            'index' => Pages\ListFaqs::route('/'),
            // 'create' => Pages\CreateFaq::route('/create'),
            'create' => Pages\BulkCreateFaqs::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
