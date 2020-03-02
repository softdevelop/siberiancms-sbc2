<?php
$schemas = (!isset($schemas)) ? array() : $schemas;
$schemas['media_gallery_image_flickr'] = array(
    'image_id' => array(
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ),
    'gallery_id' => array(
        'type' => 'int(11) unsigned',
        'foreign_key' => array(
            'table' => 'media_gallery_image',
            'column' => 'gallery_id',
            'name' => 'media_gallery_image_flickr_ibfk_1',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ),
        'index' => array(
            'key_name' => 'KEY_VALUE_ID',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ),
    ),
    'identifier' => array(
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ),
    // Could be
    'type' => array(
        'type' => 'enum(\'people\',\'gallery\')',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ),
);