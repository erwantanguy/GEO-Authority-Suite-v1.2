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

    $author_name = get_the_author_meta('display_name', $author_id);
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

    $person['worksFor'] = [
        '@id' => geo_entity_id('organization'),
    ];

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
    ?>
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
}
