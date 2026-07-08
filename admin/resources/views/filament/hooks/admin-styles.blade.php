<style>
    .fi-sidebar-nav {
        overflow-y: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding-inline: 0.625rem;
        padding-block: 0.75rem;
        row-gap: 0.75rem;
    }

    .fi-sidebar-nav::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .fi-sidebar-nav-groups {
        margin-inline: 0;
        row-gap: 0.625rem;
    }

    .fi-sidebar-group-label {
        font-size: 0.6875rem;
        line-height: 1.25rem;
    }

    .fi-sidebar-item-btn {
        padding: 0.375rem 0.5rem;
        gap: 0.5rem;
    }

    .fi-sidebar-item-label {
        font-size: 0.8125rem;
        line-height: 1.25rem;
    }

    .fi-sidebar-header {
        height: 3.5rem;
    }

    :not(.fi-body-has-topbar) .fi-sidebar-header {
        padding-inline: 0.625rem;
    }

    .fi-sidebar-header-logo-ctn .fi-logo {
        max-width: 100%;
    }

    .fi-sidebar-header-logo-ctn img {
        max-height: 1.5rem;
        width: auto;
    }

    .pd-server-status {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.875rem;
        margin: 0;
    }

    .pd-server-status-item {
        margin: 0;
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: color-mix(in srgb, var(--gray-950) 3%, transparent);
    }

    .dark .pd-server-status-item {
        background: color-mix(in srgb, var(--gray-50) 5%, transparent);
    }

    .pd-server-status-item--wide {
        grid-column: 1 / -1;
    }

    .pd-server-status-item dt {
        margin: 0 0 0.375rem;
        font-size: 0.75rem;
        line-height: 1rem;
        color: var(--gray-500);
    }

    .pd-server-status-item dd {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        margin: 0;
        font-size: 0.8125rem;
        line-height: 1.25rem;
        color: var(--gray-700);
    }

    .dark .pd-server-status-item dd {
        color: var(--gray-300);
    }

    .pd-server-status-item dd strong {
        font-weight: 600;
        color: var(--gray-950);
    }

    .dark .pd-server-status-item dd strong {
        color: var(--gray-50);
    }

    .pd-server-status-item dd span {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .pd-server-meter {
        height: 0.5rem;
        overflow: hidden;
        border-radius: 9999px;
        background: color-mix(in srgb, var(--gray-950) 8%, transparent);
    }

    .dark .pd-server-meter {
        background: color-mix(in srgb, var(--gray-50) 10%, transparent);
    }

    .pd-server-meter-bar {
        height: 100%;
        border-radius: 9999px;
    }

    .pd-server-meter-bar--memory {
        background: var(--primary-500);
    }

    .pd-server-meter-bar--disk {
        background: #0ea5e9;
    }

    .pd-server-status-link {
        margin-top: 1rem;
    }

    @media (max-width: 640px) {
        .pd-server-status {
            grid-template-columns: 1fr;
        }
    }

    .pd-resource-gauges {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.25rem;
    }

    .pd-resource-gauge {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.625rem;
    }

    .pd-resource-gauge-ring {
        position: relative;
        width: 8.5rem;
        height: 8.5rem;
    }

    .pd-resource-gauge-ring svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }

    .pd-resource-gauge-track,
    .pd-resource-gauge-progress {
        fill: none;
        stroke-width: 2.8;
    }

    .pd-resource-gauge-track {
        stroke: color-mix(in srgb, var(--gray-950) 10%, transparent);
    }

    .dark .pd-resource-gauge-track {
        stroke: color-mix(in srgb, var(--gray-50) 12%, transparent);
    }

    .pd-resource-gauge-progress {
        stroke-linecap: round;
        transition: stroke-dasharray 0.7s ease, stroke 0.3s ease;
    }

    .pd-resource-gauge-progress--memory.pd-resource-gauge-progress--success,
    .pd-resource-gauge-progress--disk.pd-resource-gauge-progress--success,
    .pd-resource-gauge-progress--load.pd-resource-gauge-progress--success {
        stroke: #22c55e;
    }

    .pd-resource-gauge-progress--memory.pd-resource-gauge-progress--warning {
        stroke: var(--primary-500);
    }

    .pd-resource-gauge-progress--disk.pd-resource-gauge-progress--warning {
        stroke: #0ea5e9;
    }

    .pd-resource-gauge-progress--load.pd-resource-gauge-progress--warning {
        stroke: #f59e0b;
    }

    .pd-resource-gauge-progress--memory.pd-resource-gauge-progress--danger {
        stroke: #ef4444;
    }

    .pd-resource-gauge-progress--disk.pd-resource-gauge-progress--danger {
        stroke: #ef4444;
    }

    .pd-resource-gauge-progress--load.pd-resource-gauge-progress--danger {
        stroke: #ef4444;
    }

    .pd-resource-gauge-value {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.375rem;
        font-weight: 700;
        line-height: 1;
        color: var(--gray-950);
    }

    .dark .pd-resource-gauge-value {
        color: var(--gray-50);
    }

    .pd-resource-gauge-value span {
        margin-left: 0.0625rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-500);
    }

    .pd-resource-gauge-title {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--gray-950);
    }

    .dark .pd-resource-gauge-title {
        color: var(--gray-50);
    }

    .pd-resource-gauge-detail {
        max-width: 12rem;
        font-size: 0.75rem;
        line-height: 1.25rem;
        color: var(--gray-500);
    }

    .pd-resource-gauges-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px solid color-mix(in srgb, var(--gray-950) 8%, transparent);
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .dark .pd-resource-gauges-meta {
        border-top-color: color-mix(in srgb, var(--gray-50) 10%, transparent);
    }

    @media (max-width: 900px) {
        .pd-resource-gauges {
            grid-template-columns: 1fr;
        }
    }
</style>
