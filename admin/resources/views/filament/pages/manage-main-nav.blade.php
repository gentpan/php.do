<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            顶部导航固定为「首页」，其后按排序展示已启用的版块（与前台 header 一致）。
        </p>
        <p class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-100">
            预览：首页
            @if (count($this->navPreview()) > 0)
                → {{ implode(' → ', $this->navPreview()) }}
            @else
                <span class="font-normal text-gray-500">（暂无版块显示在导航）</span>
            @endif
        </p>
    </div>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
