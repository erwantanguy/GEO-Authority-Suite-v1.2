<?php
/**
 * GEO Authority Suite - Schema Organization (fallback)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {

    $eas_organizations = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'entity_type',
                'field'    => 'name',
                'terms'    => 'Organization',
            ],
        ],
    ]);

    if (!empty($eas_organizations)) {
        return;
    }

    $organization = [
        '@type' => 'Organization',
        '@id'   => geo_entity_id('organization'),
        'name'  => get_bloginfo('name'),
        'url'   => home_url('/'),
    ];

    $description = get_bloginfo('description');
    if (!empty($description)) {
        $organization['description'] = $description;
    }

    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        if ($logo_url) {
            $organization['logo'] = $logo_url;
        }
    }

    $social_links = [];
    $facebook = get_option('geo_social_facebook');
    $twitter = get_option('geo_social_twitter');
    $linkedin = get_option('geo_social_linkedin');
    $instagram = get_option('geo_social_instagram');
    $youtube = get_option('geo_social_youtube');

    if ($facebook) $social_links[] = $facebook;
    if ($twitter) $social_links[] = $twitter;
    if ($linkedin) $social_links[] = $linkedin;
    if ($instagram) $social_links[] = $instagram;
    if ($youtube) $social_links[] = $youtube;

    if (!empty($social_links)) {
        $organization['sameAs'] = $social_links;
    }

    $contact_email = get_option('geo_contact_email', get_option('admin_email'));
    if ($contact_email) {
        $organization['email'] = $contact_email;
    }

    $phone = get_option('geo_contact_phone');
    if ($phone) {
        $organization['telephone'] = $phone;
    }

    $address_street = get_option('geo_address_street');
    $address_city = get_option('geo_address_city');
    $address_postal = get_option('geo_address_postal');
    $address_country = get_option('geo_address_country');

    if ($address_street || $address_city) {
        $organization['address'] = ['@type' => 'PostalAddress'];
        if ($address_street) $organization['address']['streetAddress'] = $address_street;
        if ($address_city) $organization['address']['addressLocality'] = $address_city;
        if ($address_postal) $organization['address']['postalCode'] = $address_postal;
        if ($address_country) $organization['address']['addressCountry'] = $address_country;
    }

    geo_register_entity($organization);

}, 19);
