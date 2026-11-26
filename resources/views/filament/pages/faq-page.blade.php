{{-- resources/views/filament/pages/faq-page.blade.php --}}

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Pertanyaan yang Sering Diajukan
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                Temukan jawaban untuk pertanyaan yang paling umum
            </p>
        </div>

        {{-- Search Bar --}}
        <div class="max-w-md mx-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input 
                    type="text" 
                    id="faq-search"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-800 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="Cari FAQ..."
                >
            </div>
        </div>

        {{-- FAQ Items --}}
        <div class="max-w-4xl mx-auto">
            @if($this->getFaqs()->count() > 0)
                <div class="space-y-4" id="faq-container">
                    @foreach($this->getFaqs() as $faq)
                        <div class="faq-item bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                            <button 
                                class="faq-toggle w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                                type="button"
                                data-target="faq-{{ $faq->id }}"
                            >
                                <span class="faq-question text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $faq->question }}
                                </span>
                                <x-heroicon-o-chevron-down class="faq-icon h-5 w-5 text-gray-500 dark:text-gray-400 transform transition-transform duration-200" />
                            </button>
                            
                            <div 
                                id="faq-{{ $faq->id }}" 
                                class="faq-content hidden px-6 pb-4"
                            >
                                <div class="text-gray-700 dark:text-gray-300 leading-relaxed mt-2">
                                    {!! $faq->answer !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- No results message --}}
                <div id="no-results" class="hidden text-center py-12">
                    <x-heroicon-o-magnifying-glass class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Tidak ada FAQ ditemukan
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Coba sesuaikan pencarian Anda untuk menemukan apa yang Anda cari.
                    </p>
                </div>
            @else
                <div class="text-center py-12">
                    <x-heroicon-o-question-mark-circle class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Tidak ada FAQ tersedia
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Konten FAQ akan muncul di sini setelah ditambahkan.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- JavaScript for interactivity --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle FAQ items
            const faqToggles = document.querySelectorAll('.faq-toggle');
            
            faqToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const target = document.getElementById(this.dataset.target);
                    const icon = this.querySelector('.faq-icon');
                    
                    if (target.classList.contains('hidden')) {
                        target.classList.remove('hidden');
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        target.classList.add('hidden');
                        icon.style.transform = 'rotate(0deg)';
                    }
                });
            });

            // Search functionality
            const searchInput = document.getElementById('faq-search');
            const faqItems = document.querySelectorAll('.faq-item');
            const noResults = document.getElementById('no-results');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    let visibleItems = 0;
                    
                    faqItems.forEach(item => {
                        const question = item.querySelector('.faq-question').textContent.toLowerCase();
                        const content = item.querySelector('.faq-content').textContent.toLowerCase();
                        
                        if (question.includes(searchTerm) || content.includes(searchTerm)) {
                            item.style.display = 'block';
                            visibleItems++;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    // Show/hide no results message
                    if (visibleItems === 0 && searchTerm !== '') {
                        noResults.classList.remove('hidden');
                    } else {
                        noResults.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    {{-- Additional CSS for smooth animations --}}
    <style>
        .faq-icon {
            transition: transform 0.2s ease-in-out;
        }
        
        .faq-content {
            transition: all 0.3s ease-in-out;
        }
        
        .faq-toggle:hover .faq-icon {
            color: #6366f1;
        }
    </style>
</x-filament-panels::page>