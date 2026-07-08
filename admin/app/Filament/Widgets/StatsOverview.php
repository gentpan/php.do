<?php

namespace App\Filament\Widgets;

use App\Models\Ban;
use App\Models\Forum;
use App\Models\OnlineDaily;
use App\Models\OnlineSession;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

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

        $threadsToday = Thread::query()
            ->where('is_deleted', 0)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $postsToday = Post::query()
            ->where('is_deleted', 0)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $usersWeek = User::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $activeWeek = (int) DB::scalar(
            'SELECT COUNT(DISTINCT user_id) FROM (
                SELECT user_id, created_at FROM pd_threads WHERE is_deleted = 0
                UNION ALL
                SELECT user_id, created_at FROM pd_posts WHERE is_deleted = 0
            ) x WHERE created_at >= ?',
            [now()->subDays(7)],
        );
        $threadsWeek = Thread::query()
            ->where('is_deleted', 0)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('用户', User::query()->count())
                ->description("7 日新增 {$usersWeek}")
                ->descriptionIcon('heroicon-m-user-plus'),
            Stat::make('主题', Thread::query()->where('is_deleted', 0)->count())
                ->description("7 日新增 {$threadsWeek}")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right'),
            Stat::make('回复', Post::query()->where('is_deleted', 0)->count())
                ->description("今日 {$postsToday}")
                ->descriptionIcon('heroicon-m-chat-bubble-bottom-center-text'),
            Stat::make('版块', Forum::query()->count()),
            Stat::make('当前在线', $members + $guests)
                ->description("会员 {$members} · 访客 {$guests}")
                ->descriptionIcon('heroicon-m-signal'),
            Stat::make('今日峰值', $today?->peak_total ?? 0)
                ->description($today ? '会员 ' . ($today->peak_members ?? 0) . ' · 访客 ' . ($today->peak_guests ?? 0) : null),
            Stat::make('今日新帖', $threadsToday)
                ->description("今日回复 {$postsToday}")
                ->descriptionIcon('heroicon-m-document-plus'),
            Stat::make('7 日活跃', $activeWeek)
                ->description('发帖或回复的用户')
                ->descriptionIcon('heroicon-m-fire'),
            Stat::make('活跃禁封', Ban::query()->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count())
                ->descriptionIcon('heroicon-m-no-symbol'),
        ];
    }
}
