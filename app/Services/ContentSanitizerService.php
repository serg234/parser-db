<?php

namespace App\Services;

/**
 * Cleans extracted HTML content.
 *
 * Responsibilities:
 * - remove images
 * - remove links but keep their text
 * - keep a limited set of safe formatting tags
 * - provide both HTML-safe output and plain-text output
 */
class ContentSanitizerService
{
    /**
     * Sanitize HTML content for export:
     * - удалить теги <img>
     * - развернуть ссылки (<a>) в их текст
     * - оставить только безопасные форматирующие теги из конфигурации
     */
    public function sanitize(string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // 1) Удаляем все изображения полностью.
        $html = preg_replace('#<img\b[^>]*>#i', '', $html) ?? $html;

        // 2) Разворачиваем ссылки: <a href="...">Текст</a> -> Текст
        // Флаг "is": i — регистр, s — многострочный/с точкой по \n.
        $html = preg_replace('#<a\b[^>]*>(.*?)</a>#is', '$1', $html) ?? $html;

        // 3) Оставляем только разрешённые теги (остальное — голый текст).
        $allowedTags = (array) config('parser.content.allowed_html_tags', []);
        $allowed = '';
        foreach ($allowedTags as $tag) {
            $allowed .= '<' . $tag . '>';
        }

        $result = strip_tags($html, $allowed);
        $result = $this->normalizeLineBreaks($result);

        return trim($result);
    }

    /**
     * Convert content to plain text after applying sanitize rules.
     */
    public function toPlainText(string $html): string
    {
        $clean = $this->sanitize($html);
        $text = html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = $this->normalizeLineBreaks($text);

        return trim($text);
    }

    /**
     * Normalize line breaks to `\n` and collapse excessive empty lines.
     */
    private function normalizeLineBreaks(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

        return $value;
    }
}

