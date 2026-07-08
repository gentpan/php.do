@php
    use App\Support\ServerInfo;

    $percent = min(100, max(0, (float) ($percent ?? 0)));
    $level = ServerInfo::gaugeLevel($percent);
@endphp

<div class="pd-resource-gauge">
    <div class="pd-resource-gauge-ring" data-level="{{ $level }}">
        <svg viewBox="0 0 36 36" role="img" aria-label="{{ $title }} {{ ServerInfo::formatPercent($percent) }}%">
            <path
                class="pd-resource-gauge-track"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
            />
            <path
                class="pd-resource-gauge-progress pd-resource-gauge-progress--{{ $tone ?? 'memory' }} pd-resource-gauge-progress--{{ $level }}"
                stroke-dasharray="{{ $percent }}, 100"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
            />
        </svg>
        <div class="pd-resource-gauge-value">{{ ServerInfo::formatPercent($percent) }}<span>%</span></div>
    </div>
    <div class="pd-resource-gauge-title">{{ $title }}</div>
    <div class="pd-resource-gauge-detail">{{ $detail }}</div>
</div>
