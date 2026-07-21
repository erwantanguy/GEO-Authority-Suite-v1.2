<?php
/**
 * GEO Authority Suite - JSON-LD Output
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {
    geo_register_all_entities();
}, 5);

add_action('wp_head', function () {
    geo_output_jsonld();
}, 999);

function geo_register_all_entities() {
    $entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    foreach ($entities as $entity) {
        $entity_data = geo_build_entity_schema($entity);
        if ($entity_data) {
            geo_register_entity_locked($entity_data);
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
        case 'ProfessionalService':
        case 'Restaurant':
        case 'Store':
            $schema = geo_add_organization_entity_properties($schema, $post_id);
            break;

        case 'Service':
            $schema = geo_add_service_entity_properties($schema, $post_id);
            break;
    }

    return $schema;
}

function geo_add_person_entity_properties($schema, $post_id) {
    $job_title = get_post_meta($post_id, '_entity_job_title', true);
    if (!empty($job_title)) {
        $titles = array_filter(array_map('trim', explode(',', $job_title)));
        if (count($titles) > 1) {
            $schema['jobTitle'] = $titles;
        } else {
            $schema['jobTitle'] = $job_title;
        }
    }

    $email = get_post_meta($post_id, '_entity_email', true);
    if (!empty($email)) {
        $schema['email'] = $email;
    }

    $telephone = get_post_meta($post_id, '_entity_telephone', true);
    if (!empty($telephone)) {
        $schema['telephone'] = $telephone;
    }

    $home_location = get_post_meta($post_id, '_entity_home_location', true);
    if (!empty($home_location)) {
        $address_postal = get_post_meta($post_id, '_entity_address_postal', true);
        $address_country = get_post_meta($post_id, '_entity_address_country', true);

        $schema['homeLocation'] = [
            '@type'   => 'Place',
            'address' => [
                '@type'           => 'PostalAddress',
                'addressLocality' => $home_location,
            ],
        ];

        if (!empty($address_postal)) {
            $schema['homeLocation']['address']['postalCode'] = $address_postal;
        }

        if (!empty($address_country)) {
            $schema['homeLocation']['address']['addressCountry'] = $address_country;
        } else {
            $schema['homeLocation']['address']['addressCountry'] = 'FR';
        }
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

    $contact_type = get_post_meta($post_id, '_entity_contact_point_type', true);
    if (!empty($contact_type)) {
        $cp_phone = get_post_meta($post_id, '_entity_contact_point_phone', true);
        $cp_email = get_post_meta($post_id, '_entity_contact_point_email', true);
        $cp_lang = get_post_meta($post_id, '_entity_contact_point_lang', true);

        $contact_point = [
            '@type'       => 'ContactPoint',
            'contactType' => $contact_type,
        ];

        if (!empty($cp_phone)) {
            $contact_point['telephone'] = $cp_phone;
        } elseif (!empty($telephone)) {
            $contact_point['telephone'] = $telephone;
        }

        if (!empty($cp_email)) {
            $contact_point['email'] = $cp_email;
        } elseif (!empty($email)) {
            $contact_point['email'] = $email;
        }

        if (!empty($cp_lang)) {
            $contact_point['availableLanguage'] = $cp_lang;
        }

        $schema['contactPoint'] = $contact_point;
    }

    $area_scope = get_post_meta($post_id, '_entity_area_served_scope', true);
    $area_name = get_post_meta($post_id, '_entity_area_served_name', true);

    if (!empty($area_scope) && !empty($area_name)) {
        $area_type = 'Place';
        switch ($area_scope) {
            case 'City':
                $area_type = 'City';
                break;
            case 'AdministrativeArea':
                $area_type = 'AdministrativeArea';
                break;
            case 'Country':
                $area_type = 'Country';
                break;
            case 'GeoShape':
                $area_type = 'GeoShape';
                break;
        }

        $schema['areaServed'] = [
            '@type' => $area_type,
            'name'  => $area_name,
        ];
    }

    // Génération automatique de hasOfferCatalog à partir des Services liés
    $linked_services = geo_get_services_for_organization($post_id);
    if (!empty($linked_services)) {
        $org_name = isset($schema['name']) ? $schema['name'] : get_the_title($post_id);
        $schema['hasOfferCatalog'] = [
            '@type' => 'OfferCatalog',
            'name'  => sprintf(__('Services de %s', 'geo-authority-suite'), $org_name),
            'itemListElement' => $linked_services,
        ];
    }

    // Génération automatique de founder à partir des Person avec rôle fondateur
    $founders = geo_get_founders_for_organization($post_id);
    if (!empty($founders)) {
        $schema['founder'] = count($founders) === 1 ? $founders[0] : $founders;
    }

    // Génération automatique de employee à partir des Person avec rôle employé
    $employees = geo_get_employees_for_organization($post_id);
    if (!empty($employees)) {
        $schema['employee'] = count($employees) === 1 ? $employees[0] : $employees;
    }

    return $schema;
}

function geo_add_service_entity_properties($schema, $post_id) {
    $provider = get_post_meta($post_id, '_entity_provider', true);

    if (!empty($provider)) {
        if ($provider === 'main_organization') {
            $schema['provider'] = [
                '@id' => geo_entity_id('organization'),
            ];
        } else {
            $org_post = get_post($provider);
            if ($org_post && $org_post->post_type === 'entity') {
                $org_name = get_the_title($org_post);
                $schema['provider'] = [
                    '@id' => geo_entity_id('organization', sanitize_title($org_name)),
                ];
            }
        }
    }

    $area_scope = get_post_meta($post_id, '_entity_area_served_scope', true);
    $area_name = get_post_meta($post_id, '_entity_area_served_name', true);

    if (!empty($area_scope) && !empty($area_name)) {
        $area_type = 'Place';
        switch ($area_scope) {
            case 'City':
                $area_type = 'City';
                break;
            case 'AdministrativeArea':
                $area_type = 'AdministrativeArea';
                break;
            case 'Country':
                $area_type = 'Country';
                break;
            case 'GeoShape':
                $area_type = 'GeoShape';
                break;
        }

        $schema['areaServed'] = [
            '@type' => $area_type,
            'name'  => $area_name,
        ];
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
    $atts = shortcode_atts([
        'id'      => 0,
        'show'    => 'name',
        'link'    => 'yes',
        'image'   => 'no',
        'display' => 'inline',
    ], $atts);

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
    $job_title = get_post_meta($entity_id, '_entity_job_title', true);
    $description = get_post_meta($entity_id, '_entity_description', true);
    $image_url = get_post_meta($entity_id, '_entity_image', true);
    $entity_type = get_post_meta($entity_id, '_entity_type', true);

    $show_link = ($atts['link'] !== 'no' && !empty($url));
    $show_image = ($atts['image'] === 'yes' && !empty($image_url));

    $display_text = $name;
    if ($atts['show'] === 'name+title' && !empty($job_title)) {
        $display_text = $name . ' (' . $job_title . ')';
    } elseif ($atts['show'] === 'full') {
        $parts = [$name];
        if (!empty($job_title)) {
            $parts[] = $job_title;
        }
        if (!empty($description)) {
            $short_desc = wp_trim_words($description, 10, '...');
            $parts[] = $short_desc;
        }
        $display_text = implode(' – ', $parts);
    }

    if ($atts['display'] === 'card') {
        $output = '<div class="entity-card" data-entity-id="' . $entity_id . '">';
        if ($show_image) {
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '" class="entity-card-image">';
        }
        $output .= '<div class="entity-card-content">';
        $output .= '<strong class="entity-card-name">' . esc_html($name) . '</strong>';
        if (!empty($job_title)) {
            $output .= '<span class="entity-card-title">' . esc_html($job_title) . '</span>';
        }
        if (!empty($description)) {
            $output .= '<p class="entity-card-description">' . esc_html(wp_trim_words($description, 20, '...')) . '</p>';
        }
        if ($show_link) {
            $output .= '<a href="' . esc_url($url) . '" class="entity-card-link" target="_blank">Voir plus</a>';
        }
        $output .= '</div></div>';
        return $output;
    }

    if ($atts['display'] === 'tooltip') {
        $tooltip_text = '';
        if (!empty($job_title)) {
            $tooltip_text = $job_title;
        }
        if (!empty($description)) {
            $tooltip_text .= ($tooltip_text ? ' – ' : '') . wp_trim_words($description, 15, '...');
        }
        if ($show_link) {
            return sprintf(
                '<a href="%s" class="entity-link entity-tooltip" data-entity-id="%d" title="%s">%s</a>',
                esc_url($url),
                $entity_id,
                esc_attr($tooltip_text),
                esc_html($display_text)
            );
        }
        return sprintf(
            '<span class="entity-mention entity-tooltip" data-entity-id="%d" title="%s">%s</span>',
            $entity_id,
            esc_attr($tooltip_text),
            esc_html($display_text)
        );
    }

    $output = '';
    if ($show_image) {
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '" class="entity-inline-image"> ';
    }

    if ($show_link) {
        $output .= sprintf(
            '<a href="%s" class="entity-link" data-entity-id="%d">%s</a>',
            esc_url($url),
            $entity_id,
            esc_html($display_text)
        );
    } else {
        $output .= sprintf(
            '<span class="entity-mention" data-entity-id="%d">%s</span>',
            $entity_id,
            esc_html($display_text)
        );
    }

    return $output;
});

/**
 * Récupère tous les Services liés à une Organization
 * Recherche les Services dont le provider pointe vers cette Organization
 */
function geo_get_services_for_organization($org_post_id) {
    $services = [];
    
    $service_entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'entity_type',
                'field'    => 'name',
                'terms'    => 'Service',
            ],
        ],
    ]);
    
    foreach ($service_entities as $service) {
        $provider = get_post_meta($service->ID, '_entity_provider', true);
        
        $is_linked = false;
        
        if ($provider === 'main_organization') {
            $main_org = geo_get_main_organization_id();
            if ($main_org == $org_post_id) {
                $is_linked = true;
            }
        } elseif ($provider == $org_post_id) {
            $is_linked = true;
        }
        
        if ($is_linked) {
            $service_name = get_the_title($service);
            $service_id = geo_entity_id('Service', sanitize_title($service_name));
            $services[] = ['@id' => $service_id];
        }
    }
    
    return $services;
}

/**
 * Récupère l'ID de l'Organization principale (première Organization créée)
 */
function geo_get_main_organization_id() {
    $orgs = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'ASC',
        'tax_query'      => [
            [
                'taxonomy' => 'entity_type',
                'field'    => 'name',
                'terms'    => ['Organization', 'LocalBusiness', 'ProfessionalService', 'Restaurant', 'Store'],
            ],
        ],
    ]);
    
    return !empty($orgs) ? $orgs[0]->ID : null;
}

/**
 * Récupère tous les fondateurs d'une Organization
 * Recherche les Person dont le rôle est "founder" ou "founder_former" et qui travaillent pour cette Organization
 */
function geo_get_founders_for_organization($org_post_id) {
    $founders = [];
    
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
    ]);
    
    foreach ($person_entities as $person) {
        $org_role = get_post_meta($person->ID, '_entity_org_role', true);
        
        if (!in_array($org_role, ['founder', 'founder_former'])) {
            continue;
        }
        
        $works_for = get_post_meta($person->ID, '_entity_works_for', true);
        $member_of = get_post_meta($person->ID, '_entity_member_of', true);
        
        $is_linked = false;
        $main_org = geo_get_main_organization_id();
        
        if ($works_for === 'main_organization' && $main_org == $org_post_id) {
            $is_linked = true;
        } elseif ($works_for == $org_post_id) {
            $is_linked = true;
        } elseif ($member_of === 'main_organization' && $main_org == $org_post_id) {
            $is_linked = true;
        } elseif ($member_of == $org_post_id) {
            $is_linked = true;
        }
        
        if ($is_linked) {
            $person_name = get_the_title($person);
            $person_id = geo_entity_id('Person', sanitize_title($person_name));
            $founders[] = ['@id' => $person_id];
        }
    }
    
    return $founders;
}

/**
 * Récupère tous les employés d'une Organization
 * Recherche les Person dont le rôle est "employee" et qui travaillent pour cette Organization
 */
function geo_get_employees_for_organization($org_post_id) {
    $employees = [];
    
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
    ]);
    
    foreach ($person_entities as $person) {
        $org_role = get_post_meta($person->ID, '_entity_org_role', true);
        
        if ($org_role !== 'employee') {
            continue;
        }
        
        $works_for = get_post_meta($person->ID, '_entity_works_for', true);
        
        $is_linked = false;
        $main_org = geo_get_main_organization_id();
        
        if ($works_for === 'main_organization' && $main_org == $org_post_id) {
            $is_linked = true;
        } elseif ($works_for == $org_post_id) {
            $is_linked = true;
        }
        
        if ($is_linked) {
            $person_name = get_the_title($person);
            $person_id = geo_entity_id('Person', sanitize_title($person_name));
            $employees[] = ['@id' => $person_id];
        }
    }
    
    return $employees;
}
