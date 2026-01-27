<?php
/**
 * GEO Authority Suite - Meta Boxes
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', function () {

    add_meta_box(
        'entity_details',
        'Details de l\'entite',
        'geo_entity_details_meta_box',
        'entity',
        'normal',
        'high'
    );

    add_meta_box(
        'entity_schema_properties',
        'Proprietes Schema.org',
        'geo_entity_schema_meta_box',
        'entity',
        'normal',
        'high'
    );

    add_meta_box(
        'entity_relations',
        'Relations avec d\'autres entites',
        'geo_entity_relations_meta_box',
        'entity',
        'side',
        'default'
    );

    $ai_indexing_post_types = apply_filters('geo_ai_indexing_post_types', ['post', 'page']);
    foreach ($ai_indexing_post_types as $post_type) {
        add_meta_box(
            'geo_ai_indexing',
            'Indexation IA',
            'geo_ai_indexing_meta_box',
            $post_type,
            'side',
            'default'
        );
    }

});

function geo_entity_details_meta_box($post) {
    wp_nonce_field('geo_entity_meta', 'geo_entity_nonce');

    $canonical = get_post_meta($post->ID, '_entity_canonical', true);
    $synonyms = get_post_meta($post->ID, '_entity_synonyms', true);
    $url = get_post_meta($post->ID, '_entity_url', true);
    ?>

    <table class="form-table">
        <tr>
            <th><label for="entity_canonical">Nom canonique</label></th>
            <td>
                <input type="text"
                       id="entity_canonical"
                       name="entity_canonical"
                       value="<?php echo esc_attr($canonical); ?>"
                       class="regular-text">
                <p class="description">Le nom officiel et unique de cette entite</p>
            </td>
        </tr>

        <tr>
            <th><label for="entity_url">URL officielle</label></th>
            <td>
                <input type="url"
                       id="entity_url"
                       name="entity_url"
                       value="<?php echo esc_url($url); ?>"
                       class="regular-text"
                       placeholder="https://example.com">
                <p class="description">L'URL principale de cette entite (site web, page Wikipedia, etc.)</p>
            </td>
        </tr>

        <tr>
            <th><label for="entity_synonyms">Synonymes / Variantes</label></th>
            <td>
                <textarea id="entity_synonyms"
                          name="entity_synonyms"
                          rows="3"
                          class="large-text"><?php echo esc_textarea($synonyms); ?></textarea>
                <p class="description">Variantes du nom, separees par des virgules</p>
            </td>
        </tr>
    </table>

    <?php
}

function geo_entity_schema_meta_box($post) {

    $types = wp_get_post_terms($post->ID, 'entity_type');
    $current_type = $types && !is_wp_error($types) ? $types[0]->name : '';

    $image = get_post_meta($post->ID, '_entity_image', true);
    $same_as = get_post_meta($post->ID, '_entity_same_as', true);

    $job_title = get_post_meta($post->ID, '_entity_job_title', true);
    $email = get_post_meta($post->ID, '_entity_email', true);
    $telephone = get_post_meta($post->ID, '_entity_telephone', true);

    $address_street = get_post_meta($post->ID, '_entity_address_street', true);
    $address_city = get_post_meta($post->ID, '_entity_address_city', true);
    $address_postal = get_post_meta($post->ID, '_entity_address_postal', true);
    $address_country = get_post_meta($post->ID, '_entity_address_country', true);

    ?>

    <div class="geo-schema-properties">

        <h4>Proprietes communes</h4>
        <table class="form-table">
            <tr>
                <th><label for="entity_image">Image / Logo</label></th>
                <td>
                    <input type="url"
                           id="entity_image"
                           name="entity_image"
                           value="<?php echo esc_url($image); ?>"
                           class="regular-text"
                           placeholder="https://example.com/image.jpg">
                    <p class="description">URL de l'image ou logo (ou utilisez l'image a la une)</p>
                </td>
            </tr>

            <tr>
                <th><label for="entity_same_as">Liens sameAs</label></th>
                <td>
                    <textarea id="entity_same_as"
                              name="entity_same_as"
                              rows="4"
                              class="large-text"
                              placeholder="https://facebook.com/...&#10;https://twitter.com/...&#10;https://linkedin.com/..."><?php echo esc_textarea($same_as); ?></textarea>
                    <p class="description">Liens vers les profils sociaux ou pages externes (un par ligne)</p>
                </td>
            </tr>
        </table>

        <?php if ($current_type === 'Person'): ?>
        <h4>Proprietes Person</h4>
        <table class="form-table">
            <tr>
                <th><label for="entity_job_title">Fonction / Titre</label></th>
                <td>
                    <input type="text"
                           id="entity_job_title"
                           name="entity_job_title"
                           value="<?php echo esc_attr($job_title); ?>"
                           class="regular-text"
                           placeholder="CEO, Developpeur, etc.">
                </td>
            </tr>

            <tr>
                <th><label for="entity_email">Email</label></th>
                <td>
                    <input type="email"
                           id="entity_email"
                           name="entity_email"
                           value="<?php echo esc_attr($email); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="entity_telephone">Telephone</label></th>
                <td>
                    <input type="tel"
                           id="entity_telephone"
                           name="entity_telephone"
                           value="<?php echo esc_attr($telephone); ?>"
                           class="regular-text">
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <?php if (in_array($current_type, ['Organization', 'LocalBusiness'])): ?>
        <h4>Adresse postale</h4>
        <table class="form-table">
            <tr>
                <th><label for="entity_address_street">Rue</label></th>
                <td>
                    <input type="text"
                           id="entity_address_street"
                           name="entity_address_street"
                           value="<?php echo esc_attr($address_street); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="entity_address_city">Ville</label></th>
                <td>
                    <input type="text"
                           id="entity_address_city"
                           name="entity_address_city"
                           value="<?php echo esc_attr($address_city); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="entity_address_postal">Code postal</label></th>
                <td>
                    <input type="text"
                           id="entity_address_postal"
                           name="entity_address_postal"
                           value="<?php echo esc_attr($address_postal); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="entity_address_country">Pays</label></th>
                <td>
                    <input type="text"
                           id="entity_address_country"
                           name="entity_address_country"
                           value="<?php echo esc_attr($address_country); ?>"
                           class="regular-text"
                           placeholder="FR">
                    <p class="description">Code pays ISO (FR, US, GB, etc.)</p>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <?php if (in_array($current_type, ['Organization', 'LocalBusiness'])): ?>
        <h4>Contact</h4>
        <table class="form-table">
            <tr>
                <th><label for="entity_email">Email</label></th>
                <td>
                    <input type="email"
                           id="entity_email"
                           name="entity_email"
                           value="<?php echo esc_attr($email); ?>"
                           class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="entity_telephone">Telephone</label></th>
                <td>
                    <input type="tel"
                           id="entity_telephone"
                           name="entity_telephone"
                           value="<?php echo esc_attr($telephone); ?>"
                           class="regular-text">
                </td>
            </tr>
        </table>
        <?php endif; ?>

    </div>

    <?php
}

function geo_entity_relations_meta_box($post) {

    $works_for = get_post_meta($post->ID, '_entity_works_for', true);
    $member_of = get_post_meta($post->ID, '_entity_member_of', true);

    $organizations = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'tax_query'      => [
            [
                'taxonomy' => 'entity_type',
                'field'    => 'name',
                'terms'    => ['Organization', 'LocalBusiness'],
            ],
        ],
    ]);

    $site_name = get_bloginfo('name');

    ?>

    <p>
        <label for="entity_works_for"><strong>Travaille pour (worksFor)</strong></label><br>
        <select id="entity_works_for" name="entity_works_for" style="width: 100%;">
            <option value="">-- Aucune --</option>
            <option value="main_organization" <?php selected($works_for, 'main_organization'); ?>>
                <?php echo esc_html($site_name); ?> (Organization principale)
            </option>
            <?php foreach ($organizations as $org): ?>
                <option value="<?php echo $org->ID; ?>" <?php selected($works_for, $org->ID); ?>>
                    <?php echo esc_html($org->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="description">Pour les Person : l'organisation employeur</span>
    </p>

    <p>
        <label for="entity_member_of"><strong>Membre de (memberOf)</strong></label><br>
        <select id="entity_member_of" name="entity_member_of" style="width: 100%;">
            <option value="">-- Aucune --</option>
            <option value="main_organization" <?php selected($member_of, 'main_organization'); ?>>
                <?php echo esc_html($site_name); ?> (Organization principale)
            </option>
            <?php foreach ($organizations as $org): ?>
                <option value="<?php echo $org->ID; ?>" <?php selected($member_of, $org->ID); ?>>
                    <?php echo esc_html($org->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="description">Pour les Person : organisation dont la personne est membre</span>
    </p>

    <?php
}

add_action('save_post_entity', function ($post_id) {

    if (!isset($_POST['geo_entity_nonce']) || !wp_verify_nonce($_POST['geo_entity_nonce'], 'geo_entity_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'entity_canonical'        => 'sanitize_text_field',
        'entity_url'              => 'esc_url_raw',
        'entity_synonyms'         => 'sanitize_textarea_field',
        'entity_image'            => 'esc_url_raw',
        'entity_same_as'          => 'sanitize_textarea_field',
        'entity_job_title'        => 'sanitize_text_field',
        'entity_email'            => 'sanitize_email',
        'entity_telephone'        => 'sanitize_text_field',
        'entity_address_street'   => 'sanitize_text_field',
        'entity_address_city'     => 'sanitize_text_field',
        'entity_address_postal'   => 'sanitize_text_field',
        'entity_address_country'  => 'sanitize_text_field',
        'entity_works_for'        => 'sanitize_text_field',
        'entity_member_of'        => 'sanitize_text_field',
    ];

    foreach ($fields as $field => $sanitize_function) {
        if (isset($_POST[$field])) {
            $value = call_user_func($sanitize_function, $_POST[$field]);
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

});

function geo_ai_indexing_meta_box($post) {
    wp_nonce_field('geo_ai_indexing_meta', 'geo_ai_indexing_nonce');

    $exclude_ai = get_post_meta($post->ID, GEO_AI_Indexing::META_EXCLUDE_AI, true);
    $exclude_llm = get_post_meta($post->ID, GEO_AI_Indexing::META_EXCLUDE_LLM, true);
    $declaration = get_post_meta($post->ID, GEO_AI_Indexing::META_CONTENT_DECLARATION, true);

    $declaration_labels = GEO_AI_Indexing::get_declaration_labels();
    $default_declaration = get_option('geo_default_content_declaration', 'original');

    ?>
    <div class="geo-ai-indexing-metabox">
        <p>
            <label>
                <input type="checkbox" 
                       name="geo_exclude_ai" 
                       value="1"
                       <?php checked($exclude_ai, '1'); ?>>
                <strong>Exclure de l'indexation IA</strong>
            </label>
            <br>
            <span class="description">Ajoute <code>data-noai</code> et la meta <code>noai</code></span>
        </p>

        <p>
            <label>
                <input type="checkbox" 
                       name="geo_exclude_llm" 
                       value="1"
                       <?php checked($exclude_llm, '1'); ?>>
                <strong>Exclure des LLM</strong>
            </label>
            <br>
            <span class="description">Ajoute <code>data-nollm</code> spécifiquement</span>
        </p>

        <hr>

        <p>
            <label for="geo_content_declaration"><strong>Déclaration de contenu</strong></label>
            <select name="geo_content_declaration" id="geo_content_declaration" style="width: 100%; margin-top: 5px;">
                <option value="default" <?php selected($declaration, ''); selected($declaration, 'default'); ?>>
                    Par défaut (<?php echo esc_html($declaration_labels[$default_declaration] ?? 'Original'); ?>)
                </option>
                <?php foreach ($declaration_labels as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($declaration, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p class="description" style="margin-top: 10px;">
            La déclaration indique aux IA l'origine de ce contenu 
            via <code>&lt;meta name="ai-content-declaration"&gt;</code>.
        </p>
    </div>
    <?php
}

add_action('save_post', function($post_id, $post) {
    if (!isset($_POST['geo_ai_indexing_nonce']) || 
        !wp_verify_nonce($_POST['geo_ai_indexing_nonce'], 'geo_ai_indexing_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $allowed_post_types = apply_filters('geo_ai_indexing_post_types', ['post', 'page']);
    if (!in_array($post->post_type, $allowed_post_types)) {
        return;
    }

    if (isset($_POST['geo_exclude_ai'])) {
        update_post_meta($post_id, GEO_AI_Indexing::META_EXCLUDE_AI, '1');
    } else {
        delete_post_meta($post_id, GEO_AI_Indexing::META_EXCLUDE_AI);
    }

    if (isset($_POST['geo_exclude_llm'])) {
        update_post_meta($post_id, GEO_AI_Indexing::META_EXCLUDE_LLM, '1');
    } else {
        delete_post_meta($post_id, GEO_AI_Indexing::META_EXCLUDE_LLM);
    }

    $declaration = sanitize_text_field($_POST['geo_content_declaration'] ?? 'default');

    if ($declaration === 'default') {
        delete_post_meta($post_id, GEO_AI_Indexing::META_CONTENT_DECLARATION);
    } else {
        $valid_declarations = array_keys(GEO_AI_Indexing::get_declaration_labels());
        if (in_array($declaration, $valid_declarations)) {
            update_post_meta($post_id, GEO_AI_Indexing::META_CONTENT_DECLARATION, $declaration);
        }
    }

}, 10, 2);
