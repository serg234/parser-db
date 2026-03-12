<?php

/**
 * Project parser configuration.
 *
 * Defines:
 * - where SQL dump files are stored
 * - where export files are generated
 * - how temporary databases are created/imported
 * - which HTML tags are allowed after cleaning content
 */
return [
    'storage' => [
        'dumps_dir' => env('PARSER_DUMPS_DIR', 'dumps'),
        'exports_dir' => env('PARSER_EXPORTS_DIR', 'exports'),
    ],

    'import' => [
        'mysql_bin' => env('PARSER_MYSQL_BIN', 'mysql'),
        'tmp_db_prefix' => env('PARSER_TMP_DB_PREFIX', 'tmp_content_'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    'content' => [
        'allowed_html_tags' => [
            'p',
            'br',
            'strong',
            'b',
            'em',
            'i',
            'u',
            'blockquote',
            'ul',
            'ol',
            'li',
        ],
    ],
];

