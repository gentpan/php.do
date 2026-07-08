<div>
    <x-filament-panels::page>
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-600 dark:text-gray-300">点击右上角「立即清理」可清理 OPcache 与 Laravel 应用缓存。</p>
            @if (! empty($messages))
                <ul class="mt-4 list-disc space-y-1 pl-5 text-sm text-gray-800 dark:text-gray-100">
                    @foreach ($messages as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-filament-panels::page>
</div>
