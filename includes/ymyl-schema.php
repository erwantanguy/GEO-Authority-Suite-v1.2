<?php
/**
 * GEO Authority Suite - YMYL Schema
 *
 * Detecte les contenus YMYL (Your Money, Your Life) et ajoute
 * les proprietes author / reviewedBy au JSON-LD pour renforcer l'EEAT.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detecte si un contenu est YMYL.
 *
 * @param string $content Contenu texte.
 * @param string $title   Titre.
 * @return array ['is_ymyl' => bool, 'categories' => string[]]
 */
function geo_detect_ymyl($content, $title = '') {
    $text = mb_strtolower($content . ' ' . $title, 'UTF-8');

    $categories = [
        'Sante'    => ['santé', 'maladie', 'symptôme', 'traitement', 'médicament', 'médecin', 'hôpital', 'clinique', 'diagnostic', 'thérapie', 'vaccin', 'nutrition', 'régime', 'bien-être', 'psychologie', 'dépression', 'anxiété', 'cancer', 'diabète', 'allergie', 'grossesse'],
        'Finance'  => ['argent', 'investissement', 'banque', 'crédit', 'prêt', 'assurance', 'impôt', 'fiscalité', 'retraite', 'bourse', 'crypto', 'épargne', 'hypothèque', 'placements', 'dettes', 'emprunt'],
        'Juridique' => ['avocat', 'notaire', 'juge', 'tribunal', 'justice', 'loi', 'règlement', 'droit', 'contrat', 'procès', 'infraction', 'pénal', 'juridique', 'succession', 'divorce', 'plainte'],
        'Securite' => ['sécurité', 'danger', 'risque', 'accident', 'protection', 'prévention', 'incendie', 'secours', 'urgence', 'équipement de protection', 'accessibilité', 'sécurité routière'],
    ];

    $detected = [];
    foreach ($categories as $label => $keywords) {
        $matches = 0;
        foreach ($keywords as $keyword) {
            if (mb_strpos($text, $keyword) !== false) {
                $matches++;
                if ($matches >= 2) {
                    $detected[] = $label;
                    break;
                }
            }
        }
    }

    return [
        'is_ymyl'    => count($detected) > 0,
        'categories' => $detected,
    ];
}

/**
 * Construit la reference Person de l'auteur d'un contenu.
 * Priorite : entite Person correspondante, puis auteur WordPress.
 *
 * @param WP_Post $post
 * @return array|null Schema Person ou null.
 */
function geo_ymyl_get_author_schema($post) {
    // 1. Chercher une entite Person correspondant a l'auteur
    $author_id = (int) $post->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);

    if ($author_name) {
        $entities = get_posts([
            'post_type'      => 'entity',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            's'              => $author_name,
        ]);

        foreach ($entities as $entity) {
            $types = wp_get_post_terms($entity->ID, 'entity_type');
            if ($types && !is_wp_error($types) && $types[0]->name === 'Person') {
                $canonical = get_post_meta($entity->ID, '_entity_canonical', true);
                $name = !empty($canonical) ? $canonical : get_the_title($entity);
                return [
                    '@type' => 'Person',
                    '@id'   => geo_entity_id('Person', sanitize_title($name)),
                    'name'  => $name,
                ];
            }
        }
    }

    // 2. Fallback : auteur WordPress
    if ($author_name) {
        return [
            '@type' => 'Person',
            'name'  => $author_name,
            'url'   => get_author_posts_url($author_id),
        ];
    }

    // 3. Fallback : Organization du site
    $org_entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
    ]);

    foreach ($org_entities as $entity) {
        $types = wp_get_post_terms($entity->ID, 'entity_type');
        if ($types && !is_wp_error($types) && $types[0]->name === 'Organization') {
            $canonical = get_post_meta($entity->ID, '_entity_canonical', true);
            $name = !empty($canonical) ? $canonical : get_the_title($entity);
            return [
                '@type' => 'Organization',
                '@id'   => geo_entity_id('Organization', sanitize_title($name)),
                'name'  => $name,
            ];
        }
    }

    return null;
}

/**
 * Cherche une entite Person "expert" pour reviewedBy.
 * Utilise la premiere entite Person disponible.
 *
 * @return array|null
 */
function geo_ymyl_get_reviewer_schema() {
    $entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
    ]);

    foreach ($entities as $entity) {
        $types = wp_get_post_terms($entity->ID, 'entity_type');
        if ($types && !is_wp_error($types) && $types[0]->name === 'Person') {
            $job_title = get_post_meta($entity->ID, '_entity_job_title', true);
            $canonical = get_post_meta($entity->ID, '_entity_canonical', true);
            $name = !empty($canonical) ? $canonical : get_the_title($entity);

            // Prioriser les Person avec un jobTitle (expert identifie)
            if (!empty($job_title)) {
                return [
                    '@type' => 'Person',
                    '@id'   => geo_entity_id('Person', sanitize_title($name)),
                    'name'  => $name,
                ];
            }
        }
    }

    // Fallback : la premiere Person sans jobTitle
    foreach ($entities as $entity) {
        $types = wp_get_post_terms($entity->ID, 'entity_type');
        if ($types && !is_wp_error($types) && $types[0]->name === 'Person') {
            $canonical = get_post_meta($entity->ID, '_entity_canonical', true);
            $name = !empty($canonical) ? $canonical : get_the_title($entity);
            return [
                '@type' => 'Person',
                '@id'   => geo_entity_id('Person', sanitize_title($name)),
                'name'  => $name,
            ];
        }
    }

    return null;
}

/**
 * Sort le JSON-LD EEAT pour les contenus YMYL.
 */
add_action('wp_head', function () {
    if (!is_singular()) {
        return;
    }

    $post = get_queried_object();
    if (!$post || !in_array($post->post_status, ['publish'])) {
        return;
    }

    if (!in_array($post->post_type, ['post', 'page'], true)) {
        return;
    }

    $ymyl = geo_detect_ymyl($post->post_content, get_the_title($post));

    // Permettre de forcer YMYL via meta
    $forced = get_post_meta($post->ID, '_geo_force_ymyl', true);
    if ($forced === 'yes') {
        $ymyl['is_ymyl'] = true;
        if (!in_array('Manuel', $ymyl['categories'], true)) {
            $ymyl['categories'][] = 'Manuel';
        }
    }

    if (!$ymyl['is_ymyl']) {
        return;
    }

    $author_schema = geo_ymyl_get_author_schema($post);
    $reviewer_schema = geo_ymyl_get_reviewer_schema();

    if (!$author_schema && !$reviewer_schema) {
        return;
    }

    $schema = [
        '@context'     => 'https://schema.org',
        '@type'        => 'Article',
        '@id'          => get_permalink($post) . '#article',
        'headline'     => get_the_title($post),
        'url'          => get_permalink($post),
        'mainEntityOfPage' => get_permalink($post),
        'datePublished' => get_the_date('c', $post),
        'dateModified'  => get_the_modified_date('c', $post),
    ];

    if ($author_schema) {
        $schema['author'] = $author_schema;
    }

    if ($reviewer_schema && (!$author_schema || $reviewer_schema['@id'] !== ($author_schema['@id'] ?? ''))) {
        $schema['reviewedBy'] = $reviewer_schema;
    }

    if (has_post_thumbnail($post)) {
        $schema['image'] = get_the_post_thumbnail_url($post, 'full');
    }

    /**
     * Filtre le schema EEAT genere pour les contenus YMYL.
     *
     * @param array   $schema Schema JSON-LD.
     * @param WP_Post $post   Contenu courant.
     * @param array   $ymyl   Detection YMYL.
     */
    $schema = apply_filters('geo_ymyl_schema', $schema, $post, $ymyl);

    echo "\n" . '<!-- GEO Authority Suite : EEAT / YMYL (' . esc_attr(implode(', ', $ymyl['categories'])) . ') -->' . "\n";
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}, 120);

/**
 * Metabox pour forcer le statut YMYL d'un contenu.
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'geo_ymyl_box',
        'Statut YMYL (GEO Authority Suite)',
        'geo_ymyl_meta_box',
        ['post', 'page'],
        'side',
        'default'
    );
});

function geo_ymyl_meta_box($post) {
    $forced = get_post_meta($post->ID, '_geo_force_ymyl', true);
    $ymyl = geo_detect_ymyl($post->post_content, $post->post_title);

    wp_nonce_field('geo_ymyl_save', 'geo_ymyl_nonce');
    ?>
    <p>
        <label>
            <input type="checkbox" name="geo_force_ymyl" value="yes" <?php checked($forced, 'yes'); ?>>
            <?php esc_html_e('Forcer le statut YMYL (schema EEAT renforce)', 'geo-authority-suite'); ?>
        </label>
    </p>
    <?php if ($ymyl['is_ymyl']) : ?>
        <p style="color: #dba617; margin-bottom: 0;">
            <strong><?php esc_html_e('Detection automatique :', 'geo-authority-suite'); ?></strong>
            <?php echo esc_html(implode(', ', $ymyl['categories'])); ?>
        </p>
    <?php else : ?>
        <p class="description" style="margin-bottom: 0;">
            <?php esc_html_e('Detection automatique : non YMYL', 'geo-authority-suite'); ?>
        </p>
    <?php endif;
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['geo_ymyl_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['geo_ymyl_nonce'])), 'geo_ymyl_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['geo_force_ymyl']) && $_POST['geo_force_ymyl'] === 'yes') {
        update_post_meta($post_id, '_geo_force_ymyl', 'yes');
    } else {
        delete_post_meta($post_id, '_geo_force_ymyl');
    }
});
