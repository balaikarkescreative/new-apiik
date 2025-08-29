<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoPanduanResource\Pages;
use App\Filament\Resources\VideoPanduanResource\RelationManagers;
use App\Models\VideoPanduan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class VideoPanduanResource extends Resource
{
    protected static ?string $model = VideoPanduan::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationLabel = 'Kelola Video Sosialisasi';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Video Sosialisasi';

    protected static ?string $pluralModelLabel = 'Video Sosialisasi';

    public static function canAccess(): bool
    {
        // Hanya admin yang bisa akses kelola video sosialisasi
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Video')
                    ->description('Upload dan kelola video sosialisasi untuk pengguna')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Video')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan judul video sosialisasi')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Jelaskan isi dari video sosialisasi ini')
                            ->columnSpanFull()
                            ->rows(3),

                        Forms\Components\FileUpload::make('video_path')
                            ->label('File Video')
                            ->required()
                            ->disk('public')
                            ->directory('video-panduan')
                            ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov', 'video/wmv'])
                            ->maxSize(1024 * 100) // 100MB
                            ->columnSpanFull()
                            ->helperText('Format yang didukung: MP4, AVI, MOV, WMV. Maksimal 100MB')
                            ->downloadable()
                            ->previewable(),
                    ]),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Aktifkan untuk menampilkan video di halaman publik'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Video')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function ($record): ?string {
                        $title = $record->title;
                        return strlen($title) > 50 ? $title : null;
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(100)
                    ->tooltip(function ($record): ?string {
                        $desc = $record->description;
                        return strlen($desc ?? '') > 100 ? $desc : null;
                    }),

                Tables\Columns\TextColumn::make('video_path')
                    ->label('File Video')
                    ->formatStateUsing(function ($state) {
                        return $state ? basename($state) : '-';
                    })
                    ->tooltip(function ($record): ?string {
                        return $record->video_size ?? 'Ukuran tidak diketahui';
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record): string => $record->is_active ? 'Aktif' : 'Tidak Aktif'),

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
                    ->action(function (VideoPanduan $record): void {
                        $higherRecord = VideoPanduan::where('order', '<', $record->order)
                            ->orderBy('order', 'desc')
                            ->first();
                        
                        if ($higherRecord) {
                            $tempOrder = $record->order;
                            $record->update(['order' => $higherRecord->order]);
                            $higherRecord->update(['order' => $tempOrder]);
                        }
                    })
                    ->visible(fn (VideoPanduan $record): bool => 
                        VideoPanduan::where('order', '<', $record->order)->exists()
                    )
                    ->tooltip('Pindahkan ke atas'),

                Tables\Actions\Action::make('moveDown')
                    ->label('Turun')
                    ->icon('heroicon-s-arrow-down')
                    ->color('warning')
                    ->size('sm')
                    ->action(function (VideoPanduan $record): void {
                        $lowerRecord = VideoPanduan::where('order', '>', $record->order)
                            ->orderBy('order', 'asc')
                            ->first();
                        
                        if ($lowerRecord) {
                            $tempOrder = $record->order;
                            $record->update(['order' => $lowerRecord->order]);
                            $lowerRecord->update(['order' => $tempOrder]);
                        }
                    })
                    ->visible(fn (VideoPanduan $record): bool => 
                        VideoPanduan::where('order', '>', $record->order)->exists()
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
            ->emptyStateHeading('Belum ada Video Sosialisasi')
            ->emptyStateDescription('Buat video sosialisasi pertama untuk membantu pengguna.')
            ->emptyStateIcon('heroicon-o-play-circle')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload Video Sosialisasi'),
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
            'index' => Pages\ListVideoPanduans::route('/'),
            'create' => Pages\CreateVideoPanduan::route('/create'),
            'edit' => Pages\EditVideoPanduan::route('/{record}/edit'),
        ];
    }
}
