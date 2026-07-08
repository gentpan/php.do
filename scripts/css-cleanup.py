#!/usr/bin/env python3
"""Remove legacy theme / duplicate CSS blocks from assets/main.css."""
import re
import sys

PATH = 'assets/main.css'

DEAD_SELECTOR_PATTERNS = [
    r'site-nav-bar',
    r'phpnet-',
    r'\.site-header\b',
    r'\.nav nav\b',
    r'body\.theme-cool-',
    r'body\.theme-warm-',
    r'body\.theme-light\b',
    r'body\.theme-litenote\b',
    r'body\.theme-dark\b(?!-)',
    r'\.auth-modal-',
    r'\.auth-panel\b',
    r'\.auth-tab\b',
    r'\.auth-tabs\b',
    r'\.auth-submit\b',
    r'\.auth-alert\b',
]


def remove_between(text, start, end):
    """Remove from start marker through char before end marker."""
    i = text.find(start)
    if i < 0:
        print('skip (no start):', start[:60])
        return text
    j = text.find(end, i + len(start))
    if j < 0:
        print('skip (no end):', end[:60])
        return text
    print('removed:', start[:50], '->', end[:50], '(%d bytes)' % (j - i))
    return text[:i] + text[j:]


def rule_is_dead(block):
    for pat in DEAD_SELECTOR_PATTERNS:
        if re.search(pat, block):
            return True
    return False


def strip_dead_rules(css):
  lines = css.split('\n')
  out = []
  i = 0
  while i < len(lines):
    line = lines[i]
    stripped = line.strip()
    if stripped.startswith('/*'):
      start = i
      i += 1
      while i < len(lines) and '*/' not in lines[i - 1]:
        i += 1
      out.extend(lines[start:i])
      continue
    if '{' not in line:
      if rule_is_dead(line):
        i += 1
        continue
      out.append(line)
      i += 1
      continue
    block_start = i
    depth = line.count('{') - line.count('}')
    i += 1
    while i < len(lines) and depth > 0:
      depth += lines[i].count('{') - lines[i].count('}')
      i += 1
    block = '\n'.join(lines[block_start:i])
    if not rule_is_dead(block):
      out.extend(lines[block_start:i])
  return '\n'.join(out)


def main():
    with open(PATH, encoding='utf-8') as f:
        s = f.read()
    original = len(s)

    # Large duplicate / legacy sections (bottom-up)
    s = remove_between(s, '/* php.net fidelity pass', '/* php.do forum index */')
    s = remove_between(s, '/* LiteNote-style navigation */', '/* LiteNote-style toast */')
    s = remove_between(s, '/* Three-palette theme lock */', '/* Dynamic font application */')
    s = remove_between(s, '/* Final theme overrides */', '/* Dynamic font application */')

    # Duplicate shape locks (keep first, drop until real body reset)
    marker = '/* Final shape lock */'
    first = s.find(marker)
    second = s.find(marker, first + len(marker)) if first >= 0 else -1
    body_reset = 'body {\n    margin: 0;\n    background: #fff;'
    if second >= 0:
        br = s.find(body_reset, second)
        if br > second:
            print('removed: duplicate shape locks (%d bytes)' % (br - second))
            s = s[:second] + s[br:]

    # First accent refresh block (before first shape lock)
    s = remove_between(s, '/* PHP accent visual refresh */', marker)

    # Strip dead selector rules throughout
    s = strip_dead_rules(s)

    # Collapse excessive blank lines
    s = re.sub(r'\n{4,}', '\n\n\n', s)

    # Forum index: drop ineffective background (outer frame uses php.net texture)
    s = re.sub(
        r'body\.theme-php \{\n    color: var\(--phpdo-text\);\n    background:\n        linear-gradient[^\}]+\}\n',
        'body.theme-php {\n    color: var(--phpdo-text);\n    font-family: var(--qf-content-font), var(--phpdo-font-sans);\n}\n',
        s,
        count=1,
    )

    with open(PATH, 'w', encoding='utf-8') as f:
        f.write(s)
    print('done: %d -> %d bytes (%.1f%% reduction)' % (original, len(s), 100 * (1 - len(s) / original)))


if __name__ == '__main__':
    main()
