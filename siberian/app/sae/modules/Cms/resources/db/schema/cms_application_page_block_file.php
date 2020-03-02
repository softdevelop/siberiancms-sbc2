<?php
/**
 *
 * Schema definition for 'cms_application_page_block_file'
 *
 * Last update: 2016-04-28
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cms_application_page_block_file'] = [
    'file_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'cms_application_page_block',
            'column' => 'value_id',
            'name' => 'FK_CMS_APPLICATION_PAGE_BLOCK_FILE_VALUE_ID',
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
    'name' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'original_name' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'label' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
];