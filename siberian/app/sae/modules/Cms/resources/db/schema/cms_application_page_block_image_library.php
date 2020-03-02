<?php
/**
 *
 * Schema definition for 'cms_application_page_block_image_library'
 *
 * Last update: 2016-04-28
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cms_application_page_block_image_library'] = [
    'image_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'library_id' => [
        'type' => 'int(11) unsigned',
    ],
    'image_url' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'image_fullsize_url' => [
        'type' => 'varchar(255)',
        'is_null' => true,
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
];