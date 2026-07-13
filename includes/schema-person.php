<?php
/**
 * GEO Authority Suite - Schema Person (auteurs WordPress)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {

    if (!is_singular()) {
        return;
    }

    $author_id = get_post_field('post_author', get_queried_object_id());
    if (!$author_id) {
        return;
    }

    // --- Liaison prioritaire : utilisateur WordPress lie a une entite Person ---
    $linked_person_id = absint(get_user_meta($author_id, '_geo_person_entity_id', true));
    if ($linked_person_id) {
        $linked_person = get_post($linked_person_id);
        if ($linked_person && $linked_person->post_type === 'entity' && $linked_person->post_status === 'publish') {
            $person_schema = geo_build_entity_schema($linked_person);
            if (!empty($person_schema)) {
                // S'assurer que worksFor/memberOf pointent vers la bonne Organization principale
                $person_schema = geo_fix_person_organization_refs($person_schema);
                geo_register_entity($person_schema);
                return;
            }
        }
    }

    // --- Fallback : generation automatique a partir du profil WordPress ---
    $author_name = get_the_author_meta('display_name', $author_id);
    if (empty($author_name)) {
        return;
    }

    $author_description = get_the_author_meta('description', $author_id);
    $author_url = get_author_posts_url($author_id);

    $person = [
        '@type' => 'Person',
        '@id'   => geo_entity_id('person', sanitize_title($author_name)),
        'name'  => $author_name,
        'url'   => $author_url,
    ];

    if (!empty($author_description)) {
        $person['description'] = $author_description;
    }

    $avatar_url = get_avatar_url($author_id, ['size' => 200]);
    if ($avatar_url) {
        $person['image'] = $avatar_url;
    }

    $person = geo_fix_person_organization_refs($person);

    $social_links = [];
    $author_twitter = get_the_author_meta('twitter', $author_id);
    $author_linkedin = get_the_author_meta('linkedin', $author_id);
    $author_facebook = get_the_author_meta('facebook', $author_id);

    if ($author_twitter) $social_links[] = $author_twitter;
    if ($author_linkedin) $social_links[] = $author_linkedin;
    if ($author_facebook) $social_links[] = $author_facebook;

    if (!empty($social_links)) {
        $person['sameAs'] = $social_links;
    }

    geo_register_entity($person);

}, 21);

add_action('show_user_profile', 'geo_add_social_fields_to_profile');
add_action('edit_user_profile', 'geo_add_social_fields_to_profile');

function geo_add_social_fields_to_profile($user) {
    $linked_person_id = absint(get_user_meta($user->ID, '_geo_person_entity_id', true));

    $person_entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'entity_type',
                'field'    => 'name',
                'terms'    => 'Person',
            ],
        ],
        'orderby' => 'title',
        'order'   => 'ASC',
    ]);
    ?>
    <h2>Entite GEO liee (Schema.org Person)</h2>
    <table class="form-table">
        <tr>
            <th><label for="geo_person_entity_id">Lier a une entite Person</label></th>
            <td>
                <select name="geo_person_entity_id" id="geo_person_entity_id" style="max-width: 100%;">
                    <option value="0">-- Aucune (utiliser le profil WordPress) --</option>
                    <?php foreach ($person_entities as $person): ?>
                        <option value="<?php echo esc_attr($person->ID); ?>" <?php selected($linked_person_id, $person->ID); ?>>
                            <?php echo esc_html(get_the_title($person)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    Si selectionne, le schema JSON-LD de l'auteur utilisera cette entite Person au lieu du profil WordPress.
                </p>
            </td>
        </tr>
    </table>

    <h2>Reseaux Sociaux (pour Schema.org)</h2>
    <table class="form-table">
        <tr>
            <th><label for="twitter">Twitter</label></th>
            <td>
                <input type="url" name="twitter" id="twitter"
                       value="<?php echo esc_attr(get_the_author_meta('twitter', $user->ID)); ?>"
                       class="regular-text"
                       placeholder="https://twitter.com/username" />
            </td>
        </tr>
        <tr>
            <th><label for="linkedin">LinkedIn</label></th>
            <td>
                <input type="url" name="linkedin" id="linkedin"
                       value="<?php echo esc_attr(get_the_author_meta('linkedin', $user->ID)); ?>"
                       class="regular-text"
                       placeholder="https://linkedin.com/in/username" />
            </td>
        </tr>
        <tr>
            <th><label for="facebook">Facebook</label></th>
            <td>
                <input type="url" name="facebook" id="facebook"
                       value="<?php echo esc_attr(get_the_author_meta('facebook', $user->ID)); ?>"
                       class="regular-text"
                       placeholder="https://facebook.com/username" />
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'geo_save_social_fields');
add_action('edit_user_profile_update', 'geo_save_social_fields');

function geo_save_social_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta($user_id, 'twitter', esc_url_raw($_POST['twitter'] ?? ''));
    update_user_meta($user_id, 'linkedin', esc_url_raw($_POST['linkedin'] ?? ''));
    update_user_meta($user_id, 'facebook', esc_url_raw($_POST['facebook'] ?? ''));

    if (isset($_POST['geo_person_entity_id'])) {
        $person_id = absint($_POST['geo_person_entity_id']);
        if ($person_id > 0) {
            update_user_meta($user_id, '_geo_person_entity_id', $person_id);
        } else {
            delete_user_meta($user_id, '_geo_person_entity_id');
        }
    }
}

/**
 * Corrige les references worksFor/memberOf d'une Person pour pointer
 * vers le @id exact de l'Organization principale du site.
 */
function geo_fix_person_organization_refs(array $person_schema): array {
    $main_org_id = geo_get_main_organization_id();
    if (!$main_org_id) {
        return $person_schema;
    }

    $main_org = get_post($main_org_id);
    if (!$main_org) {
        return $person_schema;
    }

    $canonical = get_post_meta($main_org_id, '_entity_canonical', true);
    $org_name = !empty($canonical) ? $canonical : get_the_title($main_org);
    $org_id = geo_entity_id('organization', sanitize_title($org_name));

    foreach (['worksFor', 'memberOf'] as $relation) {
        if (isset($person_schema[$relation])) {
            $person_schema[$relation] = ['@id' => $org_id];
        }
    }

    return $person_schema;
}
