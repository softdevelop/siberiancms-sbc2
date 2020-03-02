<?php
/**
 *
 * Schema definition for 'cms_application_page_block_slider'
 *
 * Last update: 2016-04-28
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cms_application_page_block_slider'] = [
    'slider_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'cms_application_page_block',
            'column' => 'value_id',
            'name' => 'cms_application_page_block_slider_ibfk_1',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'KEY_VALUE_ID',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'image' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'duration' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'allow_line_return' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'library_id' => [
        'type' => 'int(11)',
        'is_null' => true,
    ],
    'description' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'layout' => [
        'type' => 'text',
        'is_null' => true,
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
];