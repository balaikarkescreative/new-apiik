<?php

namespace App\Filament\Resources\PaktaIntegritasResource\Pages;

use App\Filament\Resources\PaktaIntegritasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaktaIntegritas extends EditRecord
{
    protected static string $resource = PaktaIntegritasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->renameUploadedFile();
    }

    private function renameUploadedFile(): void
    {
        $record = $this->record;

        if ($record->file) {
            $user = $record->user;
            $userName = $user ? $user->name : 'Unknown';

            // Clean user name for filename
            $cleanUserName = preg_replace('/[^a-zA-Z0-9\s]/', '', $userName);
            $cleanUserName = preg_replace('/\s+/', ' ', trim($cleanUserName));

            // Get current date in ddmmyyyy format
            $date = now()->format('dmY');

            // Get file extension
            $currentFilePath = $record->file;
            $extension = pathinfo($currentFilePath, PATHINFO_EXTENSION);

            // Create new filename
            $newFileName = "Pakta integritas {$cleanUserName} ({$date}).{$extension}";
            $newFilePath = 'pakta-integritas/' . $newFileName;

            // Check if filename needs to be changed
            if ($currentFilePath !== $newFilePath) {
                $oldFullPath = storage_path('app/public/' . $currentFilePath);
                $newFullPath = storage_path('app/public/' . $newFilePath);

                if (file_exists($oldFullPath)) {
                    // Ensure directory exists
                    $directory = dirname($newFullPath);
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    // Rename file
                    if (rename($oldFullPath, $newFullPath)) {
                        // Update database record
                        $record->update(['file' => $newFilePath]);
                    }
                }
            }
        }
    }

    public function getRedirectUrl(): string|null
    {
        return static::getResource()::getUrl('index');
    }
}
