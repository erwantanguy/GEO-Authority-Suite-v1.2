<?php
/**
 * GEO Authority Suite - LLMS.txt Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

function geo_generate_llms_content(): string {

    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description');
    $site_url = home_url('/');

    $content = "# $site_name\n\n";

    if (!empty($site_description)) {
        $content .= "> $site_description\n\n";
    }

    $content .= "## A propos\n\n";
    $content .= "Site web : $site_url\n";

    $org_email = get_option('geo_contact_email');
    $org_phone = get_option('geo_contact_phone');

    if ($org_email) {
        $content .= "Contact : $org_email\n";
    }
    if ($org_phone) {
        $content .= "Telephone : $org_phone\n";
    }

    $content .= "\n";

    $socials = [];
    if ($fb = get_option('geo_social_facebook')) $socials['Facebook'] = $fb;
    if ($tw = get_option('geo_social_twitter')) $socials['Twitter'] = $tw;
    if ($li = get_option('geo_social_linkedin')) $socials['LinkedIn'] = $li;
    if ($ig = get_option('geo_social_instagram')) $socials['Instagram'] = $ig;
    if ($yt = get_option('geo_social_youtube')) $socials['YouTube'] = $yt;

    if (!empty($socials)) {
        $content .= "## Reseaux sociaux\n\n";
        foreach ($socials as $platform => $url) {
            $content .= "- $platform : $url\n";
        }
        $content .= "\n";
    }

    $content .= geo_generate_llms_ai_section();

    $recent_posts = get_posts([
        'post_type'      => 'post',
        'posts_per_page' => get_option('geo_llms_posts_count', 20),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $ai_indexing = class_exists('GEO_AI_Indexing') ? GEO_AI_Indexing::get_instance() : null;

    if (!empty($recent_posts)) {
        $content .= "## Articles recents\n\n";

        foreach ($recent_posts as $post) {
            if ($ai_indexing && $ai_indexing->is_excluded_from_ai($post->ID)) {
                continue;
            }

            $title = get_the_title($post);
            $url = get_permalink($post);
            $excerpt = wp_strip_all_tags(get_the_excerpt($post));
            $date = get_the_date('Y-m-d', $post);

            $content .= "### $title\n";
            $content .= "URL : $url\n";
            $content .= "Date : $date\n";

            $score = get_post_meta($post->ID, '_gco_score', true);
            if ($score !== '') {
                $content .= "Score GEO : $score/100\n";
            }

            if (!empty($excerpt)) {
                $content .= "Resume : $excerpt\n";
            }

            $content .= "\n";
        }
    }

    $main_pages = get_pages([
        'sort_column' => 'menu_order',
        'number'      => 10,
    ]);

    if (!empty($main_pages)) {
        $content .= "## Pages principales\n\n";

        foreach ($main_pages as $page) {
            if ($ai_indexing && $ai_indexing->is_excluded_from_ai($page->ID)) {
                continue;
            }

            $title = get_the_title($page);
            $url = get_permalink($page);

            $content .= "- $title : $url\n";
        }

        $content .= "\n";
    }

    $entities = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
    ]);

    if (!empty($entities)) {
        $content .= "## Entites referencees\n\n";

        $entities_by_type = [];

        foreach ($entities as $entity_post) {
            $terms = get_the_terms($entity_post->ID, 'entity_type');
            $type = !empty($terms) ? $terms[0]->name : 'Autre';

            if (!isset($entities_by_type[$type])) {
                $entities_by_type[$type] = [];
            }

            $entities_by_type[$type][] = [
                'name' => get_the_title($entity_post),
                'url'  => get_post_meta($entity_post->ID, '_entity_url', true),
            ];
        }

        foreach ($entities_by_type as $type => $entities_list) {
            $content .= "### $type\n\n";

            foreach ($entities_list as $entity) {
                $line = "- {$entity['name']}";
                if (!empty($entity['url'])) {
                    $line .= " : {$entity['url']}";
                }
                $content .= "$line\n";
            }

            $content .= "\n";
        }
    }

    $content .= "## Informations techniques\n\n";
    $content .= "- Format : llms.txt v1.1\n";
    $content .= "- Genere le : " . date('Y-m-d H:i:s') . "\n";
    $content .= "- CMS : WordPress " . get_bloginfo('version') . "\n";
    $content .= "- Plugin : GEO Authority Suite v" . GEO_AUTHORITY_VERSION . "\n";

    /**
     * Permet aux extensions de modifier le contenu du fichier llms.txt
     *
     * @param string $content Contenu généré
     */
    $content = apply_filters('geo_llms_content', $content);

    return $content;
}

function geo_generate_llms_ai_section(): string {
    $content = '';

    if (!class_exists('GEO_AI_Sitemap')) {
        return $content;
    }

    $ai_sitemap = GEO_AI_Sitemap::get_instance();
    
    if ($ai_sitemap->sitemap_exists()) {
        $content .= "## Indexation IA\n\n";
        $content .= "Sitemap IA : " . $ai_sitemap->get_sitemap_url() . "\n";
        $content .= "Nombre de contenus optimises : " . $ai_sitemap->get_entry_count() . "\n";
        
        $avg_score = geo_get_average_geo_score();
        if ($avg_score > 0) {
            $content .= "Score GEO moyen : " . $avg_score . "/100\n";
        }
        
        $content .= "\n";
    }

    if (class_exists('GEO_AI_Indexing')) {
        $ai_indexing = GEO_AI_Indexing::get_instance();
        $stats = $ai_indexing->get_stats();
        
        if ($stats['excluded_ai'] > 0) {
            $content .= "## Contenus exclus de l'indexation IA\n\n";
            $content .= "Nombre de contenus exclus : " . $stats['excluded_ai'] . "\n";
            $content .= "Ces contenus sont marques avec data-noai et ne doivent pas etre utilises pour l'entrainement.\n\n";
        }

        $content .= "## Declaration de contenu\n\n";
        $content .= "- Contenus originaux : " . $stats['original'] . "\n";
        $content .= "- Contenus assistes par IA : " . $stats['ai_assisted'] . "\n";
        $content .= "- Contenus generes par IA : " . $stats['ai_generated'] . "\n\n";
    }

    return $content;
}

function geo_get_average_geo_score(): int {
    global $wpdb;
    
    $result = $wpdb->get_var(
        "SELECT AVG(CAST(meta_value AS DECIMAL(5,2))) 
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
         WHERE pm.meta_key = '_gco_score' 
         AND pm.meta_value REGEXP '^[0-9]+\\.?[0-9]*$'
         AND p.post_status = 'publish'"
    );
    
    return $result ? (int) round($result) : 0;
}

function geo_write_llms_file(): bool {

    $content = geo_generate_llms_content();
    $file_path = ABSPATH . 'llms.txt';
    $result = file_put_contents($file_path, $content);

    return $result !== false;
}

add_action('save_post', function ($post_id, $post) {

    if (!get_option('geo_llms_auto_generate', false)) {
        return;
    }

    if (!in_array($post->post_type, ['post', 'page', 'entity'])) {
        return;
    }

    if ($post->post_status !== 'publish') {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    geo_write_llms_file();

}, 10, 2);

add_action('wp_head', function () {

    if (!get_option('geo_llms_add_link', true)) {
        return;
    }

    $llms_url = home_url('/llms.txt');

    echo '<link rel="llms" href="' . esc_url($llms_url) . '" />' . "\n";

}, 1);

add_action('wp_ajax_geo_generate_llms', function () {

    check_ajax_referer('geo_llms_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission refusee']);
    }

    $success = geo_write_llms_file();

    if ($success) {
        wp_send_json_success([
            'message' => 'Fichier llms.txt genere avec succes !',
            'url'     => home_url('/llms.txt'),
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Erreur lors de la generation du fichier.',
        ]);
    }
});

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=entity',
        'Fichier llms.txt',
        'llms.txt',
        'manage_options',
        'geo-llms-generator',
        'geo_render_llms_page'
    );
}, 40);

function geo_render_llms_page() {

    if (isset($_POST['geo_llms_save'])) {
        check_admin_referer('geo_llms_options');

        update_option('geo_llms_auto_generate', isset($_POST['geo_llms_auto_generate']));
        update_option('geo_llms_add_link', isset($_POST['geo_llms_add_link']));
        update_option('geo_llms_posts_count', intval($_POST['geo_llms_posts_count']));

        echo '<div class="notice notice-success"><p>Options enregistrees avec succes !</p></div>';
    }

    $file_path = ABSPATH . 'llms.txt';
    $file_exists = file_exists($file_path);
    $file_url = home_url('/llms.txt');

    $auto_generate = get_option('geo_llms_auto_generate', false);
    $add_link = get_option('geo_llms_add_link', true);
    $posts_count = get_option('geo_llms_posts_count', 20);

    ?>
    <div class="wrap">
        <h1>Generateur llms.txt</h1>

        <p class="description">
            Le fichier <code>llms.txt</code> aide les moteurs IA a mieux comprendre et indexer votre site.
            <a href="https://llmstxt.org/" target="_blank">En savoir plus sur llms.txt</a>
        </p>

        <div class="card" style="margin: 20px 0;">
            <h2>Statut</h2>

            <?php if ($file_exists): ?>
                <div class="notice notice-success inline">
                    <p>Le fichier <code>llms.txt</code> existe et est accessible.</p>
                </div>

                <p>
                    <strong>URL :</strong>
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank">
                        <?php echo esc_html($file_url); ?>
                    </a>
                </p>

                <p>
                    <strong>Derniere modification :</strong>
                    <?php echo date('d/m/Y H:i:s', filemtime($file_path)); ?>
                </p>

                <p>
                    <strong>Taille :</strong>
                    <?php echo size_format(filesize($file_path)); ?>
                </p>

            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p>Le fichier <code>llms.txt</code> n'existe pas encore.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin: 20px 0;">
            <h2>Generer le fichier</h2>

            <p>
                Cliquez sur le bouton ci-dessous pour generer ou mettre a jour le fichier <code>llms.txt</code>.
            </p>

            <p>
                <button type="button" id="geo-generate-llms" class="button button-primary button-hero">
                    Generer llms.txt
                </button>
            </p>

            <div id="geo-llms-result" style="margin-top: 15px;"></div>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('geo_llms_options'); ?>

            <div class="card" style="margin: 20px 0;">
                <h2>Options</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="geo_llms_auto_generate">Generation automatique</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="geo_llms_auto_generate"
                                       id="geo_llms_auto_generate"
                                       value="1"
                                       <?php checked($auto_generate); ?> />
                                Regenerer automatiquement le fichier lors de la publication d'un article/page
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="geo_llms_add_link">Lien dans le &lt;head&gt;</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="geo_llms_add_link"
                                       id="geo_llms_add_link"
                                       value="1"
                                       <?php checked($add_link); ?> />
                                Ajouter <code>&lt;link rel="llms" href="/llms.txt" /&gt;</code> dans le &lt;head&gt;
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="geo_llms_posts_count">Nombre d'articles</label>
                        </th>
                        <td>
                            <input type="number"
                                   name="geo_llms_posts_count"
                                   id="geo_llms_posts_count"
                                   value="<?php echo esc_attr($posts_count); ?>"
                                   min="5"
                                   max="100"
                                   class="small-text" />
                            <p class="description">
                                Nombre d'articles recents a inclure dans le fichier (5 a 100).
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="geo_llms_save" class="button button-primary">
                        Enregistrer les options
                    </button>
                </p>
            </div>
        </form>

        <?php if ($file_exists): ?>
            <div class="card" style="margin: 20px 0;">
                <h2>Apercu du fichier</h2>

                <textarea readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 12px; padding: 10px;"><?php
                    echo esc_textarea(file_get_contents($file_path));
                ?></textarea>
            </div>
        <?php endif; ?>

        <div class="card" style="margin: 20px 0; background: #e7f3ff; border-left: 4px solid #2196f3;">
            <h2>Qu'est-ce que llms.txt ?</h2>

            <p>
                Le fichier <code>llms.txt</code> est un standard emergent qui permet aux grands modeles de langage (LLMs)
                comme ChatGPT, Claude, ou Perplexity de mieux comprendre la structure et le contenu de votre site.
            </p>

            <h3>Contenu genere automatiquement :</h3>
            <ul>
                <li>Informations de base (nom, description, contact)</li>
                <li>Reseaux sociaux</li>
                <li>Articles recents avec resumes</li>
                <li>Pages principales</li>
                <li>Entites referencees</li>
            </ul>

            <h3>Avantages pour le GEO :</h3>
            <ul>
                <li>Meilleure indexation par les IA</li>
                <li>Citations plus precises de votre contenu</li>
                <li>Decouverte facilitee de vos articles</li>
                <li>Contexte enrichi pour les moteurs IA</li>
            </ul>

            <p>
                <a href="https://llmstxt.org/" target="_blank" class="button">
                    Documentation officielle llms.txt
                </a>
            </p>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#geo-generate-llms').on('click', function() {
            var $button = $(this);
            var $result = $('#geo-llms-result');

            $button.prop('disabled', true).text('Generation en cours...');
            $result.html('');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'geo_generate_llms',
                    nonce: '<?php echo wp_create_nonce('geo_llms_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p><p><a href="' + response.data.url + '" target="_blank">Voir le fichier</a></p></div>');

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $result.html('<div class="notice notice-error inline"><p>Erreur de communication avec le serveur.</p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generer llms.txt');
                }
            });
        });
    });
    </script>
    <?php
}
