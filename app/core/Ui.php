<?php
defined('APP_PATH') or exit;

// Small HTML helpers shared by the views
class Ui
{
    // Attributes that make a <form> ask first in the confirmation dialog
    public static function confirm(string $title, string $text, string $ok, string $tone = '', string $back = ''): string
    {
        return ' data-confirm="' . e($text) . '" data-confirm-title="' . e($title) . '" data-confirm-ok="' . e($ok) . '"'
            . ($tone !== '' ? ' data-confirm-tone="' . e($tone) . '"' : '')
            . ($back !== '' ? ' data-confirm-back="' . e($back) . '"' : '');
    }

    // A "three dots" button that opens a small menu. Each item is a POST form with its own confirmation.
    // Item keys: label, url, csrf, fields[], title, text, ok, tone ('danger' = red confirm button),
    //            red (true = red menu text)
    public static function rowMenu(string $id, string $label, array $items): string
    {
        $h = '<div class="kebab">'
           . '<button class="kebab-btn" type="button" data-menu aria-haspopup="menu" aria-expanded="false"'
           . ' aria-controls="' . e($id) . '" aria-label="' . e($label) . '">'
           . '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
           . '<circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></button>'
           . '<div class="menu-pop" id="' . e($id) . '" role="menu" hidden>';

        foreach ($items as $i => $it) {
            if (!empty($it['red']) && $i > 0) { $h .= '<div class="menu-sep" role="separator"></div>'; }
            $h .= '<form method="post" action="' . e($it['url']) . '" role="none"'
                . self::confirm($it['title'], $it['text'], $it['ok'], $it['tone'] ?? '') . '>'
                . '<input type="hidden" name="csrf" value="' . e($it['csrf']) . '">';
            foreach ($it['fields'] as $name => $value) {
                $h .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
            }
            $h .= '<button type="submit" role="menuitem" class="menu-item' . (!empty($it['red']) ? ' red' : '') . '">'
                . e($it['label']) . '</button></form>';
        }
        return $h . '</div></div>';
    }

    // A small POST form with one button. $confirm = [title, text, okLabel] (optional)
    public static function postButton(string $url, string $csrf, array $fields, string $label, string $class = '', array $confirm = []): string
    {
        $h = '<form method="post" action="' . e($url) . '"'
           . ($confirm ? self::confirm($confirm[0], $confirm[1], $confirm[2]) : '') . '>'
           . '<input type="hidden" name="csrf" value="' . e($csrf) . '">';
        foreach ($fields as $name => $value) {
            $h .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
        }
        return $h . '<button class="btn ' . e($class) . '" type="submit">' . e($label) . '</button></form>';
    }
}
