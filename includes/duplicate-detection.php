<?php
/**
 * GEO Authority Suite - Duplicate Detection
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'entity_duplicate_check',
        'Verification des doublons',
        'geo_duplicate_check_meta_box',
        'entity',
        'side',
        'high'
    );
}, 15);

function geo_duplicate_check_meta_box($post) {

    $current_title = get_the_title($post);
    $canonical = get_post_meta($post->ID, '_entity_canonical', true);
    $search_name = !empty($canonical) ? $canonical : $current_title;

    if (empty($search_name)) {
        echo '<p class="description">Saisissez un titre pour verifier les doublons.</p>';
        return;
    }

    $types = wp_get_post_terms($post->ID, 'entity_type');
    $current_type = $types && !is_wp_error($types) ? $types[0]->name : '';

    $duplicates = geo_find_duplicate_entities($search_name, $current_type, $post->ID);

    if (empty($duplicates)) {
        ?>
        <div style="padding: 10px; background: #d4edda; border-left: 3px solid #28a745; margin-bottom: 10px;">
            <strong style="color: #155724;">Aucun doublon detecte</strong>
            <p style="margin: 5px 0 0; color: #155724; font-size: 12px;">
                Cette entite semble unique.
            </p>
        </div>
        <?php
    } else {
        ?>
        <div style="padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; margin-bottom: 10px;">
            <strong style="color: #856404;"><?php echo count($duplicates); ?> doublon(s) potentiel(s)</strong>
        </div>

        <div style="max-height: 300px; overflow-y: auto;">
            <?php foreach ($duplicates as $duplicate): ?>
                <div style="padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; margin-bottom: 10px; border-radius: 4px;">
                    <div style="flex: 1;">
                        <strong style="color: #495057;">
                            <?php echo esc_html($duplicate['title']); ?>
                        </strong>

                        <?php if (!empty($duplicate['type'])): ?>
                            <div style="margin: 5px 0;">
                                <span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html($duplicate['type']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 5px; font-size: 11px; color: #6c757d;">
                            Source: <?php echo esc_html($duplicate['source']); ?>
                        </div>
                    </div>

                    <?php if ($duplicate['source'] === 'Entite GEO' && $duplicate['id']): ?>
                        <div style="margin-top: 8px;">
                            <a href="<?php echo get_edit_post_link($duplicate['id']); ?>"
                               class="button button-small"
                               target="_blank">
                                Voir l'entite
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-left: 3px solid #0073aa;">
            <strong style="color: #004085;">Recommandation</strong>
            <p style="margin: 5px 0 0; color: #004085; font-size: 12px;">
                Si un doublon existe deja, supprimez cette entite et utilisez l'existante.
            </p>
        </div>
        <?php
    }

    ?>
    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
        <button type="button"
                onclick="location.reload();"
                class="button button-secondary button-small"
                style="width: 100%;">
            Reverifier
        </button>
    </div>
    <?php
}

function geo_find_duplicate_entities($name, $type = '', $exclude_id = 0) {

    $duplicates = [];
    $name_lower = strtolower(trim($name));

    $entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'exclude'        => $exclude_id ? [$exclude_id] : [],
    ]);

    foreach ($entities as $entity) {
        $entity_title = strtolower(get_the_title($entity));
        $entity_canonical = strtolower(get_post_meta($entity->ID, '_entity_canonical', true));

        if ($entity_title === $name_lower || $entity_canonical === $name_lower) {

            $entity_types = wp_get_post_terms($entity->ID, 'entity_type');
            $entity_type = $entity_types && !is_wp_error($entity_types) ? $entity_types[0]->name : '';

            if (!empty($type) && !empty($entity_type) && $entity_type !== $type) {
                continue;
            }

            $duplicates[] = [
                'id'     => $entity->ID,
                'title'  => get_the_title($entity),
                'type'   => $entity_type,
                'url'    => get_post_meta($entity->ID, '_entity_url', true),
                'source' => 'Entite GEO',
            ];
        }
    }

    if (empty($type) || $type === 'Organization') {
        $site_name = strtolower(get_bloginfo('name'));

        if ($site_name === $name_lower) {
            $duplicates[] = [
                'id'     => null,
                'title'  => get_bloginfo('name'),
                'type'   => 'Organization',
                'url'    => home_url('/'),
                'source' => 'Organization principale du site',
            ];
        }
    }

    if (empty($type) || $type === 'Person') {
        $users = get_users([
            'search'         => '*' . $name . '*',
            'search_columns' => ['display_name', 'user_nicename'],
            'number'         => 5,
        ]);

        foreach ($users as $user) {
            $user_name_lower = strtolower($user->display_name);

            if ($user_name_lower === $name_lower) {
                $duplicates[] = [
                    'id'     => $user->ID,
                    'title'  => $user->display_name,
                    'type'   => 'Person',
                    'url'    => get_author_posts_url($user->ID),
                    'source' => 'Auteur WordPress',
                ];
            }
        }
    }

    return $duplicates;
}

add_action('admin_footer', function () {

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'entity') {
        return;
    }

    ?>
    <script>
    jQuery(document).ready(function($) {

        var $worksForSelect = $('#entity_works_for');
        if ($worksForSelect.length) {
            var orgCount = $worksForSelect.find('option').length - 1;

            if (orgCount === 0) {
                $worksForSelect.after(
                    '<p class="description" style="color: #d63638; margin-top: 5px;">' +
                    'Aucune Organization trouvee. Creez d\'abord une entite de type "Organization".' +
                    '</p>'
                );
            }
        }

        function checkPersonType() {
            var selectedType = $('input[name="tax_input[entity_type][]"]:checked').val() ||
                               $('select[name="tax_input[entity_type][]"]').val();

            if (selectedType && selectedType.toLowerCase().includes('person')) {
                if ($worksForSelect.val() === '') {
                    $worksForSelect.css('border', '2px solid #d63638');
                    if (!$worksForSelect.next('.missing-relation-warning').length) {
                        $worksForSelect.after(
                            '<p class="missing-relation-warning" style="color: #d63638; margin-top: 5px; font-weight: 600;">' +
                            'Une Person devrait etre reliee a une Organization (worksFor)' +
                            '</p>'
                        );
                    }
                } else {
                    $worksForSelect.css('border', '');
                    $('.missing-relation-warning').remove();
                }
            } else {
                $worksForSelect.css('border', '');
                $('.missing-relation-warning').remove();
            }
        }

        checkPersonType();
        $('input[name="tax_input[entity_type][]"], select[name="tax_input[entity_type][]"]').on('change', checkPersonType);
        $worksForSelect.on('change', checkPersonType);
    });
    </script>
    <?php
});
