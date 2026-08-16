<?php
/**
 * Plugin Name: GEO Authority Suite
 * Description: Suite complète pour le GEO (Generative Engine Optimization) - Gestion des entités Schema.org, JSON-LD, llms.txt, indexation IA et audits de contenu.
 * Version: 1.6.1
 * Author: Erwan Tanguy - Ticoët
 * Author URI: https://www.ticoet.fr/
 * License: GPL2+
 * Text Domain: geo-authority-suite
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GEO_AUTHORITY_VERSION', '1.6.1');
define('GEO_AUTHORITY_PATH', plugin_dir_path(__FILE__));
define('GEO_AUTHORITY_URL', plugin_dir_url(__FILE__));

require_once GEO_AUTHORITY_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$geoAuthorityUpdateChecker = PucFactory::buildUpdateChecker(
    'https://dl.ticoet.me/downloads/pluginsWP/geo-authority-suite/details.json',
    __FILE__,
    'geo-authority-suite'
);

require_once GEO_AUTHORITY_PATH . 'includes/entity-id.php';
require_once GEO_AUTHORITY_PATH . 'includes/entity-registry.php';
require_once GEO_AUTHORITY_PATH . 'includes/cpt-entity.php';
require_once GEO_AUTHORITY_PATH . 'includes/duplicate-detection.php';
require_once GEO_AUTHORITY_PATH . 'includes/schema-organization.php';
require_once GEO_AUTHORITY_PATH . 'includes/schema-person.php';
require_once GEO_AUTHORITY_PATH . 'includes/jsonld-output.php';
require_once GEO_AUTHORITY_PATH . 'includes/entity-audit.php';
require_once GEO_AUTHORITY_PATH . 'includes/content-audit.php';
require_once GEO_AUTHORITY_PATH . 'includes/admin-audit-page.php';
require_once GEO_AUTHORITY_PATH . 'includes/ai-indexing.php';
require_once GEO_AUTHORITY_PATH . 'includes/ai-sitemap.php';
require_once GEO_AUTHORITY_PATH . 'includes/admin-ai-indexing-page.php';
require_once GEO_AUTHORITY_PATH . 'includes/meta-boxes.php';
require_once GEO_AUTHORITY_PATH . 'includes/llms-generator.php';

register_activation_hook(__FILE__, function () {
    do_action('init');
    
    if (class_exists('GEO_AI_Sitemap')) {
        GEO_AI_Sitemap::get_instance()->add_rewrite_rules();
    }
    
    flush_rewrite_rules();
    set_transient('geo_authority_activation_notice', true, 5);
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('admin_notices', function () {
    if (get_transient('geo_authority_activation_notice')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>GEO Authority Suite activ&eacute; !</strong></p>
            <p>Vous pouvez maintenant g&eacute;rer vos entit&eacute;s dans le menu <strong>Entit&eacute;s</strong> et acc&eacute;der aux audits GEO.</p>
        </div>
        <?php
        delete_transient('geo_authority_activation_notice');
    }
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'geo-authority-admin',
        GEO_AUTHORITY_URL . 'assets/admin.css',
        [],
        GEO_AUTHORITY_VERSION
    );
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'geo-authority-frontend',
        GEO_AUTHORITY_URL . 'assets/frontend.css',
        [],
        GEO_AUTHORITY_VERSION
    );
});

add_filter('wpseo_schema_graph_pieces', 'geo_authority_filter_yoast_schema', 100, 2);
function geo_authority_filter_yoast_schema($pieces, $context) {
    $geo_persons = geo_get_geo_authority_person_names();
    
    if (empty($geo_persons)) {
        return $pieces;
    }
    
    return array_filter($pieces, function($piece) use ($geo_persons) {
        $class_name = get_class($piece);
        
        if (strpos($class_name, 'Person') !== false || strpos($class_name, 'Author') !== false) {
            return false;
        }
        
        return true;
    });
}

add_filter('wpseo_schema_graph', 'geo_authority_filter_yoast_graph', 100, 2);
function geo_authority_filter_yoast_graph($graph, $context) {
    $geo_persons = geo_get_geo_authority_person_names();
    
    if (empty($geo_persons)) {
        return $graph;
    }
    
    return array_filter($graph, function($item) use ($geo_persons) {
        if (!isset($item['@type'])) {
            return true;
        }
        
        $type = $item['@type'];
        if (is_array($type)) {
            $type = $type[0];
        }
        
        if ($type === 'Person' && isset($item['name'])) {
            $name = strtolower(trim($item['name']));
            foreach ($geo_persons as $geo_name) {
                if (strtolower(trim($geo_name)) === $name) {
                    return false;
                }
                $name_parts = explode(' ', $name);
                $geo_parts = explode(' ', strtolower($geo_name));
                if (!empty($name_parts) && !empty($geo_parts)) {
                    if ($name_parts[0] === $geo_parts[0] || end($name_parts) === end($geo_parts)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    });
}

function geo_get_geo_authority_person_names() {
    static $names = null;
    
    if ($names !== null) {
        return $names;
    }
    
    $names = [];
    
    $persons = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'entity_type',
                'field'    => 'slug',
                'terms'    => 'person',
            ],
        ],
    ]);
    
    foreach ($persons as $person) {
        $canonical = get_post_meta($person->ID, '_entity_canonical', true);
        $name = !empty($canonical) ? $canonical : get_the_title($person);
        if (!empty($name)) {
            $names[] = $name;
        }
    }
    
    return $names;
}

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=entity',
        'Aide & Documentation',
        'Aide',
        'manage_options',
        'geo-authority-help',
        'geo_authority_render_help_page'
    );
}, 99);

function geo_authority_render_help_page() {
    ?>
    <div class="wrap">
        <h1>GEO Authority Suite - Aide</h1>
        
        <div class="card">
            <h2>Objectif du plugin</h2>
            <p>Ce plugin centralise toutes les fonctionnalit&eacute;s GEO (Generative Engine Optimization) :</p>
            <ul>
                <li><strong>Gestion des entit&eacute;s</strong> : Personnes, Organisations, Produits, Services...</li>
                <li><strong>G&eacute;n&eacute;ration JSON-LD</strong> : Schema.org optimis&eacute; pour les IA</li>
                <li><strong>Fichier llms.txt</strong> : Index pour les moteurs IA</li>
                <li><strong>Audits</strong> : V&eacute;rification des entit&eacute;s et du contenu</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Comment l'utiliser</h2>
            
            <h3>1. Cr&eacute;er une Organization principale</h3>
            <ol>
                <li>Aller dans <strong>Entit&eacute;s > Ajouter</strong></li>
                <li>Titre : Le nom de votre entreprise/site</li>
                <li>Type : <strong>Organization</strong></li>
                <li>Remplir les champs : URL, description, logo, adresse</li>
                <li>Ajouter les liens sociaux dans "sameAs"</li>
            </ol>
            
            <h3>2. Cr&eacute;er les Person (auteurs, employ&eacute;s)</h3>
            <ol>
                <li>Cr&eacute;er une nouvelle entit&eacute; pour chaque personne</li>
                <li>Type : <strong>Person</strong></li>
                <li>Remplir : Fonction, email, photo</li>
                <li><strong>Important :</strong> Dans "Relations", s&eacute;lectionner l'Organization dans "Travaille pour"</li>
            </ol>
            
            <h3>3. Mentionner les entit&eacute;s dans vos articles</h3>
            <p>Utilisez le shortcode <code>[entity id=X]</code> pour mentionner une entit&eacute; :</p>
            <pre>J'ai rencontr&eacute; [entity id=5] lors de la conf&eacute;rence...</pre>
        </div>
        
        <div class="card">
            <h2>V&eacute;rifier le JSON-LD</h2>
            <ol>
                <li>Afficher le code source de votre page (Ctrl+U)</li>
                <li>Chercher <code>&lt;script type="application/ld+json"&gt;</code></li>
                <li>Tester sur <a href="https://validator.schema.org/" target="_blank">Schema.org Validator</a></li>
            </ol>
        </div>
        
        <div class="card">
            <h2>Bonnes pratiques GEO</h2>
            <ul>
                <li><strong>Une seule Organization principale</strong> pour votre site</li>
                <li><strong>Relier toutes les Person</strong> &agrave; cette Organization via "worksFor"</li>
                <li><strong>Ajouter des photos/logos</strong> pour chaque entit&eacute;</li>
                <li><strong>Remplir les descriptions</strong> avec soin</li>
                <li><strong>Ajouter les liens sociaux</strong> (Facebook, LinkedIn, Twitter)</li>
                <li><strong>Utiliser le shortcode [entity]</strong> pour cr&eacute;er des liens</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Types d'entit&eacute;s disponibles</h2>
            <ul>
                <li><strong>Organization</strong> : Votre entreprise, association</li>
                <li><strong>Person</strong> : Auteurs, employ&eacute;s, experts</li>
                <li><strong>LocalBusiness</strong> : Entreprise avec adresse physique</li>
                <li><strong>Product</strong> : Produits que vous vendez</li>
                <li><strong>Service</strong> : Services que vous proposez</li>
                <li><strong>Place</strong> : Lieux g&eacute;ographiques</li>
                <li><strong>Event</strong> : &Eacute;v&eacute;nements</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Ressources</h2>
            <ul>
                <li><a href="https://schema.org/" target="_blank">Schema.org Documentation</a></li>
                <li><a href="https://validator.schema.org/" target="_blank">Schema.org Validator</a></li>
                <li><a href="https://search.google.com/test/rich-results" target="_blank">Google Rich Results Test</a></li>
            </ul>
        </div>
    </div>
    <?php
}

add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'entity') {
        ?>
        <style>
            .geo-schema-properties {
                background: #fff;
            }
            .geo-schema-properties h4 {
                margin: 20px 0 10px;
                padding: 10px;
                background: #f0f0f0;
                border-left: 3px solid #0073aa;
                font-size: 14px;
                font-weight: 600;
            }
            .geo-schema-properties .form-table th {
                width: 200px;
            }
        </style>
        <?php
    }
});
