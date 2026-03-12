<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Detects content-related tables in an imported database.
 *
 * Goal: find the table that contains title/text fields to extract news items.
 * This is done by scanning tables ending with `_posts` and checking required columns.
 */
class ContentSchemaDetectorService
{
    /**
     * Detect a content table inside a database schema.
     *
     * @throws \RuntimeException when suitable table is not found
     */
    public function detectPostsTable(string $schemaName): string
    {
        $candidates = DB::select(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME LIKE ? ESCAPE '\\\\'
             ORDER BY TABLE_NAME ASC",
            [$schemaName, '%\\_posts']
        );

        if (empty($candidates)) {
            throw new \RuntimeException('No content tables were found in imported database.');
        }

        $requiredColumns = [
            'ID',
            'post_title',
            'post_content',
            'post_status',
            'post_type',
        ];

        foreach ($candidates as $row) {
            $table = (string) $row->TABLE_NAME;
            $count = (int) (DB::selectOne(
                "SELECT COUNT(*) AS cnt
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME IN (" . implode(',', array_fill(0, count($requiredColumns), '?')) . ")",
                array_merge([$schemaName, $table], $requiredColumns)
            )->cnt ?? 0);

            if ($count === count($requiredColumns)) {
                return $table;
            }
        }

        throw new \RuntimeException('Content table was not detected (missing required columns).');
    }
}

