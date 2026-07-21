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
    $home_location = get_post_meta($post->ID, '_entity_home_location', true);
    $org_role = get_post_meta($post->ID, '_entity_org_role', true);

    $address_street = get_post_meta($post->ID, '_entity_address_street', true);
    $address_city = get_post_meta($post->ID, '_entity_address_city', true);
    $address_postal = get_post_meta($post->ID, '_entity_address_postal', true);
    $address_country = get_post_meta($post->ID, '_entity_address_country', true);

    $contact_point_type = get_post_meta($post->ID, '_entity_contact_point_type', true);
    $contact_point_phone = get_post_meta($post->ID, '_entity_contact_point_phone', true);
    $contact_point_email = get_post_meta($post->ID, '_entity_contact_point_email', true);
    $contact_point_lang = get_post_meta($post->ID, '_entity_contact_point_lang', true);

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
                    <p class="description">Separé par des virgules si plusieurs : "Ecrivaine publique, Correctrice"</p>
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
                           class="regular-text"
                           placeholder="+33612345678">
                </td>
            </tr>

            <tr>
                <th><label for="entity_home_location">Localisation (SEO local)</label></th>
                <td>
                    <input type="text"
                           id="entity_home_location"
                           name="entity_home_location"
                           value="<?php echo esc_attr($home_location); ?>"
                           class="regular-text"
                           placeholder="Saumur">
                    <p class="description">Ville de residence / base (homeLocation). Renforce l'ancrage local.</p>
                </td>
            </tr>

            <tr>
                <th><label for="entity_org_role">Role dans l'organisation</label></th>
                <td>
                    <select id="entity_org_role" name="entity_org_role" class="regular-text">
                        <option value="" <?php selected($org_role, ''); ?>>-- Non defini --</option>
                        <option value="founder" <?php selected($org_role, 'founder'); ?>>Fondateur (actif)</option>
                        <option value="founder_former" <?php selected($org_role, 'founder_former'); ?>>Fondateur (parti)</option>
                        <option value="employee" <?php selected($org_role, 'employee'); ?>>Employe</option>
                        <option value="member" <?php selected($org_role, 'member'); ?>>Membre</option>
                    </select>
                    <p class="description">Definit la relation avec l'organisation (genere "founder" dans Organization)</p>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <?php if (in_array($current_type, ['Organization', 'LocalBusiness', 'ProfessionalService', 'Restaurant', 'Store'])): ?>
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

        <?php if (in_array($current_type, ['Organization', 'LocalBusiness', 'ProfessionalService', 'Restaurant', 'Store'])): ?>
        <h4>Contact principal</h4>
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
                           class="regular-text"
                           placeholder="+33241525472">
                </td>
            </tr>
        </table>

        <h4>Point de contact structure (contactPoint)</h4>
        <p class="description" style="margin-bottom: 10px;">Optionnel. Permet de mieux structurer le contact pour Google (type de service, langue).</p>
        <table class="form-table">
            <tr>
                <th><label for="entity_contact_point_type">Type de contact</label></th>
                <td>
                    <select id="entity_contact_point_type" name="entity_contact_point_type">
                        <option value="">-- Non utilise --</option>
                        <option value="customer service" <?php selected($contact_point_type, 'customer service'); ?>>Service client</option>
                        <option value="technical support" <?php selected($contact_point_type, 'technical support'); ?>>Support technique</option>
                        <option value="billing support" <?php selected($contact_point_type, 'billing support'); ?>>Facturation</option>
                        <option value="sales" <?php selected($contact_point_type, 'sales'); ?>>Commercial</option>
                        <option value="reservations" <?php selected($contact_point_type, 'reservations'); ?>>Reservations</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="entity_contact_point_phone">Telephone contactPoint</label></th>
                <td>
                    <input type="tel"
                           id="entity_contact_point_phone"
                           name="entity_contact_point_phone"
                           value="<?php echo esc_attr($contact_point_phone); ?>"
                           class="regular-text"
                           placeholder="+33241525472">
                    <p class="description">Laissez vide pour utiliser le telephone principal</p>
                </td>
            </tr>

            <tr>
                <th><label for="entity_contact_point_email">Email contactPoint</label></th>
                <td>
                    <input type="email"
                           id="entity_contact_point_email"
                           name="entity_contact_point_email"
                           value="<?php echo esc_attr($contact_point_email); ?>"
                           class="regular-text">
                    <p class="description">Laissez vide pour utiliser l'email principal</p>
                </td>
            </tr>

            <tr>
                <th><label for="entity_contact_point_lang">Langue disponible</label></th>
                <td>
                    <input type="text"
                           id="entity_contact_point_lang"
                           name="entity_contact_point_lang"
                           value="<?php echo esc_attr($contact_point_lang ?: 'French'); ?>"
                           class="regular-text"
                           placeholder="French">
                    <p class="description">Langue du service (French, English, etc.)</p>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <?php 
        $area_served_types = ['Organization', 'LocalBusiness', 'ProfessionalService', 'Restaurant', 'Store', 'Service'];
        if (in_array($current_type, $area_served_types)): 
            $area_served_scope = get_post_meta($post->ID, '_entity_area_served_scope', true);
            $area_served_name = get_post_meta($post->ID, '_entity_area_served_name', true);
        ?>
        <h4>Zone desservie (areaServed)</h4>
        <p class="description" style="margin-bottom: 10px;">Optionnel. Definit la zone geographique couverte par vos services.</p>
        <table class="form-table">
            <tr>
                <th><label for="entity_area_served_scope">Portee</label></th>
                <td>
                    <select id="entity_area_served_scope" name="entity_area_served_scope">
                        <option value="">-- Non definie --</option>
                        <option value="City" <?php selected($area_served_scope, 'City'); ?>>Locale (ville)</option>
                        <option value="AdministrativeArea" <?php selected($area_served_scope, 'AdministrativeArea'); ?>>Regionale (region/departement)</option>
                        <option value="Country" <?php selected($area_served_scope, 'Country'); ?>>Nationale (pays)</option>
                        <option value="GeoShape" <?php selected($area_served_scope, 'GeoShape'); ?>>Internationale / Monde</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="entity_area_served_name">Zone</label></th>
                <td>
                    <input type="text"
                           id="entity_area_served_name"
                           name="entity_area_served_name"
                           value="<?php echo esc_attr($area_served_name); ?>"
                           class="regular-text"
                           placeholder="France, Pays de la Loire, Saumur...">
                    <p class="description">Nom de la zone (ex: France, Bretagne, Paris, etc.)</p>
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
    $provider = get_post_meta($post->ID, '_entity_provider', true);

    $types = wp_get_post_terms($post->ID, 'entity_type');
    $current_type = $types && !is_wp_error($types) ? $types[0]->name : '';

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

    <?php if ($current_type === 'Service'): ?>
    <p>
        <label for="entity_provider"><strong>Fournisseur (provider)</strong></label><br>
        <select id="entity_provider" name="entity_provider" style="width: 100%;">
            <option value="">-- Aucun --</option>
            <option value="main_organization" <?php selected($provider, 'main_organization'); ?>>
                <?php echo esc_html($site_name); ?> (Organization principale)
            </option>
            <?php foreach ($organizations as $org): ?>
                <option value="<?php echo $org->ID; ?>" <?php selected($provider, $org->ID); ?>>
                    <?php echo esc_html($org->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="description">L'organisation qui fournit ce service (ferme le graphe semantique)</span>
    </p>
    <?php endif; ?>

    <?php if ($current_type === 'Person' || $current_type === ''): ?>
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
    <?php endif; ?>

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
        'entity_canonical'          => 'sanitize_text_field',
        'entity_url'                => 'esc_url_raw',
        'entity_synonyms'           => 'sanitize_textarea_field',
        'entity_image'              => 'esc_url_raw',
        'entity_same_as'            => 'sanitize_textarea_field',
        'entity_job_title'          => 'sanitize_text_field',
        'entity_email'              => 'sanitize_email',
        'entity_telephone'          => 'sanitize_text_field',
        'entity_home_location'      => 'sanitize_text_field',
        'entity_org_role'           => 'sanitize_text_field',
        'entity_address_street'     => 'sanitize_text_field',
        'entity_address_city'       => 'sanitize_text_field',
        'entity_address_postal'     => 'sanitize_text_field',
        'entity_address_country'    => 'sanitize_text_field',
        'entity_contact_point_type' => 'sanitize_text_field',
        'entity_contact_point_phone'=> 'sanitize_text_field',
        'entity_contact_point_email'=> 'sanitize_email',
        'entity_contact_point_lang' => 'sanitize_text_field',
        'entity_area_served_scope'  => 'sanitize_text_field',
        'entity_area_served_name'   => 'sanitize_text_field',
        'entity_works_for'          => 'sanitize_text_field',
        'entity_member_of'          => 'sanitize_text_field',
        'entity_provider'           => 'sanitize_text_field',
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
