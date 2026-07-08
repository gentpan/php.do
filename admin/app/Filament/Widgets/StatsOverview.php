<?php

namespace App\Filament\Widgets;

use App\Models\Ban;
use App\Models\Forum;
use App\Models\OnlineDaily;
use App\Models\OnlineSession;
use App\Models\Thread;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $win = now()->subMinutes(15);
        $members = (int) OnlineSession::query()
            ->where('user_id', '>', 0)
            ->where('last_seen', '>=', $win)
            ->distinct()
            ->count('user_id');
        $guests = OnlineSession::query()
            ->where('user_id', 0)
            ->where('last_seen', '>=', $win)
            ->count();
        $today = OnlineDaily::query()->orderByDesc('day_date')->first();

        return [
            Stat::make('用户', User::query()->count()),
            Stat::make('版块', Forum::query()->count()),
            Stat::make('主题', Thread::query()->where('is_deleted', 0)->count()),
            Stat::make('当前在线', $members + $guests)
                ->description("会员 {$members} · 访客 {$guests}"),
            Stat::make('今日峰值', $today?->peak_total ?? 0),
            Stat::make('活跃禁封', Ban::query()->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count()),
        ];
    }
}
