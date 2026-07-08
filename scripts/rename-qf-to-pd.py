#!/usr/bin/env python3
"""One-shot: rename qf/phpdo prefixes to pd across project sources."""
from __future__ import annotations

import os
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

SKIP_DIRS = {
    '.git',
    'node_modules',
    'vendor',
    'bootstrap/cache',
}

SKIP_PREFIXES = (
    'admin/public/js/filament/',
    'admin/public/css/filament/',
    'admin/public/fonts/filament/',
)

TEXT_EXTENSIONS = {
    '.php', '.js', '.css', '.md', '.txt', '.py', '.json', '.svg', '.blade.php', '.example', '.sh',
}

# Longest / most specific first to avoid partial collisions.
REPLACEMENTS = [
    ('phpdo_render_thread_row', 'pd_render_thread_row'),
    ('__qfHomeBarEarly', '__pdHomeBarEarly'),
    ('phpdo-right-toolbar', 'pd-right-toolbar'),
    ('seed-phpdo-community', 'seed-pd-community'),
    ('seed_phpdo_', 'seed_pd_'),
    ('qfPrelineInit', 'pdPrelineInit'),
    ('qfSetLoading', 'pdSetLoading'),
    ('qfCsrfToken', 'pdCsrfToken'),
    ('qfGeoipUrl', 'pdGeoipUrl'),
    ('qfUserTimezone', 'pdUserTimezone'),
    ('qfCurrentUserId', 'pdCurrentUserId'),
    ('qfThemeMode', 'pdThemeMode'),
    ('qf_is_bar_loading', 'pd_is_bar_loading'),
    ('qf_is_feed_loading', 'pd_is_feed_loading'),
    ('qf_is_loading', 'pd_is_loading'),
    ('qf-topload', 'pd-topload'),
    ('theme-php-dark', 'theme-pd-dark'),
    ('theme-php', 'theme-pd'),
    ('--phpdo-', '--pd-'),
    ('is_php_theme', 'is_pd_theme'),
    ('QF_START', 'PD_START'),
    ('qf_phpdo', 'pd_forum'),
    ('phpdo-', 'pd-'),
    ('phpdo_', 'pd_'),
    ('qf-', 'pd-'),
    ('qf_', 'pd_'),
]

# Logo SVG ids: phpdo-badge-* → pd-badge-* (not caught by phpdo- alone in all cases)
EXTRA_REPLACEMENTS = [
    ('phpdo-badge-purple', 'pd-badge-purple'),
    ('phpdo-badge-shadow', 'pd-badge-shadow'),
    ('phpdo-logo-title', 'pd-logo-title'),
]


def should_skip(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    for prefix in SKIP_PREFIXES:
        if rel.startswith(prefix):
            return True
    for part in path.parts:
        if part in SKIP_DIRS:
            return True
    return False


def transform(content: str) -> str:
    for old, new in REPLACEMENTS + EXTRA_REPLACEMENTS:
        content = content.replace(old, new)
    return content


def main() -> None:
    changed = 0
    for path in ROOT.rglob('*'):
        if not path.is_file() or should_skip(path):
            continue
        if path.suffix not in TEXT_EXTENSIONS and path.name not in ('.env.example',):
            continue
        if path.name == 'rename-qf-to-pd.py':
            continue
        try:
            original = path.read_text(encoding='utf-8')
        except (UnicodeDecodeError, OSError):
            continue
        updated = transform(original)
        if updated != original:
            path.write_text(updated, encoding='utf-8')
            changed += 1
            print(path.relative_to(ROOT))
    print(f'done: {changed} files updated')


if __name__ == '__main__':
    main()
