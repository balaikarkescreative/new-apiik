<?php

// app/Filament/Pages/FaqPage.php

namespace App\Filament\Pages;

use App\Models\Faq;
use Filament\Pages\Page;

class FaqPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    
    protected static string $view = 'filament.pages.faq-page';
    
    protected static ?string $title = 'FAQ';

    protected ?string $heading = '';
    
    protected static ?string $navigationLabel = 'FAQ';
    
    protected static ?int $navigationSort = 99;
    
    // Jika ingin menempatkan di group tertentu
    // protected static ?string $navigationGroup = 'Help';

    public function getFaqs()
    {
        return Faq::isActive()
            ->ordered()
            ->get();
    }
}