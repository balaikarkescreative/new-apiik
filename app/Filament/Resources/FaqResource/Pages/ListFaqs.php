<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()
            //     ->label('Buat FAQ Baru')
            //     ->icon('heroicon-o-plus'),
            Actions\Action::make('create')
                ->label('Tambah FAQ')
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => FaqResource::getUrl('create')),
        ];
    }

    protected static ?string $title = 'Daftar FAQ';
}
