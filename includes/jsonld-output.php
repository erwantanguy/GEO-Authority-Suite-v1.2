<?php
/**
 * GEO Authority Suite - JSON-LD Output
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {
    geo_register_all_entities();
}, 20);

add_action('wp_head', function () {
    geo_output_jsonld();
}, 100);

function geo_register_all_entities() {
    $entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    foreach ($entities as $entity) {
        $entity_data = geo_build_entity_schema($entity);
        if ($entity_data) {
            geo_register_entity($entity_data);
        }
    }
}

function geo_build_entity_schema($entity) {
    $post_id = $entity->ID;

    $types = wp_get_post_terms($post_id, 'entity_type');
    if (!$types || is_wp_error($types)) {
        $type = 'Thing';
    } else {
        $type = $types[0]->name;
    }

    if (in_array(strtolower($type), ['worksfor', 'memberof'])) {
        return null;
    }

    $canonical = get_post_meta($post_id, '_entity_canonical', true);
    $name = !empty($canonical) ? $canonical : get_the_title($entity);
    $description = wp_strip_all_tags($entity->post_content);
    $url = get_post_meta($post_id, '_entity_url', true);

    $id = geo_entity_id($type, sanitize_title($name));

    $schema = [
        '@type' => $type,
        '@id'   => $id,
        'name'  => $name,
    ];

    if (!empty($description)) {
        $schema['description'] = $description;
    }

    if (!empty($url)) {
        $schema['url'] = $url;
    }

    $image = get_post_meta($post_id, '_entity_image', true);
    if (empty($image) && has_post_thumbnail($post_id)) {
        $image = get_the_post_thumbnail_url($post_id, 'full');
    }
    if (!empty($image)) {
        $schema['image'] = $image;
        if ($type === 'Organization' || $type === 'LocalBusiness') {
            $schema['logo'] = $image;
        }
    }

    $synonyms = get_post_meta($post_id, '_entity_synonyms', true);
    if (!empty($synonyms)) {
        $synonyms_array = array_filter(array_map('trim', explode(',', $synonyms)));
        if (!empty($synonyms_array)) {
            $schema['alternateName'] = count($synonyms_array) === 1 ? $synonyms_array[0] : $synonyms_array;
        }
    }

    $same_as = get_post_meta($post_id, '_entity_same_as', true);
    if (!empty($same_as)) {
        $same_as_array = array_filter(array_map('trim', explode("\n", $same_as)));
        if (!empty($same_as_array)) {
            $schema['sameAs'] = $same_as_array;
        }
    }

    switch ($type) {
        case 'Person':
            $schema = geo_add_person_entity_properties($schema, $post_id);
            break;

        case 'Organization':
        case 'LocalBusiness':
            $schema = geo_add_organization_entity_properties($schema, $post_id);
            break;
    }

    return $schema;
}

function geo_add_person_entity_properties($schema, $post_id) {
    $job_title = get_post_meta($post_id, '_entity_job_title', true);
    if (!empty($job_title)) {
        $schema['jobTitle'] = $job_title;
    }

    $email = get_post_meta($post_id, '_entity_email', true);
    if (!empty($email)) {
        $schema['email'] = $email;
    }

    $telephone = get_post_meta($post_id, '_entity_telephone', true);
    if (!empty($telephone)) {
        $schema['telephone'] = $telephone;
    }

    $works_for = get_post_meta($post_id, '_entity_works_for', true);
    if (!empty($works_for)) {
        if ($works_for === 'main_organization') {
            $schema['worksFor'] = [
                '@id' => geo_entity_id('organization'),
            ];
        } else {
            $org_post = get_post($works_for);
            if ($org_post && $org_post->post_type === 'entity') {
                $org_name = get_the_title($org_post);
                $schema['worksFor'] = [
                    '@id' => geo_entity_id('organization', sanitize_title($org_name)),
                ];
            }
        }
    }

    $member_of = get_post_meta($post_id, '_entity_member_of', true);
    if (!empty($member_of)) {
        if ($member_of === 'main_organization') {
            $schema['memberOf'] = [
                '@id' => geo_entity_id('organization'),
            ];
        } else {
            $org_post = get_post($member_of);
            if ($org_post && $org_post->post_type === 'entity') {
                $org_name = get_the_title($org_post);
                $schema['memberOf'] = [
                    '@id' => geo_entity_id('organization', sanitize_title($org_name)),
                ];
            }
        }
    }

    return $schema;
}

function geo_add_organization_entity_properties($schema, $post_id) {
    $email = get_post_meta($post_id, '_entity_email', true);
    if (!empty($email)) {
        $schema['email'] = $email;
    }

    $telephone = get_post_meta($post_id, '_entity_telephone', true);
    if (!empty($telephone)) {
        $schema['telephone'] = $telephone;
    }

    $street = get_post_meta($post_id, '_entity_address_street', true);
    $city = get_post_meta($post_id, '_entity_address_city', true);
    $postal = get_post_meta($post_id, '_entity_address_postal', true);
    $country = get_post_meta($post_id, '_entity_address_country', true);

    if (!empty($street) || !empty($city)) {
        $address = ['@type' => 'PostalAddress'];
        if (!empty($street)) $address['streetAddress'] = $street;
        if (!empty($city)) $address['addressLocality'] = $city;
        if (!empty($postal)) $address['postalCode'] = $postal;
        if (!empty($country)) $address['addressCountry'] = $country;
        $schema['address'] = $address;
    }

    return $schema;
}

function geo_output_jsonld() {
    $entities = geo_get_entities();

    if (empty($entities)) {
        return;
    }

    $graph = [];
    foreach ($entities as $entity) {
        if (isset($entity['@context'])) {
            unset($entity['@context']);
        }
        if (!isset($entity['@type']) || in_array($entity['@type'], ['worksFor', 'memberOf'])) {
            continue;
        }
        $graph[] = $entity;
    }

    if (empty($graph)) {
        return;
    }

    echo "\n" . '<script type="application/ld+json">' . "\n";
    echo wp_json_encode(
        [
            '@context' => 'https://schema.org',
            '@graph'   => array_values($graph),
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    echo "\n" . '</script>' . "\n";
}

add_shortcode('entity', function ($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $entity_id = intval($atts['id']);

    if (!$entity_id) {
        return '';
    }

    $entity = get_post($entity_id);
    if (!$entity || $entity->post_type !== 'entity') {
        return '';
    }

    $canonical = get_post_meta($entity_id, '_entity_canonical', true);
    $name = !empty($canonical) ? $canonical : get_the_title($entity);
    $url = get_post_meta($entity_id, '_entity_url', true);

    if (!empty($url)) {
        return sprintf(
            '<a href="%s" class="entity-link" data-entity-id="%d">%s</a>',
            esc_url($url),
            $entity_id,
            esc_html($name)
        );
    }

    return sprintf(
        '<span class="entity-mention" data-entity-id="%d">%s</span>',
        $entity_id,
        esc_html($name)
    );
});
