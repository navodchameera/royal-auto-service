<?php
defined('APP_PATH') or exit;

// A tiny one-page PDF writer (A4, Helvetica, text, lines and filled boxes). No libraries needed,
// so it runs on any free host. Text is converted to the PDF's Windows-1252 character set.
class SimplePdf
{
    private const W = 595.28;
    private const H = 841.89;
    private array $pages = [''];
    private string $footer = '';

    private static function esc(string $s): string
    {
        $s = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        $s = $s === false ? '' : $s;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $s);
    }

    private static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return sprintf('%.3F %.3F %.3F', hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255);
    }

    public function newPage(): void
    {
        $this->pages[] = '';
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    // Small grey line printed at the bottom of every page, with the page number
    public function footer(string $text): void
    {
        $this->footer = $text;
    }

    // Cuts text so it fits in $w points
    public function fit(string $s, float $w, float $size, bool $bold = false): string
    {
        if ($this->width($s, $size, $bold) <= $w) { return $s; }
        while ($s !== '' && $this->width($s . '..', $size, $bold) > $w) { $s = mb_substr($s, 0, -1); }
        return rtrim($s) . '..';
    }

    // y is measured from the top of the page
    public function rect(float $x, float $y, float $w, float $h, string $fill): void
    {
        $this->pages[count($this->pages) - 1] .= self::rgb($fill) . ' rg ' . sprintf('%.2F %.2F %.2F %.2F', $x, self::H - $y - $h, $w, $h) . " re f\n";
    }

    public function line(float $x1, float $y, float $x2, string $color = '#D5E0E8'): void
    {
        $this->pages[count($this->pages) - 1] .= self::rgb($color) . ' RG 0.8 w ' . sprintf('%.2F %.2F m %.2F %.2F l S', $x1, self::H - $y, $x2, self::H - $y) . "\n";
    }

    // $align: 'l' or 'r' (right aligned at $x)
    public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false, string $color = '#0B1B2B', string $align = 'l'): void
    {
        if ($align === 'r') { $x -= $this->width($s, $size, $bold); }
        $this->pages[count($this->pages) - 1] .= 'BT ' . self::rgb($color) . ' rg /' . ($bold ? 'F2' : 'F1') . ' ' . $size . ' Tf '
                 . sprintf('%.2F %.2F Td', $x, self::H - $y) . ' (' . self::esc($s) . ") Tj ET\n";
    }

    // Rough width in points (good enough for right alignment)
    public function width(string $s, float $size, bool $bold = false): float
    {
        $s = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s) ?: '';
        return strlen($s) * $size * ($bold ? 0.56 : 0.52);
    }

    public function output(): string
    {
        $n = count($this->pages);
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        for ($i = 0; $i < $n; $i++) { $kids[] = (5 + $i * 2) . ' 0 R'; }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $saved = $this->pages;
        for ($i = 0; $i < $n; $i++) {
            $this->pages = [''];
            if ($this->footer !== '') {
                $label = $this->footer . '  |  Page ' . ($i + 1) . ' of ' . $n;
                $this->text(self::W / 2 - $this->width($label, 8) / 2, self::H - 24, $label, 8, false, '#475B6C');
            }
            $content = $saved[$i] . $this->pages[0];
            $objs[5 + $i * 2] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . (6 + $i * 2) . ' 0 R >>';
            $objs[6 + $i * 2] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        }
        $this->pages = $saved;

        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $k => $body) {
            $offsets[$k] = strlen($out);
            $out .= $k . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) { $out .= sprintf("%010d 00000 n \n", $o); }
        $out .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $out;
    }
}
