<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Generates export files for extracted news items.
 *
 * Supported formats:
 * - xml: output with CDATA for text
 * - csv: two columns (title, text)
 * - txt: simple human-readable format
 */
class NewsExportService
{
    /**
     * Write export file to local storage path.
     *
     * @param array<int, array{title:string, content:string}> $items
     * @return array{size_bytes:int|null, items_count:int}
     */
    public function export(array $items, string $format, string $relativePath): array
    {
        $disk = Storage::disk('local');

        $dir = dirname($relativePath);
        if ($dir !== '.' && $dir !== '') {
            $disk->makeDirectory($dir);
        }

        $absolutePath = $disk->path($relativePath);

        switch (strtolower($format)) {
            case 'xml':
                $this->writeXml($items, $absolutePath);
                break;
            case 'csv':
                $this->writeCsv($items, $absolutePath);
                break;
            case 'txt':
                $this->writeTxt($items, $absolutePath);
                break;
            default:
                throw new \InvalidArgumentException('Unsupported export format.');
        }

        $size = @filesize($absolutePath);

        return [
            'size_bytes' => $size !== false ? (int) $size : null,
            'items_count' => count($items),
        ];
    }

    /**
     * Write XML export.
     *
     * @param array<int, array{title:string, content:string}> $items
     */
    private function writeXml(array $items, string $absolutePath): void
    {
        $writer = new \XMLWriter();
        $writer->openURI($absolutePath);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        $writer->startElement('news');

        foreach ($items as $item) {
            $writer->startElement('item');

            $writer->writeElement('title', (string) ($item['title'] ?? ''));
            $writer->startElement('text');
            $writer->writeCData((string) ($item['content'] ?? ''));
            $writer->endElement(); // text

            $writer->endElement(); // item
        }

        $writer->endElement(); // news
        $writer->endDocument();
        $writer->flush();
    }

    /**
     * Write CSV export.
     *
     * @param array<int, array{title:string, content:string}> $items
     */
    private function writeCsv(array $items, string $absolutePath): void
    {
        $fh = fopen($absolutePath, 'wb');
        if ($fh === false) {
            throw new \RuntimeException('Failed to create export file.');
        }

        fputcsv($fh, ['title', 'text']);

        foreach ($items as $item) {
            fputcsv($fh, [
                (string) ($item['title'] ?? ''),
                (string) ($item['content'] ?? ''),
            ]);
        }

        fclose($fh);
    }

    /**
     * Write TXT export.
     *
     * @param array<int, array{title:string, content:string}> $items
     */
    private function writeTxt(array $items, string $absolutePath): void
    {
        $fh = fopen($absolutePath, 'wb');
        if ($fh === false) {
            throw new \RuntimeException('Failed to create export file.');
        }

        foreach ($items as $idx => $item) {
            if ($idx > 0) {
                fwrite($fh, "\n\n---\n\n");
            }

            fwrite($fh, (string) ($item['title'] ?? ''));
            fwrite($fh, "\n\n");
            fwrite($fh, (string) ($item['content'] ?? ''));
        }

        fclose($fh);
    }
}

