<?php
/**
 * GEO Authority Suite - Admin AI Indexing Page
 * Interface d'administration pour les paramètres d'indexation IA
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=entity',
        'Indexation IA',
        'Indexation IA',
        'manage_options',
        'geo-ai-indexing',
        'geo_render_ai_indexing_page'
    );
}, 35);

function geo_render_ai_indexing_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Accès non autorisé', 'geo-authority-suite'));
    }

    if (isset($_POST['geo_ai_indexing_save'])) {
        check_admin_referer('geo_ai_indexing_options');
        geo_save_ai_indexing_options();
        echo '<div class="notice notice-success"><p>Options enregistrées avec succès !</p></div>';
    }

    $ai_indexing = GEO_AI_Indexing::get_instance();
    $ai_sitemap = GEO_AI_Sitemap::get_instance();
    $stats = $ai_indexing->get_stats();

    $post_types = get_post_types(['public' => true], 'objects');
    $excluded_ai_types = get_option('geo_ai_excluded_post_types', []);
    $excluded_llm_types = get_option('geo_llm_excluded_post_types', []);
    $default_declaration = get_option('geo_default_content_declaration', 'original');

    $sitemap_enabled = get_option(GEO_AI_Sitemap::OPTION_ENABLED, true);
    $sitemap_min_score = get_option(GEO_AI_Sitemap::OPTION_MIN_SCORE, 0);
    $sitemap_include_entities = get_option(GEO_AI_Sitemap::OPTION_INCLUDE_ENTITIES, true);
    $sitemap_max_entries = get_option(GEO_AI_Sitemap::OPTION_MAX_ENTRIES, 500);

    $declaration_labels = GEO_AI_Indexing::get_declaration_labels();

    ?>
    <div class="wrap geo-ai-indexing-page">
        <h1>Indexation IA - Paramètres avancés</h1>

        <p class="description">
            Contrôlez comment les moteurs IA (ChatGPT, Claude, Perplexity) indexent et citent votre contenu.
        </p>

        <!-- Statistiques -->
        <div class="geo-stats-grid">
            <div class="geo-stat-card">
                <span class="geo-stat-value"><?php echo esc_html($stats['total']); ?></span>
                <span class="geo-stat-label">Contenus publiés</span>
            </div>
            <div class="geo-stat-card geo-stat-success">
                <span class="geo-stat-value"><?php echo esc_html($stats['original']); ?></span>
                <span class="geo-stat-label">Contenus originaux</span>
            </div>
            <div class="geo-stat-card geo-stat-info">
                <span class="geo-stat-value"><?php echo esc_html($stats['ai_assisted']); ?></span>
                <span class="geo-stat-label">Assistés par IA</span>
            </div>
            <div class="geo-stat-card geo-stat-warning">
                <span class="geo-stat-value"><?php echo esc_html($stats['excluded_ai']); ?></span>
                <span class="geo-stat-label">Exclus de l'indexation IA</span>
            </div>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('geo_ai_indexing_options'); ?>

            <!-- Directives globales -->
            <div class="card geo-section">
                <h2>Directives globales d'exclusion</h2>
                <p class="description">
                    Excluez des types de contenu entiers de l'indexation par les IA.
                    Les directives <code>data-noai</code> et <code>data-nollm</code> seront ajoutées automatiquement.
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">Exclure de l'indexation IA</th>
                        <td>
                            <fieldset>
                                <?php foreach ($post_types as $pt): ?>
                                    <?php if ($pt->name === 'attachment') continue; ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="geo_ai_excluded_post_types[]" 
                                               value="<?php echo esc_attr($pt->name); ?>"
                                               <?php checked(in_array($pt->name, (array) $excluded_ai_types)); ?>>
                                        <?php echo esc_html($pt->labels->name); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                Ces types de contenu auront la directive <code>data-noai="true"</code> 
                                et la meta <code>&lt;meta name="robots" content="noai"&gt;</code>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Exclure des LLM spécifiquement</th>
                        <td>
                            <fieldset>
                                <?php foreach ($post_types as $pt): ?>
                                    <?php if ($pt->name === 'attachment') continue; ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="geo_llm_excluded_post_types[]" 
                                               value="<?php echo esc_attr($pt->name); ?>"
                                               <?php checked(in_array($pt->name, (array) $excluded_llm_types)); ?>>
                                        <?php echo esc_html($pt->labels->name); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                            <p class="description">
                                Directive <code>data-nollm="true"</code> pour exclure spécifiquement des modèles de langage.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Déclaration de contenu -->
            <div class="card geo-section">
                <h2>Déclaration de contenu par défaut</h2>
                <p class="description">
                    Le meta tag <code>ai-content-declaration</code> indique aux IA l'origine de votre contenu.
                    Cette option définit la valeur par défaut pour les nouveaux contenus.
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="geo_default_declaration">Déclaration par défaut</label>
                        </th>
                        <td>
                            <select name="geo_default_content_declaration" id="geo_default_declaration">
                                <?php foreach ($declaration_labels as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($default_declaration, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                Chaque contenu peut avoir sa propre déclaration via la metabox d'édition.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sitemap IA -->
            <div class="card geo-section">
                <h2>Sitemap IA</h2>
                <p class="description">
                    Génère un sitemap XML spécialement conçu pour les crawlers IA, 
                    avec des métadonnées enrichies (score GEO, déclaration, entités).
                </p>

                <?php if ($sitemap_enabled): ?>
                    <div class="notice notice-success inline">
                        <p>
                            <strong>Sitemap actif :</strong>
                            <a href="<?php echo esc_url($ai_sitemap->get_sitemap_url()); ?>" target="_blank">
                                <?php echo esc_html($ai_sitemap->get_sitemap_url()); ?>
                            </a>
                            (<?php echo esc_html($ai_sitemap->get_entry_count()); ?> entrées)
                        </p>
                    </div>
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Activer le sitemap IA</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="geo_ai_sitemap_enabled" 
                                       value="1"
                                       <?php checked($sitemap_enabled); ?>>
                                Générer <code>/ai-sitemap.xml</code>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="geo_ai_sitemap_min_score">Score GEO minimum</label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="geo_ai_sitemap_min_score" 
                                   id="geo_ai_sitemap_min_score"
                                   value="<?php echo esc_attr($sitemap_min_score); ?>"
                                   min="0" max="100" class="small-text">
                            <p class="description">
                                N'inclure que les contenus avec un score GEO ≥ cette valeur. 
                                0 = inclure tous les contenus.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Inclure les entités</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="geo_ai_sitemap_include_entities" 
                                       value="1"
                                       <?php checked($sitemap_include_entities); ?>>
                                Ajouter les entités (Person, Organization...) au sitemap
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="geo_ai_sitemap_max_entries">Nombre maximum d'entrées</label>
                        </th>
                        <td>
                            <input type="number" 
                                   name="geo_ai_sitemap_max_entries" 
                                   id="geo_ai_sitemap_max_entries"
                                   value="<?php echo esc_attr($sitemap_max_entries); ?>"
                                   min="10" max="50000" class="small-text">
                            <p class="description">
                                Limite le nombre d'URLs dans le sitemap (max recommandé : 50000).
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Informations -->
            <div class="card geo-section geo-info-card">
                <h2>Comment ça fonctionne</h2>

                <h3>Directives HTML</h3>
                <ul>
                    <li><code>data-noai="true"</code> : Demande aux IA de ne pas utiliser ce contenu pour l'entraînement</li>
                    <li><code>data-nollm="true"</code> : Exclut spécifiquement des grands modèles de langage</li>
                </ul>

                <h3>Meta tags</h3>
                <ul>
                    <li><code>&lt;meta name="robots" content="noai"&gt;</code> : Directive robots pour les crawlers IA</li>
                    <li><code>&lt;meta name="ai-content-declaration" content="original"&gt;</code> : Déclare l'origine du contenu</li>
                </ul>

                <h3>Sitemap IA (/ai-sitemap.xml)</h3>
                <p>Contient des métadonnées enrichies pour chaque URL :</p>
                <ul>
                    <li><code>ai:score</code> : Score GEO du contenu (si analysé)</li>
                    <li><code>ai:declaration</code> : Type de contenu (original, ai-assisted, ai-generated)</li>
                    <li><code>ai:summary</code> : Résumé du contenu</li>
                    <li><code>ai:entities</code> : Entités mentionnées dans le contenu</li>
                </ul>
            </div>

            <p class="submit">
                <button type="submit" name="geo_ai_indexing_save" class="button button-primary button-hero">
                    Enregistrer les paramètres
                </button>
            </p>
        </form>
    </div>
    <?php
}

function geo_save_ai_indexing_options() {
    $excluded_ai = isset($_POST['geo_ai_excluded_post_types']) ? array_map('sanitize_text_field', $_POST['geo_ai_excluded_post_types']) : [];
    $excluded_llm = isset($_POST['geo_llm_excluded_post_types']) ? array_map('sanitize_text_field', $_POST['geo_llm_excluded_post_types']) : [];
    
    update_option('geo_ai_excluded_post_types', $excluded_ai);
    update_option('geo_llm_excluded_post_types', $excluded_llm);

    $declaration = sanitize_text_field($_POST['geo_default_content_declaration'] ?? 'original');
    $valid_declarations = array_keys(GEO_AI_Indexing::get_declaration_labels());
    if (!in_array($declaration, $valid_declarations)) {
        $declaration = 'original';
    }
    update_option('geo_default_content_declaration', $declaration);

    update_option(GEO_AI_Sitemap::OPTION_ENABLED, isset($_POST['geo_ai_sitemap_enabled']));
    $min_score = isset($_POST['geo_ai_sitemap_min_score']) ? absint($_POST['geo_ai_sitemap_min_score']) : 0;
    update_option(GEO_AI_Sitemap::OPTION_MIN_SCORE, max(0, min(100, $min_score)));
    update_option(GEO_AI_Sitemap::OPTION_INCLUDE_ENTITIES, isset($_POST['geo_ai_sitemap_include_entities']));
    $max_entries = isset($_POST['geo_ai_sitemap_max_entries']) ? absint($_POST['geo_ai_sitemap_max_entries']) : 500;
    update_option(GEO_AI_Sitemap::OPTION_MAX_ENTRIES, max(10, min(50000, $max_entries)));

    flush_rewrite_rules();
}
