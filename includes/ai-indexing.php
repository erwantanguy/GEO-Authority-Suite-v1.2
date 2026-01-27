<?php
/**
 * GEO Authority Suite - AI Indexing Directives
 * Gestion des directives data-noai, data-nollm et meta tags ai-content-declaration
 */

if (!defined('ABSPATH')) {
    exit;
}

class GEO_AI_Indexing {

    const META_EXCLUDE_AI = '_geo_exclude_ai';
    const META_EXCLUDE_LLM = '_geo_exclude_llm';
    const META_CONTENT_DECLARATION = '_geo_content_declaration';

    const DECLARATION_ORIGINAL = 'original';
    const DECLARATION_AI_ASSISTED = 'ai-assisted';
    const DECLARATION_AI_GENERATED = 'ai-generated';

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_head', [$this, 'output_meta_tags'], 2);
        add_filter('the_content', [$this, 'maybe_wrap_content_with_directive'], 999);
        add_filter('post_class', [$this, 'add_directive_classes'], 10, 3);
    }

    public function output_meta_tags() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $declaration = $this->get_content_declaration($post_id);
        if ($declaration) {
            echo '<meta name="ai-content-declaration" content="' . esc_attr($declaration) . '" />' . "\n";
        }

        $exclude_ai = $this->is_excluded_from_ai($post_id);
        $exclude_llm = $this->is_excluded_from_llm($post_id);

        if ($exclude_ai || $exclude_llm) {
            $directives = [];
            if ($exclude_ai) {
                $directives[] = 'noai';
            }
            if ($exclude_llm) {
                $directives[] = 'nollm';
            }
            echo '<meta name="robots" content="' . esc_attr(implode(', ', $directives)) . '" />' . "\n";
        }
    }

    public function maybe_wrap_content_with_directive($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return $content;
        }

        $exclude_ai = $this->is_excluded_from_ai($post_id);
        $exclude_llm = $this->is_excluded_from_llm($post_id);

        if (!$exclude_ai && !$exclude_llm) {
            return $content;
        }

        $attrs = [];
        if ($exclude_ai) {
            $attrs[] = 'data-noai="true"';
        }
        if ($exclude_llm) {
            $attrs[] = 'data-nollm="true"';
        }

        $attr_string = implode(' ', $attrs);
        return '<div ' . $attr_string . '>' . $content . '</div>';
    }

    public function add_directive_classes($classes, $class, $post_id) {
        if ($this->is_excluded_from_ai($post_id)) {
            $classes[] = 'geo-noai';
        }
        if ($this->is_excluded_from_llm($post_id)) {
            $classes[] = 'geo-nollm';
        }
        return $classes;
    }

    public function is_excluded_from_ai($post_id) {
        $post_meta = get_post_meta($post_id, self::META_EXCLUDE_AI, true);
        if ($post_meta === '1') {
            return true;
        }

        $post_type = get_post_type($post_id);
        $excluded_types = get_option('geo_ai_excluded_post_types', []);
        return in_array($post_type, (array) $excluded_types);
    }

    public function is_excluded_from_llm($post_id) {
        $post_meta = get_post_meta($post_id, self::META_EXCLUDE_LLM, true);
        if ($post_meta === '1') {
            return true;
        }

        $post_type = get_post_type($post_id);
        $excluded_types = get_option('geo_llm_excluded_post_types', []);
        return in_array($post_type, (array) $excluded_types);
    }

    public function get_content_declaration($post_id) {
        $declaration = get_post_meta($post_id, self::META_CONTENT_DECLARATION, true);
        
        if (empty($declaration) || $declaration === 'default') {
            $declaration = get_option('geo_default_content_declaration', self::DECLARATION_ORIGINAL);
        }

        $valid = [self::DECLARATION_ORIGINAL, self::DECLARATION_AI_ASSISTED, self::DECLARATION_AI_GENERATED];
        return in_array($declaration, $valid) ? $declaration : self::DECLARATION_ORIGINAL;
    }

    public static function get_declaration_labels() {
        return [
            self::DECLARATION_ORIGINAL => __('Contenu original (rédigé par un humain)', 'geo-authority-suite'),
            self::DECLARATION_AI_ASSISTED => __('Assisté par IA (humain + IA)', 'geo-authority-suite'),
            self::DECLARATION_AI_GENERATED => __('Généré par IA (principalement IA)', 'geo-authority-suite'),
        ];
    }

    public function get_excluded_posts($type = 'ai', $limit = 50) {
        $meta_key = $type === 'llm' ? self::META_EXCLUDE_LLM : self::META_EXCLUDE_AI;
        
        return get_posts([
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => $meta_key,
                    'value' => '1',
                ],
            ],
        ]);
    }

    public function get_posts_by_declaration($declaration, $limit = 50) {
        return get_posts([
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => self::META_CONTENT_DECLARATION,
                    'value' => $declaration,
                ],
            ],
        ]);
    }

    public function get_stats() {
        global $wpdb;

        $total_published = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')"
        );

        $excluded_ai = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s AND pm.meta_value = '1' AND p.post_status = 'publish'",
            self::META_EXCLUDE_AI
        ));

        $excluded_llm = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s AND pm.meta_value = '1' AND p.post_status = 'publish'",
            self::META_EXCLUDE_LLM
        ));

        $ai_assisted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_status = 'publish'",
            self::META_CONTENT_DECLARATION,
            self::DECLARATION_AI_ASSISTED
        ));

        $ai_generated = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_status = 'publish'",
            self::META_CONTENT_DECLARATION,
            self::DECLARATION_AI_GENERATED
        ));

        $explicit_original = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_status = 'publish'",
            self::META_CONTENT_DECLARATION,
            self::DECLARATION_ORIGINAL
        ));

        $declared_total = (int) $ai_assisted + (int) $ai_generated + (int) $explicit_original;
        $undeclared = (int) $total_published - $declared_total;

        return [
            'total' => (int) $total_published,
            'excluded_ai' => (int) $excluded_ai,
            'excluded_llm' => (int) $excluded_llm,
            'ai_assisted' => (int) $ai_assisted,
            'ai_generated' => (int) $ai_generated,
            'original' => (int) $explicit_original + $undeclared,
        ];
    }
}

GEO_AI_Indexing::get_instance();
