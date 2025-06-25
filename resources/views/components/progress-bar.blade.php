{{-- filepath: /home/syhrl/Documents/new-apiik2/resources/views/components/progress-bar.blade.php --}}
<div class="flex items-center gap-2">
    <div class="w-16 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
             style="width: {{ min($progress, 100) }}%"></div>
    </div>
    <span class="text-xs text-gray-500 min-w-[30px]">{{ $progress }}%</span>
</div>
