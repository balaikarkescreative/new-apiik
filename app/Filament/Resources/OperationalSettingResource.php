<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperationalSettingResource\Pages;
use App\Models\OperationalSetting;
use Filament\Forms;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Tables;

class OperationalSettingResource extends Resource
{
    protected static ?string $model = OperationalSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Jam Operasional Pengajuan';
    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 900;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin']);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Jam Operasional')
                ->columns(2)
                ->schema([
                    TimePicker::make('start_time')
                        ->label('Jam Mulai')
                        ->withoutSeconds()
                        ->native(false)
                        ->required()
                        ->default('08:00'),
                    TimePicker::make('end_time')
                        ->label('Jam Selesai')
                        ->withoutSeconds()
                        ->native(false)
                        ->required()
                        ->default('16:00'),
                ]),
            Forms\Components\CheckboxList::make('operational_days')
                ->label('Hari Operasional')
                ->options(OperationalSetting::dayOptions())
                ->columns(4)
                ->default([1, 2, 3, 4, 5])
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
            Forms\Components\Section::make('Penutupan Layanan Spesifik')
                ->description('Pilih layanan tertentu yang ingin ditutup sementara')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Toggle::make('icv_closed')
                                ->label('Tutup Pengajuan ICV')
                                ->helperText('Tutup sementara layanan pengajuan ICV'),
                            Forms\Components\Toggle::make('izin_closed')
                                ->label('Tutup Pengajuan Izin')
                                ->helperText('Tutup sementara layanan pengajuan izin'),
                            Forms\Components\Toggle::make('account_closed')
                                ->label('Tutup Pengajuan Akun')
                                ->helperText('Tutup sementara layanan pengajuan akun'),
                        ]),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->beforeStateUpdated(function ($record, $state) {
                        // Jika mengaktifkan pengaturan ini
                        if ($state) {
                            // Nonaktifkan semua pengaturan lain
                            OperationalSetting::where('id', '!=', $record->id)
                                ->update(['is_active' => false]);
                        }
                    }),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Jam Mulai'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Jam Selesai'),
                Tables\Columns\TextColumn::make('operational_days')
                    ->label('Hari')
                    ->formatStateUsing(function ($state, $record) {
                        $days = $record->operational_days;

                        if (empty($days)) {
                            return 'Tidak ada hari';
                        }

                        if (!is_array($days)) {
                            if (is_string($days)) {
                                $decoded = json_decode($days, true);
                                if (is_array($decoded)) {
                                    $days = $decoded;
                                } else {
                                    return 'Data: ' . $days;
                                }
                            } else {
                                return 'Type: ' . gettype($days);
                            }
                        }

                        $dayOptions = OperationalSetting::dayOptions();
                        $dayNames = [];

                        foreach ($days as $day) {
                            $dayNames[] = $dayOptions[$day] ?? "Hari-$day";
                        }

                        return implode(', ', $dayNames);
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->is_active) {
                            return 'Tidak Aktif';
                        }
                        return $record->isOpen() ? 'Buka' : 'Tutup';
                    })
                    ->color(function ($state) {
                        return match($state) {
                            'Buka' => 'success',
                            'Tutup' => 'warning',
                            'Tidak Aktif' => 'gray',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('closed_services_display')
                    ->label('Layanan Ditutup')
                    ->getStateUsing(function ($record) {
                        if (!$record) return '-';
                        
                        $closedServices = [];
                        
                        if ($record->icv_closed) $closedServices[] = 'ICV';
                        if ($record->izin_closed) $closedServices[] = 'Izin';
                        if ($record->account_closed) $closedServices[] = 'Akun';
                        
                        return empty($closedServices) ? 'Semua Buka' : implode(', ', $closedServices);
                    })
                    ->color(function ($record) {
                        if (!$record) return 'gray';
                        return ($record->icv_closed || $record->izin_closed || $record->account_closed) ? 'danger' : 'success';
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_services')
                    ->label('Kelola Layanan')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('warning')
                    ->visible(fn ($record) => $record && $record->is_active)
                    ->form([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Toggle::make('icv_closed')
                                    ->label('Tutup Pengajuan ICV')
                                    ->default(fn ($record) => $record->icv_closed),
                                Forms\Components\Toggle::make('izin_closed')
                                    ->label('Tutup Pengajuan Izin')
                                    ->default(fn ($record) => $record->izin_closed),
                                Forms\Components\Toggle::make('account_closed')
                                    ->label('Tutup Pengajuan Akun')
                                    ->default(fn ($record) => $record->account_closed),
                            ]),
                    ])
                    ->action(function ($record, $data) {
                        if (!$record) {
                            return;
                        }
                        
                        $record->update([
                            'icv_closed' => $data['icv_closed'] ?? false,
                            'izin_closed' => $data['izin_closed'] ?? false,
                            'account_closed' => $data['account_closed'] ?? false,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperationalSettings::route('/'),
            'edit' => Pages\EditOperationalSetting::route('/{record}/edit'),
        ];
    }
}
