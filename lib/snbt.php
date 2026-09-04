<?php
// ================================================
// lib/snbt.php — SNBT parser and writer
// ------------------------------------------------
// Minecraft's stringified NBT. Used by Command Doctor to read a
// pasted command apart and write it back in a different version's
// syntax, so conversions are structural rather than regex guesses.
//
// Nodes are ['t' => type, 'v' => value] where type is one of:
//   compound  v = ordered map of key => node
//   list      v = list of nodes
//   array     v = ['prefix' => 'I'|'B'|'L', 'items' => [node,…]]
//   string    v = the decoded text,  q = the quote character used
//   number    v = the literal as written (keeps 1b, 5s, 3.0f intact)
//   word      v = an unquoted bareword (true, false, an id, …)
// ================================================

class SnbtError extends RuntimeException {}

function snbt_parse(string $src)
{
    $i = 0;
    $node = snbt_value($src, $i);
    snbt_ws($src, $i);
    if ($i < strlen($src)) {
        throw new SnbtError('Unexpected text after the value: "' . substr($src, $i, 20) . '"');
    }
    return $node;
}

function snbt_ws(string $s, int &$i): void
{
    while ($i < strlen($s) && ctype_space($s[$i])) $i++;
}

function snbt_expect(string $s, int &$i, string $ch): void
{
    snbt_ws($s, $i);
    if ($i >= strlen($s) || $s[$i] !== $ch) {
        throw new SnbtError('Expected "' . $ch . '" at position ' . $i . '.');
    }
    $i++;
}

function snbt_value(string $s, int &$i)
{
    snbt_ws($s, $i);
    if ($i >= strlen($s)) throw new SnbtError('Ran out of input — something is missing at the end.');

    $c = $s[$i];
    if ($c === '{') return snbt_compound($s, $i);
    if ($c === '[') return snbt_listOrArray($s, $i);
    if ($c === '"' || $c === "'") return snbt_string($s, $i);
    return snbt_bare($s, $i);
}

function snbt_compound(string $s, int &$i): array
{
    snbt_expect($s, $i, '{');
    $out = [];
    snbt_ws($s, $i);
    if ($i < strlen($s) && $s[$i] === '}') { $i++; return ['t' => 'compound', 'v' => $out]; }

    while (true) {
        snbt_ws($s, $i);
        $key = ($i < strlen($s) && ($s[$i] === '"' || $s[$i] === "'"))
            ? snbt_string($s, $i)['v']
            : snbt_key($s, $i);
        snbt_expect($s, $i, ':');
        $out[$key] = snbt_value($s, $i);
        snbt_ws($s, $i);
        if ($i < strlen($s) && $s[$i] === ',') { $i++; continue; }
        break;
    }
    snbt_expect($s, $i, '}');
    return ['t' => 'compound', 'v' => $out];
}

function snbt_key(string $s, int &$i): string
{
    $start = $i;
    while ($i < strlen($s) && preg_match('/[A-Za-z0-9_.+\-]/', $s[$i])) $i++;
    if ($i === $start) throw new SnbtError('Expected a key name at position ' . $i . '.');
    return substr($s, $start, $i - $start);
}

function snbt_listOrArray(string $s, int &$i): array
{
    snbt_expect($s, $i, '[');
    snbt_ws($s, $i);

    // Typed arrays: [I;1,2,3] · [B;1b,0b] · [L;1L]
    if ($i + 1 < strlen($s) && in_array($s[$i], ['I', 'B', 'L'], true) && $s[$i + 1] === ';') {
        $prefix = $s[$i];
        $i += 2;
        $items = [];
        snbt_ws($s, $i);
        if ($i < strlen($s) && $s[$i] === ']') { $i++; return ['t' => 'array', 'v' => ['prefix' => $prefix, 'items' => $items]]; }
        while (true) {
            $items[] = snbt_value($s, $i);
            snbt_ws($s, $i);
            if ($i < strlen($s) && $s[$i] === ',') { $i++; continue; }
            break;
        }
        snbt_expect($s, $i, ']');
        return ['t' => 'array', 'v' => ['prefix' => $prefix, 'items' => $items]];
    }

    $items = [];
    if ($i < strlen($s) && $s[$i] === ']') { $i++; return ['t' => 'list', 'v' => $items]; }
    while (true) {
        $items[] = snbt_value($s, $i);
        snbt_ws($s, $i);
        if ($i < strlen($s) && $s[$i] === ',') { $i++; continue; }
        break;
    }
    snbt_expect($s, $i, ']');
    return ['t' => 'list', 'v' => $items];
}

function snbt_string(string $s, int &$i): array
{
    $quote = $s[$i++];
    $out = '';
    while ($i < strlen($s)) {
        $c = $s[$i];
        if ($c === '\\') {
            $i++;
            if ($i >= strlen($s)) throw new SnbtError('A backslash at the end of the text has nothing to escape.');
            $out .= $s[$i];
            $i++;
            continue;
        }
        if ($c === $quote) { $i++; return ['t' => 'string', 'v' => $out, 'q' => $quote]; }
        $out .= $c;
        $i++;
    }
    throw new SnbtError('An opening ' . $quote . ' quote is never closed.');
}

function snbt_bare(string $s, int &$i): array
{
    $start = $i;
    while ($i < strlen($s) && preg_match('/[A-Za-z0-9_.+\-]/', $s[$i])) $i++;
    if ($i === $start) throw new SnbtError('Unexpected character "' . $s[$i] . '" at position ' . $i . '.');
    $raw = substr($s, $start, $i - $start);
    if (preg_match('/^[+-]?(\d+\.?\d*|\.\d+)([bslfdBSLFD])?$/', $raw)) {
        return ['t' => 'number', 'v' => $raw];
    }
    return ['t' => 'word', 'v' => $raw];
}

// ── WRITING ──────────────────────────────────────
function snbt_write($node, string $quote = '"'): string
{
    switch ($node['t']) {
        case 'compound':
            $parts = [];
            foreach ($node['v'] as $k => $child) {
                $key = preg_match('/^[A-Za-z0-9_.+\-]+$/', $k) ? $k : snbt_quote($k, '"');
                $parts[] = $key . ':' . snbt_write($child, $quote);
            }
            return '{' . implode(',', $parts) . '}';
        case 'list':
            return '[' . implode(',', array_map(fn($n) => snbt_write($n, $quote), $node['v'])) . ']';
        case 'array':
            return '[' . $node['v']['prefix'] . ';'
                . implode(',', array_map(fn($n) => snbt_write($n, $quote), $node['v']['items'])) . ']';
        case 'string':
            return snbt_quote($node['v'], $node['q'] ?? $quote);
        default:
            return $node['v'];
    }
}

function snbt_quote(string $text, string $quote = '"'): string
{
    $out = str_replace('\\', '\\\\', $text);
    $out = str_replace($quote, '\\' . $quote, $out);
    return $quote . $out . $quote;
}

// ── HELPERS ──────────────────────────────────────
function snbt_node_string(string $text, string $quote = '"'): array
{
    return ['t' => 'string', 'v' => $text, 'q' => $quote];
}

function snbt_node_word(string $w): array { return ['t' => 'word', 'v' => $w]; }
function snbt_node_number(string $n): array { return ['t' => 'number', 'v' => $n]; }
function snbt_node_compound(array $map): array { return ['t' => 'compound', 'v' => $map]; }
function snbt_node_list(array $items): array { return ['t' => 'list', 'v' => $items]; }

/** Plain PHP value for a node, for reading data out of a parsed tree. */
function snbt_plain($node)
{
    switch ($node['t']) {
        case 'compound':
            $out = [];
            foreach ($node['v'] as $k => $c) $out[$k] = snbt_plain($c);
            return $out;
        case 'list':  return array_map('snbt_plain', $node['v']);
        case 'array': return array_map('snbt_plain', $node['v']['items']);
        case 'number':
            $raw = rtrim($node['v'], 'bslfdBSLFD');
            return str_contains($raw, '.') ? (float)$raw : (int)$raw;
        default: return $node['v'];
    }
}
