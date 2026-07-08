#!/usr/bin/env python3
"""Migrate hardcoded brand colors to --pd-* tokens in main.css."""
import re

PATH = 'assets/main.css'

REPLACEMENTS = [
    (r'(?<![\w-])#505b93\b', 'var(--pd-accent)'),
    (r'(?<![\w-])#3f4874\b', 'var(--pd-accent-hover)'),
    (r'(?<![\w-])#3f4874\b', 'var(--pd-accent-hover)'),
    (r'var\(--php-accent\)', 'var(--pd-accent)'),
    (r'var\(--php-nav\)', 'var(--pd-accent)'),
    (r'var\(--php-nav-hover\)', 'var(--pd-accent-hover)'),
    (r'var\(--php-text\)', 'var(--pd-text)'),
    (r'var\(--php-text-strong\)', 'var(--pd-text-title)'),
    (r'var\(--php-muted\)', 'var(--pd-muted)'),
    (r'var\(--php-border\)', 'var(--pd-border)'),
    (r'var\(--php-surface\)', 'var(--pd-surface)'),
    (r'var\(--php-surface-subtle\)', 'var(--pd-surface-soft)'),
    (r'var\(--php-surface-muted\)', 'var(--pd-surface-soft)'),
]


def main():
    with open(PATH, encoding='utf-8') as f:
        lines = f.readlines()

    out = []
    for line in lines:
        if re.match(r'\s*--pd-[a-z-]+:\s*#', line) or re.match(r'\s*--php-[a-z-]+:\s*#', line):
            out.append(line)
            continue
        new_line = line
        for pat, repl in REPLACEMENTS:
            new_line = re.sub(pat, repl, new_line, flags=re.I)
        out.append(new_line)

    text = ''.join(out)
    # Drop legacy --php-* :root block (keep font vars only)
    text = re.sub(
        r'/\* PHP design system \*/\n:root \{\n(?:    --php-[^\n]+\n)+\n    (--pd-title-font:[^\n]+\n    --pd-content-font:[^\n]+\n)\}',
        r'/* PHP design system — 品牌色见 body.theme-pd */\n:root {\n    \1}',
        text,
        count=1,
    )
    # Merge list row selectors
    text = text.replace('body.theme-pd .pd-thread-row,\nbody.theme-pd .thread-row',
                        'body.theme-pd .pd-thread-row,\nbody.theme-pd .thread-row')
    pairs = [
        ('body.theme-pd .thread-row {', 'body.theme-pd .pd-thread-row,\nbody.theme-pd .thread-row {'),
    ]
    for old, new in pairs:
        if old in text and new not in text:
            text = text.replace(old, new, 1)

    with open(PATH, 'w', encoding='utf-8') as f:
        f.write(text)
    print('token migration done')


if __name__ == '__main__':
    main()
