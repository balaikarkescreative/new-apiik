<?php

namespace App\Filament\Pages;

use App\Models\VideoPanduan;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class VideoPanduanPlayer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play';

    protected static ?string $navigationLabel = 'Video FAQ';

    protected static ?string $slug = 'video-faq';

    protected static string $view = 'filament.pages.video-panduan-player';

    protected static ?string $title = 'Video FAQ';

    protected static ?int $navigationSort = 5;

    public ?Collection $videos = null;

    #[Url]
    public ?string $activeVideoId = null;

    public ?VideoPanduan $activeVideo = null;

    public function mount(): void
    {
        // Ambil semua video yang aktif dan diurutkan berdasarkan order
        $this->videos = VideoPanduan::isActive()->ordered()->get();

        // Jika tidak ada activeVideoId yang diberikan, gunakan video pertama (jika ada)
        if (! $this->activeVideoId && $this->videos->isNotEmpty()) {
            $this->activeVideoId = $this->videos->first()->id;
        }

        // Set activeVideo berdasarkan activeVideoId
        if ($this->activeVideoId) {
            $this->activeVideo = $this->videos->firstWhere('id', $this->activeVideoId);
        }
    }

    #[On('selectVideo')]
    public function selectVideo($videoId): void
    {
        $this->activeVideoId = $videoId;
        $this->activeVideo = $this->videos->firstWhere('id', $videoId);

        // Dispatch event dengan sedikit delay untuk memastikan DOM sudah terupdate
        $this->dispatch('videoChanged', [
            'videoUrl' => $this->activeVideo?->video_url,
            'videoId' => $videoId,
            'videoTitle' => $this->activeVideo?->title,
        ]);
    }
}
