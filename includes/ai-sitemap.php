<?php
/**
 * GEO Authority Suite - AI Sitemap Generator
 * Génère un sitemap XML spécifique pour les contenus optimisés IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class GEO_AI_Sitemap {

    const OPTION_ENABLED = 'geo_ai_sitemap_enabled';
    const OPTION_MIN_SCORE = 'geo_ai_sitemap_min_score';
    const OPTION_INCLUDE_ENTITIES = 'geo_ai_sitemap_include_entities';
    const OPTION_MAX_ENTRIES = 'geo_ai_sitemap_max_entries';

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'maybe_serve_sitemap']);
        add_filter('query_vars', [$this, 'add_query_vars']);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^ai-sitemap\.xml$', 'index.php?geo_ai_sitemap=1', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'geo_ai_sitemap';
        return $vars;
    }

    public function maybe_serve_sitemap() {
        if (!get_query_var('geo_ai_sitemap')) {
            return;
        }

        if (!get_option(self::OPTION_ENABLED, true)) {
            status_header(404);
            exit;
        }

        $this->output_sitemap();
        exit;
    }

    public function output_sitemap() {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        // Namespace AI propriétaire - extension GEO Authority Suite pour métadonnées IA
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:ai="http://www.geo-authority.org/schemas/ai-sitemap">' . "\n";

        $entries = $this->get_sitemap_entries();

        foreach ($entries as $entry) {
            echo "  <url>\n";
            echo "    <loc>" . esc_url($entry['url']) . "</loc>\n";
            echo "    <lastmod>" . esc_html($entry['lastmod']) . "</lastmod>\n";
            echo "    <changefreq>" . esc_html($entry['changefreq']) . "</changefreq>\n";
            echo "    <priority>" . esc_html($entry['priority']) . "</priority>\n";
            
            if (!empty($entry['ai_score'])) {
                echo "    <ai:score>" . esc_html($entry['ai_score']) . "</ai:score>\n";
            }
            if (!empty($entry['ai_declaration'])) {
                echo "    <ai:declaration>" . esc_html($entry['ai_declaration']) . "</ai:declaration>\n";
            }
            if (!empty($entry['ai_summary'])) {
                // Échappe la séquence de fin CDATA (]]>) pour éviter une fermeture prématurée
                $summary = str_replace(']]>', ']]]]><![CDATA[>', $entry['ai_summary']);
                echo "    <ai:summary><![CDATA[" . $summary . "]]></ai:summary>\n";
            }
            if (!empty($entry['ai_entities'])) {
                echo "    <ai:entities>\n";
                foreach ($entry['ai_entities'] as $entity) {
                    echo "      <ai:entity type=\"" . esc_attr($entity['type']) . "\">" . esc_html($entity['name']) . "</ai:entity>\n";
                }
                echo "    </ai:entities>\n";
            }
            
            echo "  </url>\n";
        }

        echo '</urlset>';
    }

    public function get_sitemap_entries() {
        $entries = [];
        $min_score = (int) get_option(self::OPTION_MIN_SCORE, 0);
        $max_entries = (int) get_option(self::OPTION_MAX_ENTRIES, 500);
        $include_entities = get_option(self::OPTION_INCLUDE_ENTITIES, true);

        $ai_indexing = GEO_AI_Indexing::get_instance();

        $post_types = ['post', 'page'];
        if ($include_entities) {
            $post_types[] = 'entity';
        }

        $args = [
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $max_entries,
            'orderby' => 'modified',
            'order' => 'DESC',
        ];

        if ($min_score > 0) {
            $args['meta_query'] = [
                [
                    'key' => '_gco_score',
                    'value' => $min_score,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ],
            ];
        }

        $posts = get_posts($args);

        foreach ($posts as $post) {
            if ($ai_indexing->is_excluded_from_ai($post->ID)) {
                continue;
            }

            $score = get_post_meta($post->ID, '_gco_score', true);

            $entry = [
                'url' => get_permalink($post),
                'lastmod' => get_the_modified_date('c', $post),
                'changefreq' => $this->get_changefreq($post),
                'priority' => $this->calculate_priority($post, $score),
            ];

            if ($score !== '') {
                $entry['ai_score'] = (int) $score;
            }

            $declaration = $ai_indexing->get_content_declaration($post->ID);
            if ($declaration) {
                $entry['ai_declaration'] = $declaration;
            }

            $excerpt = get_the_excerpt($post);
            if (!empty($excerpt)) {
                $entry['ai_summary'] = wp_trim_words($excerpt, 30, '...');
            }

            $entities = $this->get_post_entities($post->ID);
            if (!empty($entities)) {
                $entry['ai_entities'] = $entities;
            }

            $entries[] = $entry;
        }

        usort($entries, function($a, $b) {
            $score_a = $a['ai_score'] ?? 0;
            $score_b = $b['ai_score'] ?? 0;
            return $score_b - $score_a;
        });

        return array_slice($entries, 0, $max_entries);
    }

    private function get_changefreq($post) {
        $age_days = (time() - strtotime($post->post_modified)) / DAY_IN_SECONDS;
        
        if ($age_days < 1) return 'hourly';
        if ($age_days < 7) return 'daily';
        if ($age_days < 30) return 'weekly';
        if ($age_days < 365) return 'monthly';
        return 'yearly';
    }

    private function calculate_priority($post, $score) {
        $base = 0.5;

        if ($post->post_type === 'page') {
            $base = 0.6;
        }

        if ($score !== '' && (int) $score >= 80) {
            $base += 0.3;
        } elseif ($score !== '' && (int) $score >= 60) {
            $base += 0.2;
        } elseif ($score !== '' && (int) $score >= 40) {
            $base += 0.1;
        }

        return min(1.0, round($base, 1));
    }

    private function get_post_entities($post_id) {
        $entities = [];
        $content = get_post_field('post_content', $post_id);

        if (preg_match_all('/\[entity\s+id=(\d+)\]/', $content, $matches)) {
            foreach ($matches[1] as $entity_id) {
                $entity_post = get_post($entity_id);
                if ($entity_post && $entity_post->post_type === 'entity') {
                    $terms = get_the_terms($entity_id, 'entity_type');
                    $type = !empty($terms) ? $terms[0]->slug : 'Thing';
                    
                    $entities[] = [
                        'name' => get_the_title($entity_post),
                        'type' => $type,
                    ];
                }
            }
        }

        return $entities;
    }

    public function get_sitemap_url() {
        return home_url('/ai-sitemap.xml');
    }

    public function sitemap_exists() {
        return get_option(self::OPTION_ENABLED, true);
    }

    public function get_entry_count() {
        return count($this->get_sitemap_entries());
    }
}

GEO_AI_Sitemap::get_instance();
