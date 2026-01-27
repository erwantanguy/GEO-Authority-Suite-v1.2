<?php
/**
 * GEO Authority Suite - CPT Entity + Taxonomy
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {

    register_taxonomy('entity_type', 'entity', [
        'labels' => [
            'name'          => 'Types d\'entites',
            'singular_name' => 'Type d\'entite',
            'add_new_item'  => 'Ajouter un type',
            'edit_item'     => 'Modifier le type',
            'search_items'  => 'Rechercher des types',
            'all_items'     => 'Tous les types',
        ],
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => false,
    ]);

    register_post_type('entity', [
        'labels' => [
            'name'               => 'Entites',
            'singular_name'      => 'Entite',
            'add_new'            => 'Ajouter une entite',
            'add_new_item'       => 'Ajouter une nouvelle entite',
            'edit_item'          => 'Modifier l\'entite',
            'new_item'           => 'Nouvelle entite',
            'view_item'          => 'Voir l\'entite',
            'search_items'       => 'Rechercher des entites',
            'not_found'          => 'Aucune entite trouvee',
            'not_found_in_trash' => 'Aucune entite dans la corbeille',
            'all_items'          => 'Toutes les entites',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-networking',
        'menu_position'       => 20,
        'supports'            => ['title', 'editor', 'thumbnail'],
        'taxonomies'          => ['entity_type'],
        'has_archive'         => false,
        'rewrite'             => false,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
    ]);

    $default_types = ['Organization', 'Person', 'LocalBusiness', 'Product', 'Service', 'Place', 'Event'];
    foreach ($default_types as $type) {
        if (!term_exists($type, 'entity_type')) {
            wp_insert_term($type, 'entity_type');
        }
    }
});

add_filter('manage_entity_posts_columns', function ($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['entity_type'] = 'Type';
    $new_columns['canonical'] = 'Nom canonique';
    $new_columns['semantic_id'] = '@id';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
});

add_action('manage_entity_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
        case 'entity_type':
            $types = wp_get_post_terms($post_id, 'entity_type');
            if ($types && !is_wp_error($types)) {
                $names = array_map(function ($type) {
                    return $type->name;
                }, $types);
                echo esc_html(implode(', ', $names));
            } else {
                echo '—';
            }
            break;

        case 'canonical':
            $canonical = get_post_meta($post_id, '_entity_canonical', true);
            echo $canonical ? esc_html($canonical) : '—';
            break;

        case 'semantic_id':
            $types = wp_get_post_terms($post_id, 'entity_type');
            $type = $types && !is_wp_error($types) ? $types[0]->name : 'Thing';
            $name = get_the_title($post_id);
            $id = geo_entity_id($type, sanitize_title($name));
            $fragment = parse_url($id, PHP_URL_FRAGMENT);
            echo '<code style="font-size: 11px;">#' . esc_html($fragment) . '</code>';
            break;
    }
}, 10, 2);
