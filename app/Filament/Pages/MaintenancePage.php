<?php

namespace App\Filament\Pages;

use App\Models\MaintenanceMode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Str;

class MaintenancePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Mode Maintenance';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $slug = 'maintenance-mode'; // Set a specific slug
    protected static ?string $title = 'Maintenance Mode';
    protected static ?int $navigationSort = 999;
    protected static string $view = 'filament.pages.maintenance-page';

    public ?array $data = [];
    public bool $isMaintenanceActive = false;
    public ?MaintenanceMode $maintenanceStatus = null;

    public function mount(): void
    {
        $this->refreshData();
    }

    protected function refreshData(): void
    {
        $this->isMaintenanceActive = MaintenanceMode::isActive();
        $this->maintenanceStatus = MaintenanceMode::getStatus();
        
        $this->form->fill([
            'secret_key' => $this->maintenanceStatus?->secret_key ?? Str::random(16),
            'message' => $this->maintenanceStatus?->message ?? 'Sistem sedang dalam maintenance, mohon tunggu beberapa saat.',
            'retry_after' => $this->maintenanceStatus?->retry_after ?? 60,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Maintenance Mode')
                    ->description('Konfigurasi maintenance mode untuk sistem')
                    ->schema([
                        Forms\Components\TextInput::make('secret_key')
                            ->label('Secret Key (Kata Kunci Bypass)')
                            ->helperText('Kata kunci untuk mengakses sistem selama maintenance. Simpan dengan aman!')
                            ->required()
                            ->maxLength(255)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generate')
                                    ->label('Generate')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function (Forms\Set $set) {
                                        $set('secret_key', Str::random(16));
                                    })
                            ),
                        
                        Forms\Components\Textarea::make('message')
                            ->label('Pesan Maintenance (Opsional)')
                            ->helperText('Pesan yang akan ditampilkan kepada pengguna. Berikan informasi yang jelas!')
                            ->rows(3)
                            ->placeholder('Contoh: Sistem sedang maintenance dari 14:00-16:00 WIB, mohon coba lagi nanti.'),
                        
                        Forms\Components\TextInput::make('retry_after')
                            ->label('Retry After (detik)')
                            ->helperText('Browser akan menunggu sekian detik sebelum mencoba lagi. Recommended: 60-300 detik')
                            ->numeric()
                            ->required()
                            ->minValue(30)
                            ->maxValue(3600)
                            ->suffix('detik'),
                    ]),
                
                Forms\Components\Section::make('Status Saat Ini')
                    ->schema([
                        Forms\Components\Placeholder::make('current_status')
                            ->label('Status Maintenance')
                            ->content(fn (): string => $this->isMaintenanceActive ? 'AKTIF ⚠️' : 'TIDAK AKTIF ✅'),
                        
                        Forms\Components\Placeholder::make('bypass_url')
                            ->label('URL Bypass')
                            ->content(fn (): string => $this->maintenanceStatus?->getBypassUrl() ?? 'Belum tersedia')
                            ->visible(fn (): bool => $this->isMaintenanceActive),
                    ])
                    ->visible(fn (): bool => $this->maintenanceStatus !== null),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enable_maintenance')
                ->label('Aktifkan Maintenance')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (): bool => !$this->isMaintenanceActive)
                ->requiresConfirmation()
                ->modalHeading('Aktifkan Mode Maintenance?')
                ->modalDescription('Sistem akan tidak dapat diakses oleh user. Pastikan Anda menyimpan secret key untuk bypass!')
                ->action(function () {
                    $data = $this->form->getState();
                    
                    if (MaintenanceMode::enable(
                        $data['secret_key'],
                        $data['message'],
                        (int) $data['retry_after']
                    )) {
                        Notification::make()
                            ->title('Maintenance Mode Diaktifkan')
                            ->body("Secret Key: {$data['secret_key']}")
                            ->success()
                            ->persistent()
                            ->send();
                        
                        // Refresh current page data
                        $this->refreshData();
                        $this->dispatch('$refresh');
                    } else {
                        Notification::make()
                            ->title('Gagal Mengaktifkan Maintenance')
                            ->body('Terjadi kesalahan saat mengaktifkan maintenance mode')
                            ->danger()
                            ->send();
                    }
                }),
            
            Action::make('disable_maintenance')
                ->label('Nonaktifkan Maintenance')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->visible(fn (): bool => $this->isMaintenanceActive)
                ->requiresConfirmation()
                ->modalHeading('Nonaktifkan Mode Maintenance?')
                ->modalDescription('Sistem akan dapat diakses kembali oleh semua user.')
                ->action(function () {
                    if (MaintenanceMode::disable()) {
                        Notification::make()
                            ->title('Maintenance Mode Dinonaktifkan')
                            ->body('Sistem telah dapat diakses kembali')
                            ->success()
                            ->send();
                        
                        // Refresh current page data
                        $this->refreshData();
                        $this->dispatch('$refresh');
                    } else {
                        Notification::make()
                            ->title('Gagal Menonaktifkan Maintenance')
                            ->body('Terjadi kesalahan saat menonaktifkan maintenance mode')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public static function canAccess(): bool
    {
        // Sementara return true untuk testing, nanti bisa disesuaikan dengan permission yang ada
        return auth()->user()->hasRole(['admin']);
        // return auth()->user()->can('manage_maintenance_mode');
    }
}
