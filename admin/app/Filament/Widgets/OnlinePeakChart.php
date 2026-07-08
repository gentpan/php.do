<?php

namespace App\Filament\Widgets;

use App\Models\OnlineDaily;
use Filament\Widgets\ChartWidget;

class OnlinePeakChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = '在线峰值趋势';

    protected ?string $description = '每日同时在线人数峰值';

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    protected string $color = 'info';

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
        $from = now()->subDays($days - 1)->format('Y-m-d');

        $rows = OnlineDaily::query()
            ->where('day_date', '>=', $from)
            ->orderBy('day_date')
            ->get()
            ->keyBy('day_date');

        $labels = [];
        $totals = [];
        $members = [];
        $guests = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $row = $rows->get($key);

            $labels[] = $date->format('m/d');
            $totals[] = (int) ($row?->peak_total ?? 0);
            $members[] = (int) ($row?->peak_members ?? 0);
            $guests[] = (int) ($row?->peak_guests ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => '总在线',
                    'data' => $totals,
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => '会员',
                    'data' => $members,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'transparent',
                    'tension' => 0.3,
                ],
                [
                    'label' => '访客',
                    'data' => $guests,
                    'borderColor' => '#94a3b8',
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
