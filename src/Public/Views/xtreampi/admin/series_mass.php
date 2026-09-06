<?php
require_once __DIR__ . '/_migration_helpers.php';

sc_m_bulk(
    'Mass Edit Series',
    'post.php?action=series_mass&referer=series_mass',
    'series',
    [
        ['name' => 'category_id', 'value_name' => 'category_id[]', 'label' => 'Categories', 'type' => 'textarea', 'placeholder' => 'Category IDs, separated by commas or spaces'],
        ['name' => 'category_id_type', 'label' => 'Category operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'bouquets', 'value_name' => 'bouquets[]', 'label' => 'Bouquets', 'type' => 'textarea', 'placeholder' => 'Bouquet IDs, separated by commas or spaces'],
        ['name' => 'bouquets_type', 'label' => 'Bouquet operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'reprocess_tmdb', 'label' => 'Reprocess TMDB metadata', 'type' => 'checkbox', 'apply' => false],
    ]
);
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
