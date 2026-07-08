<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class DailyActivityChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = '内容活跃趋势';

    protected ?string $description = '每日新主题、回复与注册';

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public ?string $filter = '14';

    protected function getFilters(): ?array
    {
        return [
            '7' => '近 7 天',
            '14' => '近 14 天',
            '30' => '近 30 天',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?: 14);
        $from = now()->subDays($days - 1)->startOfDay();

        $threads = Thread::query()
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS c')
            ->where('is_deleted', 0)
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->pluck('c', 'day');

        $posts = Post::query()
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS c')
            ->where('is_deleted', 0)
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->pluck('c', 'day');

        $users = User::query()
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS c')
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->pluck('c', 'day');

        $labels = [];
        $threadData = [];
        $postData = [];
        $userData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');

            $labels[] = $date->format('m/d');
            $threadData[] = (int) ($threads[$key] ?? 0);
            $postData[] = (int) ($posts[$key] ?? 0);
            $userData[] = (int) ($users[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => '新主题',
                    'data' => $threadData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.08)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => '新回复',
                    'data' => $postData,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.3,
                ],
                [
                    'label' => '新注册',
                    'data' => $userData,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
