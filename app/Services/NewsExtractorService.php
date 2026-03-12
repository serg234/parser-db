<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Extracts news items (title + content) from an imported database.
 *
 * Table name is detected by `ContentSchemaDetectorService`.
 */
class NewsExtractorService
{
    /**
     * Extract items from the given schema/table.
     *
     * @return array<int, array{title:string, content:string}>
     */
    public function extract(string $schemaName, string $postsTable): array
    {
        $schemaEsc = str_replace('`', '``', $schemaName);
        $tableEsc = str_replace('`', '``', $postsTable);

        $rows = DB::select(
            "SELECT post_title AS title, post_content AS content
             FROM `{$schemaEsc}`.`{$tableEsc}`
             WHERE post_status = 'publish'
               AND post_type IN ('post', 'page')
             ORDER BY ID ASC"
        );

        $items = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row->title ?? ''));
            $content = (string) ($row->content ?? '');

            if ($title === '' && trim($content) === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'content' => $content,
            ];
        }

        return $items;
    }
}

