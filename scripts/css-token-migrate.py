#!/usr/bin/env python3
"""Migrate hardcoded brand colors to --phpdo-* tokens in main.css."""
import re

PATH = 'assets/main.css'

REPLACEMENTS = [
    (r'(?<![\w-])#505b93\b', 'var(--phpdo-accent)'),
    (r'(?<![\w-])#3f4874\b', 'var(--phpdo-accent-hover)'),
    (r'(?<![\w-])#3f4874\b', 'var(--phpdo-accent-hover)'),
    (r'var\(--php-accent\)', 'var(--phpdo-accent)'),
    (r'var\(--php-nav\)', 'var(--phpdo-accent)'),
    (r'var\(--php-nav-hover\)', 'var(--phpdo-accent-hover)'),
    (r'var\(--php-text\)', 'var(--phpdo-text)'),
    (r'var\(--php-text-strong\)', 'var(--phpdo-text-title)'),
    (r'var\(--php-muted\)', 'var(--phpdo-muted)'),
    (r'var\(--php-border\)', 'var(--phpdo-border)'),
    (r'var\(--php-surface\)', 'var(--phpdo-surface)'),
    (r'var\(--php-surface-subtle\)', 'var(--phpdo-surface-soft)'),
    (r'var\(--php-surface-muted\)', 'var(--phpdo-surface-soft)'),
]


def main():
    with open(PATH, encoding='utf-8') as f:
        lines = f.readlines()

    out = []
    for line in lines:
        if re.match(r'\s*--phpdo-[a-z-]+:\s*#', line) or re.match(r'\s*--php-[a-z-]+:\s*#', line):
            out.append(line)
            continue
        new_line = line
        for pat, repl in REPLACEMENTS:
            new_line = re.sub(pat, repl, new_line, flags=re.I)
        out.append(new_line)

    text = ''.join(out)
    # Drop legacy --php-* :root block (keep font vars only)
    text = re.sub(
        r'/\* PHP design system \*/\n:root \{\n(?:    --php-[^\n]+\n)+\n    (--qf-title-font:[^\n]+\n    --qf-content-font:[^\n]+\n)\}',
        r'/* PHP design system — 品牌色见 body.theme-php */\n:root {\n    \1}',
        text,
        count=1,
    )
    # Merge list row selectors
    text = text.replace('body.theme-php .phpdo-thread-row,\nbody.theme-php .thread-row',
                        'body.theme-php .phpdo-thread-row,\nbody.theme-php .thread-row')
    pairs = [
        ('body.theme-php .thread-row {', 'body.theme-php .phpdo-thread-row,\nbody.theme-php .thread-row {'),
    ]
    for old, new in pairs:
        if old in text and new not in text:
            text = text.replace(old, new, 1)

    with open(PATH, 'w', encoding='utf-8') as f:
        f.write(text)
    print('token migration done')


if __name__ == '__main__':
    main()
