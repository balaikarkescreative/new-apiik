<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Notifications\Notification;

class BulkCreateFaqs extends Page
{
    use InteractsWithFormActions;

    protected static string $resource = FaqResource::class;

    protected static string $view = 'filament.resources.faq-resource.pages.bulk-create-faqs';

    protected static ?string $title = 'Tambah FAQ Massal';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'faqs' => [
                [
                    'question' => '',
                    'answer' => '',
                    'is_active' => true,
                ]
            ]
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('FAQ Multiple')
                    ->description('Tambahkan beberapa FAQ sekaligus')
                    ->schema([
                        Forms\Components\Repeater::make('faqs')
                            ->label('Daftar FAQ')
                            ->schema([
                                Forms\Components\TextInput::make('question')
                                    ->label('Pertanyaan')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan pertanyaan yang sering diajukan')
                                    ->columnSpanFull(),

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
                                    ]),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->columns(1)
                            ->addActionLabel('Tambah FAQ Baru')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'FAQ Baru')
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->requiresConfirmation()
                            )
                            ->minItems(1)
                            ->defaultItems(1),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        $maxOrder = Faq::max('order') ?? 0;
        $createdCount = 0;

        foreach ($data['faqs'] as $index => $faqData) {
            if (!empty($faqData['question']) && !empty($faqData['answer'])) {
                Faq::create([
                    'question' => $faqData['question'],
                    'answer' => $faqData['answer'],
                    'is_active' => $faqData['is_active'] ?? true,
                    'order' => $maxOrder + $index + 1,
                ]);
                $createdCount++;
            }
        }

        if ($createdCount > 0) {
            Notification::make()
                ->title('Berhasil!')
                ->body("$createdCount FAQ berhasil ditambahkan.")
                ->success()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        } else {
            Notification::make()
                ->title('Gagal!')
                ->body('Tidak ada FAQ yang valid untuk ditambahkan.')
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Simpan Semua FAQ')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('create'),
            Actions\Action::make('cancel')
                ->label('Batal')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
