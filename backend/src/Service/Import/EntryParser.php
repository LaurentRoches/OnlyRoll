<?php

namespace App\Service\Import;

/**
 * Converts 5etools "entries" format into readable Markdown/text.
 *
 * The 5etools format uses inline tags like:
 *   {@damage 8d6}        → "8d6"
 *   {@dice 1d20}         → "1d20"
 *   {@creature goblin}   → "goblin"
 *   {@spell fireball}    → "Fireball"
 *   {@item longsword}    → "longsword"
 *   {@condition prone}   → "prone"
 *   {@book PHB|ch 3}     → "" (skip)
 *   {@filter ...|...}    → "" (skip)
 */
class EntryParser
{
    /**
     * Converts an entries array (5etools format) to a Markdown string.
     *
     * @param array|string $entries
     */
    public function entriesToMarkdown(array|string $entries): string
    {
        if (is_string($entries)) {
            return $this->parseText($entries);
        }

        $lines = [];
        foreach ($entries as $entry) {
            $lines[] = $this->parseEntry($entry);
        }

        return implode("\n\n", array_filter($lines));
    }

    private function parseEntry(mixed $entry): string
    {
        if (is_string($entry)) {
            return $this->parseText($entry);
        }

        if (!is_array($entry)) {
            return '';
        }

        $type = $entry['type'] ?? 'entries';

        return match ($type) {
            'entries' => $this->parseEntriesBlock($entry),
            'list'    => $this->parseList($entry),
            'table'   => $this->parseTable($entry),
            'quote'   => $this->parseQuote($entry),
            'inset'   => $this->parseInset($entry),
            'inline'  => is_string($entry['entries'][0] ?? '') ? $this->parseText($entry['entries'][0]) : $this->parseEntry($entry['entries'][0] ?? ''),
            default   => isset($entry['entries']) ? $this->entriesToMarkdown($entry['entries']) : '',
        };
    }

    private function parseEntriesBlock(array $entry): string
    {
        $parts = [];
        if (isset($entry['name'])) {
            $parts[] = '**' . $entry['name'] . '**';
        }
        if (isset($entry['entries'])) {
            $parts[] = $this->entriesToMarkdown($entry['entries']);
        }
        return implode("\n\n", $parts);
    }

    private function parseList(array $entry): string
    {
        $items = [];
        foreach ($entry['items'] ?? [] as $item) {
            $text = is_string($item) ? $this->parseText($item) : $this->parseEntry($item);
            $items[] = '- ' . $text;
        }
        return implode("\n", $items);
    }

    private function parseTable(array $entry): string
    {
        $lines = [];
        if (isset($entry['caption'])) {
            $lines[] = '**' . $entry['caption'] . '**';
        }

        $cols = $entry['colLabels'] ?? [];
        if (!empty($cols)) {
            $lines[] = '| ' . implode(' | ', $cols) . ' |';
            $lines[] = '|' . str_repeat(' --- |', count($cols));
        }

        foreach ($entry['rows'] ?? [] as $row) {
            $cells = array_map(fn($c) => is_string($c) ? $this->parseText($c) : '', $row);
            $lines[] = '| ' . implode(' | ', $cells) . ' |';
        }

        return implode("\n", $lines);
    }

    private function parseQuote(array $entry): string
    {
        $text = $this->entriesToMarkdown($entry['entries'] ?? []);
        return '> ' . str_replace("\n", "\n> ", $text);
    }

    private function parseInset(array $entry): string
    {
        $parts = [];
        if (isset($entry['name'])) {
            $parts[] = '### ' . $entry['name'];
        }
        if (isset($entry['entries'])) {
            $parts[] = $this->entriesToMarkdown($entry['entries']);
        }
        return implode("\n\n", $parts);
    }

    /**
     * Parses inline {@tag content} syntax into plain text.
     */
    public function parseText(string $text): string
    {
        // {@damage XdY} → XdY
        $text = preg_replace('/\{@damage\s+([^}]+)\}/', '$1', $text);
        // {@dice XdY} → XdY
        $text = preg_replace('/\{@dice\s+([^}]+)\}/', '$1', $text);
        // {@hit +X} → +X
        $text = preg_replace('/\{@hit\s+([^}]+)\}/', '$1', $text);
        // {@dc X} → DC X
        $text = preg_replace('/\{@dc\s+([^}]+)\}/', 'DC $1', $text);
        // {@spell Name} → Name
        $text = preg_replace('/\{@spell\s+([^|}]+)[^}]*\}/', '$1', $text);
        // {@creature Name} → Name
        $text = preg_replace('/\{@creature\s+([^|}]+)[^}]*\}/', '$1', $text);
        // {@item Name|source|display} → display (or Name if no display text)
        $text = preg_replace_callback('/\{@item\s+([^}]+)\}/', function ($m) {
            $parts = explode('|', $m[1]);
            return !empty($parts[2]) ? $parts[2] : $parts[0];
        }, $text);
        // {@condition Name} → Name
        $text = preg_replace('/\{@condition\s+([^|}]+)[^}]*\}/', '$1', $text);
        // {@skill Name} → Name
        $text = preg_replace('/\{@skill\s+([^|}]+)[^}]*\}/', '$1', $text);
        // {@action Name} → Name
        $text = preg_replace('/\{@action\s+([^|}]+)[^}]*\}/', '$1', $text);
        // {@sense Name} → Name
        $text = preg_replace('/\{@sense\s+([^|}]+)[^}]*\}/', '$1', $text);
        // {@bold text} → **text**
        $text = preg_replace('/\{@b\s+([^}]+)\}/', '**$1**', $text);
        $text = preg_replace('/\{@bold\s+([^}]+)\}/', '**$1**', $text);
        // {@italic text} → *text*
        $text = preg_replace('/\{@i\s+([^}]+)\}/', '*$1*', $text);
        $text = preg_replace('/\{@italic\s+([^}]+)\}/', '*$1*', $text);
        // {@note text} → (text)
        $text = preg_replace('/\{@note\s+([^}]+)\}/', '($1)', $text);
        // {@chance X} → X%
        $text = preg_replace('/\{@chance\s+(\d+)[^}]*\}/', '$1%', $text);
        // {@filter display text|...} → display text
        $text = preg_replace('/\{@filter\s+([^|}]+)[^}]*\}/', '$1', $text);
        // Remove remaining tags with content: {@tag content|extra} → content
        $text = preg_replace('/\{@\w+\s+([^|}]*)[^}]*\}/', '$1', $text);
        // Remove remaining empty tags: {@tag}
        $text = preg_replace('/\{@\w+\}/', '', $text);

        return trim($text);
    }
}
