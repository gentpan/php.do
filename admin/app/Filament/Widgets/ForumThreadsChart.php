<?php

namespace App\Filament\Widgets;

use App\Models\Forum;
use App\Models\Thread;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ForumThreadsChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = '版块主题分布';

    protected ?string $description = '各版块有效主题数量';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '280px';

    protected string $color = 'warning';

    protected function getData(): array
    {
        $rows = Thread::query()
            ->select('forum_id', DB::raw('COUNT(*) AS c'))
            ->where('is_deleted', 0)
            ->groupBy('forum_id')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $forumIds = $rows->pluck('forum_id')->all();
        $forums = Forum::query()
            ->whereIn('id', $forumIds)
            ->pluck('name', 'id');

        $labels = [];
        $data = [];
        $colors = [
            '#f59e0b', '#0ea5e9', '#22c55e', '#8b5cf6', '#ef4444',
            '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1',
        ];

        foreach ($rows as $i => $row) {
            $name = (string) ($forums[$row->forum_id] ?? ('#' . $row->forum_id));
            $labels[] = mb_strlen($name) > 8 ? mb_substr($name, 0, 8) . '…' : $name;
            $data[] = (int) $row->c;
        }

        return [
            'datasets' => [
                [
                    'label' => '主题数',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
