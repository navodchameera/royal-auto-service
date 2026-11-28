<?php
defined('APP_PATH') or exit;

// Turns a report (see Reports) into a CSV file or a PDF file
class ReportExport
{
    public static function csv(array $r, array $period): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");          // so Excel reads the file as UTF-8
        $put = function (array $cells) use ($out) { fputcsv($out, $cells, ',', '"', ''); };

        $put(['Royal Auto Service']);
        $put([$r['title']]);
        $put(['Period', $period['label']]);
        $put(['Created', date('j M Y, H:i')]);
        $put([$r['basis']]);
        $put([]);

        $put(['Key figures']);
        $put(['Measure', 'Value', 'Note']);
        foreach ($r['kpis'] as [$label, $value, $type, $note]) {
            $put([Reports::raw($label, 'text'), Reports::raw($value, $type), Reports::raw($note, 'text')]);
        }
        $put([]);

        $put(['What this means']);
        foreach ($r['insights'] as $line) { $put([Reports::raw($line, 'text')]); }
        $put([]);

        foreach ($r['sections'] as $sec) {
            $put([$sec['title']]);
            $put(array_map(function ($c) { return $c[0]; }, $sec['cols']));
            foreach ($sec['rows'] as $row) {
                $cells = [];
                foreach ($sec['cols'] as $i => $c) { $cells[] = Reports::raw($row[$i] ?? null, $c[1]); }
                $put($cells);
            }
            if (!$sec['rows']) { $put(['No data in this period']); }
            $put([]);
        }

        rewind($out);
        return stream_get_contents($out);
    }

    // Splits text into lines that fit $w points
    private static function wrap(SimplePdf $p, string $s, float $w, float $size): array
    {
        $lines = []; $cur = '';
        foreach (preg_split('/\s+/', trim($s)) as $word) {
            $try = $cur === '' ? $word : $cur . ' ' . $word;
            if ($cur !== '' && $p->width($try, $size) > $w) { $lines[] = $cur; $cur = $word; } else { $cur = $try; }
        }
        if ($cur !== '') { $lines[] = $cur; }
        return $lines;
    }

    public static function pdf(array $r, array $period): string
    {
        $p = new SimplePdf();
        $L = 48; $R = 547; $W = $R - $L;
        $p->footer('Royal Auto Service  |  ' . $r['title'] . '  |  ' . $period['label']);

        // Header band
        $p->rect(0, 0, 595.28, 84, '#0B1B2B');
        $p->text($L, 40, 'Royal Auto Service', 11, true, '#6FD0E0');
        $p->text($L, 64, $r['title'], 20, true, '#FFFFFF');
        $p->text($R, 40, 'Period', 8, false, '#B9C7D3', 'r');
        $p->text($R, 54, $period['label'], 10, true, '#FFFFFF', 'r');
        $p->text($R, 70, 'Created ' . date('j M Y, H:i'), 8, false, '#B9C7D3', 'r');
        $y = 104;

        $break = function (float $need) use ($p, &$y): bool {
            if ($y + $need > 800) { $p->newPage(); $y = 56; return true; }
            return false;
        };

        // Key figures: 4 per row
        $cw = ($W - 3 * 8) / 4;
        $n = count($r['kpis']);
        for ($i = 0; $i < $n; $i += 4) {
            $break(56);
            for ($j = 0; $j < 4 && $i + $j < $n; $j++) {
                [$label, $value, $type, $note] = $r['kpis'][$i + $j];
                $x = $L + $j * ($cw + 8);
                $p->rect($x, $y, $cw, 50, '#F0F8FA');
                $p->text($x + 8, $y + 14, $p->fit($label, $cw - 16, 7.5), 7.5, false, '#475B6C');
                $p->text($x + 8, $y + 31, $p->fit(Reports::fmt($value, $type), $cw - 16, 13, true), 13, true, '#0B1B2B');
                if ($note !== '') { $p->text($x + 8, $y + 43, $p->fit($note, $cw - 16, 7), 7, false, '#0A7C91'); }
            }
            $y += 58;
        }
        $y += 6;

        // What this means
        $break(40);
        $p->text($L, $y, 'WHAT THIS MEANS', 9, true, '#0A7C91');
        $p->line($L, $y + 6, $R);
        $y += 22;
        foreach ($r['insights'] as $line) {
            $lines = self::wrap($p, $line, $W - 14, 9.5);
            $break(14 * count($lines));
            foreach ($lines as $k => $ln) {
                if ($k === 0) { $p->rect($L, $y - 6, 4, 4, '#0A7C91'); }
                $p->text($L + 12, $y, $ln, 9.5);
                $y += 13;
            }
            $y += 3;
        }
        $y += 8;

        // Tables
        foreach ($r['sections'] as $sec) {
            $cols = $sec['cols'];
            $nc = count($cols);

            // Column widths from the content. Headers may wrap onto two lines, so only their longest
            // word counts. The font shrinks a little for wide tables.
            $size = 8.5;
            do {
                $nat = [];
                foreach ($cols as $i => $c) {
                    $m = 0;
                    foreach (preg_split('/\s+/', $c[0]) as $word) { $m = max($m, $p->width($word, $size, true)); }
                    foreach (array_slice($sec['rows'], 0, 80) as $row) {
                        $m = max($m, $p->width(Reports::fmt($row[$i] ?? null, $c[1]), $size));
                    }
                    $nat[$i] = min($m, 190) + 10;
                }
                $fits = array_sum($nat) <= $W;
                if (!$fits && $size > 7) { $size -= 0.5; }
            } while (!$fits && $size > 7);
            // spare room is shared out in proportion
            $scale = $W / array_sum($nat);
            $w = array_map(function ($x) use ($scale) { return $x * $scale; }, $nat);
            $hl = [];
            $hmax = 1;
            foreach ($cols as $i => $c) { $hl[$i] = self::wrap($p, $c[0], $w[$i] - 8, $size); $hmax = max($hmax, count($hl[$i])); }

            $header = function () use ($p, &$y, $cols, $w, $L, $R, $size, $hl, $hmax) {
                $p->rect($L, $y - 11, $R - $L, 6 + 10 * $hmax, '#EAF1F5');
                $x = $L;
                foreach ($cols as $i => $c) {
                    $right = in_array($c[1], ['money', 'int', 'pct', 'dec'], true);
                    foreach ($hl[$i] as $k => $line) {
                        if ($right) { $p->text($x + $w[$i] - 4, $y + $k * 10, $line, $size, true, '#0B1B2B', 'r'); } else { $p->text($x + 4, $y + $k * 10, $line, $size, true); }
                    }
                    $x += $w[$i];
                }
                $y += 6 + 10 * $hmax;
            };

            $break(70);
            $p->text($L, $y, strtoupper($sec['title']), 9, true, '#0A7C91');
            $y += 18;
            if (!empty($sec['note'])) { $p->text($L, $y - 4, $sec['note'], 8, false, '#475B6C'); $y += 10; }
            $header();

            if (!$sec['rows']) {
                $p->text($L + 4, $y, 'No data in this period', 8.5, false, '#475B6C');
                $y += 20;
                continue;
            }
            foreach ($sec['rows'] as $row) {
                if ($break(18)) { $header(); }
                $x = $L;
                foreach ($cols as $i => $c) {
                    $right = in_array($c[1], ['money', 'int', 'pct', 'dec'], true);
                    $cell = $p->fit(Reports::fmt($row[$i] ?? null, $c[1]), $w[$i] - 8, $size);
                    if ($right) { $p->text($x + $w[$i] - 4, $y, $cell, $size, false, '#0B1B2B', 'r'); } else { $p->text($x + 4, $y, $cell, $size); }
                    $x += $w[$i];
                }
                $p->line($L, $y + 5, $R, '#EEF3F6');
                $y += 15;
            }
            $y += 16;
        }

        $break(30);
        $p->text($L, $y, $r['basis'], 8, false, '#475B6C');
        return $p->output();
    }
}
