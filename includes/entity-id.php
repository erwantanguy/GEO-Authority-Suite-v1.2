<?php
/**
 * GEO Authority Suite - Entity ID Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

function geo_entity_id(string $type, string $slug = ''): string {
    $base = trailingslashit(home_url());
    $type = sanitize_key($type);

    $id = $type;
    if (!empty($slug)) {
        $slug = sanitize_title($slug);
        if (strlen($slug) > 50) {
            $slug = substr($slug, 0, 50);
        }
        $id .= '-' . $slug;
    }

    return $base . '#' . $id;
}
