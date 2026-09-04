<?php
/**
 * GEO Authority Suite - Content Audit (Version améliorée)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=entity',
        'Audit du contenu',
        'Audit Contenu',
        'manage_options',
        'geo-content-audit',
        'geo_render_content_audit_page'
    );
}, 30);

function geo_render_content_audit_page() {

    $posts = get_posts([
        'post_type'      => 'post',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $audit_results = [];
    $stats = [
        'total'           => count($posts),
        'with_faq'        => 0,
        'with_blockquotes'=> 0,
        'with_images'     => 0,
        'with_audio'      => 0,
        'with_video'      => 0,
        'with_entities'   => 0,
        'with_tldr'       => 0,
        'with_howto'      => 0,
        'with_definition' => 0,
        'with_proscons'   => 0,
        'with_author'     => 0,
        'with_stats'      => 0,
        'score_excellent' => 0,
        'score_good'      => 0,
        'score_poor'      => 0,
    ];

    foreach ($posts as $post) {
        $audit = geo_audit_post_content($post);
        $audit_results[] = $audit;

        if ($audit['has_faq']) $stats['with_faq']++;
        if ($audit['has_blockquotes']) $stats['with_blockquotes']++;
        if ($audit['has_images']) $stats['with_images']++;
        if ($audit['has_audio']) $stats['with_audio']++;
        if ($audit['has_video']) $stats['with_video']++;
        if ($audit['has_entities']) $stats['with_entities']++;
        if ($audit['has_tldr']) $stats['with_tldr']++;
        if ($audit['has_howto']) $stats['with_howto']++;
        if ($audit['has_definition']) $stats['with_definition']++;
        if ($audit['has_proscons']) $stats['with_proscons']++;
        if ($audit['has_author']) $stats['with_author']++;
        if ($audit['has_stats']) $stats['with_stats']++;

        if ($audit['geo_score'] >= 80) $stats['score_excellent']++;
        elseif ($audit['geo_score'] >= 50) $stats['score_good']++;
        else $stats['score_poor']++;
    }

    ?>
    <div class="wrap">
        <h1>Audit GEO du contenu</h1>

        <p class="description">
            Audit de vos contenus pour optimiser leur visibilite dans les moteurs IA.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">

            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo $stats['total']; ?></h3>
                <p style="margin: 5px 0 0; color: #666; font-size: 13px;">Articles</p>
            </div>

            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0; font-size: 32px; color: #00a32a;"><?php echo $stats['score_excellent']; ?></h3>
                <p style="margin: 5px 0 0; color: #666; font-size: 13px;">Excellents (>=80)</p>
            </div>

            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0; font-size: 32px; color: #dba617;"><?php echo $stats['score_good']; ?></h3>
                <p style="margin: 5px 0 0; color: #666; font-size: 13px;">Bons (>=50)</p>
            </div>

            <div class="card" style="padding: 20px; text-align: center;">
                <h3 style="margin: 0; font-size: 32px; color: #d63638;"><?php echo $stats['score_poor']; ?></h3>
                <p style="margin: 5px 0 0; color: #666; font-size: 13px;">A ameliorer</p>
            </div>

        </div>

        <div class="card">
            <h2>Elements GEO detectes</h2>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Articles</th>
                        <th>%</th>
                        <th>Impact GEO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #f0f7ff;">
                        <td colspan="4"><strong>Blocs GEO Blocks Suite</strong></td>
                    </tr>
                    <tr>
                        <td><strong>TL;DR</strong></td>
                        <td><?php echo $stats['with_tldr']; ?></td>
                        <td><?php echo round(($stats['with_tldr'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Eleve (+15)</span></td>
                    </tr>
                    <tr>
                        <td><strong>How-To</strong></td>
                        <td><?php echo $stats['with_howto']; ?></td>
                        <td><?php echo round(($stats['with_howto'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Tres eleve (+20)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Definitions</strong></td>
                        <td><?php echo $stats['with_definition']; ?></td>
                        <td><?php echo round(($stats['with_definition'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Eleve (+15 max)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Pros/Cons</strong></td>
                        <td><?php echo $stats['with_proscons']; ?></td>
                        <td><?php echo round(($stats['with_proscons'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Eleve (+15)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Author Box</strong></td>
                        <td><?php echo $stats['with_author']; ?></td>
                        <td><?php echo round(($stats['with_author'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #dba617;">Moyen (+10)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Stats</strong></td>
                        <td><?php echo $stats['with_stats']; ?></td>
                        <td><?php echo round(($stats['with_stats'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #dba617;">Moyen (+10 max)</span></td>
                    </tr>
                    <tr style="background: #f0f7ff;">
                        <td colspan="4"><strong>Contenus classiques</strong></td>
                    </tr>
                    <tr>
                        <td><strong>FAQ</strong></td>
                        <td><?php echo $stats['with_faq']; ?></td>
                        <td><?php echo round(($stats['with_faq'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Tres eleve (+30)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Citations (blockquote)</strong></td>
                        <td><?php echo $stats['with_blockquotes']; ?></td>
                        <td><?php echo round(($stats['with_blockquotes'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Eleve (+15)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Entites</strong></td>
                        <td><?php echo $stats['with_entities']; ?></td>
                        <td><?php echo round(($stats['with_entities'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Tres eleve (+20)</span></td>
                    </tr>
                    <tr style="background: #f0f7ff;">
                        <td colspan="4"><strong>Medias</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Images</strong></td>
                        <td><?php echo $stats['with_images']; ?></td>
                        <td><?php echo round(($stats['with_images'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #dba617;">Moyen (+15 max)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Video</strong></td>
                        <td><?php echo $stats['with_video']; ?></td>
                        <td><?php echo round(($stats['with_video'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #00a32a;">Eleve (+10)</span></td>
                    </tr>
                    <tr>
                        <td><strong>Audio</strong></td>
                        <td><?php echo $stats['with_audio']; ?></td>
                        <td><?php echo round(($stats['with_audio'] / max($stats['total'], 1)) * 100); ?>%</td>
                        <td><span style="color: #dba617;">Faible (+5)</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2>Detail des articles</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30%;">Article</th>
                        <th style="width: 10%;">Score</th>
                        <th style="width: 45%;">Elements GEO</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_results as $audit): ?>
                        <?php
                        $score_color = $audit['geo_score'] >= 80 ? '#00a32a' : ($audit['geo_score'] >= 50 ? '#dba617' : '#d63638');
                        $score_label = $audit['geo_score'] >= 80 ? 'Excellent' : ($audit['geo_score'] >= 50 ? 'Bon' : 'A ameliorer');
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo get_permalink($audit['post_id']); ?>" target="_blank">
                                        <?php echo esc_html($audit['title']); ?>
                                    </a>
                                </strong>
                                <br>
                                <span style="font-size: 11px; color: #666;">
                                    <?php echo date('d/m/Y', strtotime($audit['date'])); ?>
                                </span>
                            </td>

                            <td>
                                <div style="text-align: center;">
                                    <div style="width: 50px; height: 50px; margin: 0 auto; border-radius: 50%; background: <?php echo $score_color; ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 16px;">
                                        <?php echo $audit['geo_score']; ?>
                                    </div>
                                    <small style="color: <?php echo $score_color; ?>; font-weight: 600;">
                                        <?php echo $score_label; ?>
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 5px; font-size: 11px;">
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_tldr'] ? '#cce5ff' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_tldr'] ? '#004085' : '#999'; ?>;">
                                        <?php echo $audit['has_tldr'] ? '✓' : '✗'; ?> TL;DR
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_howto'] ? '#cce5ff' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_howto'] ? '#004085' : '#999'; ?>;">
                                        <?php echo $audit['has_howto'] ? '✓' : '✗'; ?> How-To<?php if ($audit['has_howto']): ?> (<?php echo $audit['howto_steps']; ?>)<?php endif; ?>
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_definition'] ? '#cce5ff' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_definition'] ? '#004085' : '#999'; ?>;">
                                        <?php echo $audit['has_definition'] ? '✓' : '✗'; ?> Def (<?php echo $audit['definition_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_proscons'] ? '#cce5ff' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_proscons'] ? '#004085' : '#999'; ?>;">
                                        <?php echo $audit['has_proscons'] ? '✓' : '✗'; ?> Pro/Con
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_author'] ? '#cce5ff' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_author'] ? '#004085' : '#999'; ?>;">
                                        <?php echo $audit['has_author'] ? '✓' : '✗'; ?> Author
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_stats'] ? '#cce5ff' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_stats'] ? '#004085' : '#999'; ?>;">
                                        <?php echo $audit['has_stats'] ? '✓' : '✗'; ?> Stats (<?php echo $audit['stats_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_faq'] ? '#d4edda' : '#f8d7da'; ?>; border-radius: 3px;">
                                        <?php echo $audit['has_faq'] ? '✓' : '✗'; ?> FAQ (<?php echo $audit['faq_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_blockquotes'] ? '#d4edda' : '#f8d7da'; ?>; border-radius: 3px;">
                                        <?php echo $audit['has_blockquotes'] ? '✓' : '✗'; ?> Cit (<?php echo $audit['blockquote_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_entities'] ? '#d4edda' : '#f8d7da'; ?>; border-radius: 3px;">
                                        <?php echo $audit['has_entities'] ? '✓' : '✗'; ?> Ent (<?php echo $audit['entity_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_images'] ? '#fff3cd' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_images'] ? '#856404' : '#999'; ?>;">
                                        <?php echo $audit['has_images'] ? '✓' : '✗'; ?> Img (<?php echo $audit['image_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_video'] ? '#fff3cd' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_video'] ? '#856404' : '#999'; ?>;">
                                        <?php echo $audit['has_video'] ? '✓' : '✗'; ?> Vid (<?php echo $audit['video_count']; ?>)
                                    </span>
                                    <span style="padding: 3px 6px; background: <?php echo $audit['has_audio'] ? '#fff3cd' : '#f5f5f5'; ?>; border-radius: 3px; color: <?php echo $audit['has_audio'] ? '856404' : '#999'; ?>;">
                                        <?php echo $audit['has_audio'] ? '✓' : '✗'; ?> Aud (<?php echo $audit['audio_count']; ?>)
                                    </span>
                                </div>
                            </td>

                            <td>
                                <a href="<?php echo get_edit_post_link($audit['post_id']); ?>" class="button button-small">
                                    Modifier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top: 20px; background: #e7f3ff; border-left: 4px solid #2196f3;">
            <h2>Comment ameliorer votre score GEO</h2>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3>Impact maximal</h3>
                    <ul>
                        <li><strong>FAQ</strong> : Les IA citent directement vos reponses</li>
                        <li><strong>Entites</strong> : Renforce votre autorite d'expert</li>
                    </ul>
                </div>
                <div>
                    <h3>Impact important</h3>
                    <ul>
                        <li><strong>Citations</strong> : Credibilite et backlinks</li>
                        <li><strong>Videos</strong> : Engagement et multimedia</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php
        /**
         * Sections supplementaires de l'audit de contenu.
         *
         * @param array $posts Contenus audites.
         */
        do_action('geo_content_audit_extra_section', $posts);
        ?>
    </div>
    <?php
}

/**
 * FONCTION AMÉLIORÉE - Détection des médias Gutenberg et MediaGEO
 */
function geo_audit_post_content($post) {

    // Récupérer le contenu brut pour les shortcodes
    $raw_content = $post->post_content;
    
    // Récupérer le contenu RENDU (avec les blocs transformés en HTML)
    $content = apply_filters('the_content', $raw_content);

    $audit = [
        'post_id'         => $post->ID,
        'title'           => get_the_title($post),
        'date'            => $post->post_date,
        'geo_score'       => 0,
        'has_faq'         => false,
        'faq_count'       => 0,
        'has_blockquotes' => false,
        'blockquote_count'=> 0,
        'has_images'      => false,
        'image_count'     => 0,
        'images_with_alt' => 0,
        'has_audio'       => false,
        'audio_count'     => 0,
        'has_video'       => false,
        'video_count'     => 0,
        'has_entities'    => false,
        'entity_count'    => 0,
        'has_tldr'        => false,
        'has_howto'       => false,
        'howto_steps'     => 0,
        'has_definition'  => false,
        'definition_count'=> 0,
        'has_proscons'    => false,
        'has_author'      => false,
        'has_stats'       => false,
        'stats_count'     => 0,
        'recommendations' => [],
    ];

    // === FAQ ===
    preg_match_all('/<details[^>]*>.*?<summary[^>]*>.*?<\/summary>.*?<\/details>/is', $content, $faq_details);
    $faq_details_count = count($faq_details[0]);

    preg_match_all('/<h3[^>]*>.*?<\/h3>\s*<p>.*?<\/p>/is', $content, $faq_h3);
    $faq_h3_count = count($faq_h3[0]);

    $audit['faq_count'] = max($faq_details_count, $faq_h3_count);

    if ($faq_details_count > 0 || $faq_h3_count >= 2) {
        $audit['has_faq'] = true;
        $audit['geo_score'] += 30;
    } else {
        $audit['recommendations'][] = 'Ajouter des FAQ (min 2)';
    }

    // === CITATIONS ===
    preg_match_all('/<blockquote[^>]*>.*?<\/blockquote>/is', $content, $quote_matches);
    $audit['blockquote_count'] = count($quote_matches[0]);
    $audit['has_blockquotes'] = $audit['blockquote_count'] > 0;

    if ($audit['has_blockquotes']) {
        $audit['geo_score'] += 15;
    } else {
        $audit['recommendations'][] = 'Ajouter des citations (blockquote)';
    }

    // === IMAGES (amélioré) ===
    $image_count = 0;
    $images_with_alt = 0;

    // Détection images HTML classiques
    preg_match_all('/<img[^>]+>/i', $content, $html_images);
    $image_count += count($html_images[0]);
    foreach ($html_images[0] as $img) {
        if (preg_match('/alt=["\'][^"\']+["\']/i', $img)) {
            $images_with_alt++;
        }
    }

    // Détection blocs Gutenberg image
    preg_match_all('/<!-- wp:image[^>]*-->.*?<!-- \/wp:image -->/s', $content, $gb_images);
    $image_count += count($gb_images[0]);
    foreach ($gb_images[0] as $block) {
        if (preg_match('/alt=["\'][^"\']+["\']/i', $block)) {
            $images_with_alt++;
        }
    }

    // Détection MediaGEO : figure avec classe "geo-image"
    preg_match_all('/<figure[^>]*class="[^"]*geo-image[^"]*"[^>]*>.*?<\/figure>/is', $content, $mediageo_images);
    $image_count += count($mediageo_images[0]);
    foreach ($mediageo_images[0] as $block) {
        if (preg_match('/alt=["\'][^"\']+["\']/i', $block)) {
            $images_with_alt++;
        }
    }

    $audit['image_count'] = $image_count;
    $audit['images_with_alt'] = $images_with_alt;
    $audit['has_images'] = $image_count > 0;

    if ($audit['has_images']) {
        $audit['geo_score'] += min(15, $images_with_alt * 3);
    } else {
        $audit['recommendations'][] = 'Ajouter des images avec alt text';
    }

    // === AUDIO (amélioré) ===
    $audio_count = 0;

    // Balises HTML audio
    preg_match_all('/<audio[^>]*>/i', $content, $html_audio);
    $audio_count += count($html_audio[0]);

    // Blocs Gutenberg audio natif
    preg_match_all('/<!-- wp:audio[^>]*-->/i', $content, $gb_audio);
    $audio_count += count($gb_audio[0]);

    // MediaGEO : figure avec classe "geo-audio"
    preg_match_all('/<figure[^>]*class="[^"]*geo-audio[^"]*"[^>]*>/i', $content, $mediageo_audio);
    $audio_count += count($mediageo_audio[0]);

    $audit['audio_count'] = $audio_count;
    $audit['has_audio'] = $audio_count > 0;

    if ($audit['has_audio']) {
        $audit['geo_score'] += 5;
    }

    // === VIDEO (amélioré) ===
    $video_count = 0;

    // Balises HTML video
    preg_match_all('/<video[^>]*>/i', $content, $html_video);
    $video_count += count($html_video[0]);

    // Blocs Gutenberg video
    preg_match_all('/<!-- wp:video[^>]*-->/i', $content, $gb_video);
    $video_count += count($gb_video[0]);

    // Blocs embed (YouTube, Vimeo, etc.)
    preg_match_all('/<!-- wp:embed[^>]*-->/i', $content, $gb_embed);
    $video_count += count($gb_embed[0]);

    // MediaGEO : figure avec classe "geo-video"
    preg_match_all('/<figure[^>]*class="[^"]*geo-video[^"]*"[^>]*>/i', $content, $mediageo_video);
    $video_count += count($mediageo_video[0]);

    // iframes (YouTube, Vimeo embarqués)
    preg_match_all('/<iframe[^>]*(youtube|vimeo)[^>]*>/i', $content, $iframe_video);
    $video_count += count($iframe_video[0]);

    $audit['video_count'] = $video_count;
    $audit['has_video'] = $video_count > 0;

    if ($audit['has_video']) {
        $audit['geo_score'] += 10;
    }

    // === TL;DR GEO ===
    preg_match_all('/data-geo-tldr="true"|class="[^"]*geo-tldr[^"]*"/i', $content, $tldr_matches);
    $audit['has_tldr'] = count($tldr_matches[0]) > 0;
    if ($audit['has_tldr']) {
        $audit['geo_score'] += 15;
    }

    // === HOW-TO GEO ===
    preg_match_all('/data-geo-howto="true"|class="[^"]*geo-howto[^"]*"/i', $content, $howto_matches);
    $audit['has_howto'] = count($howto_matches[0]) > 0;
    if ($audit['has_howto']) {
        preg_match_all('/class="[^"]*geo-howto-step[^"]*"/i', $content, $howto_steps);
        $audit['howto_steps'] = count($howto_steps[0]);
        $audit['geo_score'] += 20;
    }

    // === DEFINITION GEO ===
    preg_match_all('/data-geo-definition="true"|class="[^"]*geo-definition[^"]*"/i', $content, $def_matches);
    $definition_count = count($def_matches[0]);
    $audit['definition_count'] = $definition_count;
    $audit['has_definition'] = $definition_count > 0;
    if ($audit['has_definition']) {
        $audit['geo_score'] += min(15, $definition_count * 5);
    }

    // === PROS/CONS GEO ===
    preg_match_all('/data-geo-proscons="true"|class="[^"]*geo-proscons[^"]*"/i', $content, $proscons_matches);
    $audit['has_proscons'] = count($proscons_matches[0]) > 0;
    if ($audit['has_proscons']) {
        $audit['geo_score'] += 15;
    }

    // === AUTHOR BOX GEO ===
    preg_match_all('/data-geo-author="true"|class="[^"]*geo-author[^"]*"/i', $content, $author_matches);
    $audit['has_author'] = count($author_matches[0]) > 0;
    if ($audit['has_author']) {
        $audit['geo_score'] += 10;
    }

    // === STATS GEO ===
    preg_match_all('/data-geo-stats="true"|class="[^"]*geo-stats[^"]*"/i', $content, $stats_matches);
    $stats_count = count($stats_matches[0]);
    $audit['stats_count'] = $stats_count;
    $audit['has_stats'] = $stats_count > 0;
    if ($audit['has_stats']) {
        $audit['geo_score'] += min(10, $stats_count * 3);
    }

    // === ENTITÉS (détection dans le contenu brut) ===
    preg_match_all('/\[entity id=\d+\]/', $raw_content, $entity_matches);
    $audit['entity_count'] = count($entity_matches[0]);
    $audit['has_entities'] = $audit['entity_count'] > 0;

    if ($audit['has_entities']) {
        $audit['geo_score'] += 20;
    } else {
        $audit['recommendations'][] = 'Mentionner des entites avec [entity id=X]';
    }

    // === IMAGE À LA UNE ===
    if (has_post_thumbnail($post)) {
        $audit['geo_score'] += 5;
    }

    // === RECOMMANDATIONS NOUVEAUX BLOCS ===
    if (!$audit['has_tldr']) {
        $audit['recommendations'][] = 'Ajouter un bloc TL;DR pour un resume rapide';
    }
    if (!$audit['has_author'] && !$audit['has_blockquotes']) {
        $audit['recommendations'][] = 'Ajouter Author Box ou citations pour E-E-A-T';
    }

    // Score maximum 100
    $audit['geo_score'] = min(100, $audit['geo_score']);

    return $audit;
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'geo_content_score',
        'Score GEO',
        'geo_render_score_meta_box',
        'post',
        'side',
        'high'
    );
});

function geo_render_score_meta_box($post) {

    if ($post->post_status !== 'publish') {
        echo '<p style="color: #666; font-style: italic;">Publiez pour voir le score</p>';
        return;
    }

    $audit = geo_audit_post_content($post);
    $score_color = $audit['geo_score'] >= 80 ? '#00a32a' : ($audit['geo_score'] >= 50 ? '#dba617' : '#d63638');

    ?>
    <div style="text-align: center; padding: 15px;">
        <div style="width: 70px; height: 70px; margin: 0 auto; border-radius: 50%; background: <?php echo $score_color; ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px;">
            <?php echo $audit['geo_score']; ?>
        </div>
        <p style="margin: 10px 0; font-weight: 600;">
            <?php echo $audit['geo_score'] >= 80 ? 'Excellent' : ($audit['geo_score'] >= 50 ? 'Bon' : 'A ameliorer'); ?>
        </p>
    </div>

    <div style="font-size: 13px;">
        <p style="margin: 8px 0; font-weight: 600; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Contenu structuré</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_tldr'] ? '✓' : '✗'; ?> TL;DR</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_howto'] ? '✓' : '✗'; ?> How-To <?php if ($audit['has_howto']): ?>(<?php echo $audit['howto_steps']; ?> étapes)<?php endif; ?></p>
        <p style="margin: 5px 0;"><?php echo $audit['has_definition'] ? '✓' : '✗'; ?> Définitions (<?php echo $audit['definition_count']; ?>)</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_faq'] ? '✓' : '✗'; ?> FAQ (<?php echo $audit['faq_count']; ?>)</p>
        
        <p style="margin: 8px 0; font-weight: 600; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Évaluation</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_proscons'] ? '✓' : '✗'; ?> Pros/Cons</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_stats'] ? '✓' : '✗'; ?> Stats (<?php echo $audit['stats_count']; ?>)</p>
        
        <p style="margin: 8px 0; font-weight: 600; border-bottom: 1px solid #ddd; padding-bottom: 5px;">E-E-A-T</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_author'] ? '✓' : '✗'; ?> Author Box</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_blockquotes'] ? '✓' : '✗'; ?> Citations (<?php echo $audit['blockquote_count']; ?>)</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_entities'] ? '✓' : '✗'; ?> Entités (<?php echo $audit['entity_count']; ?>)</p>
        
        <p style="margin: 8px 0; font-weight: 600; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Médias</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_images'] ? '✓' : '✗'; ?> Images (<?php echo $audit['image_count']; ?>)</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_video'] ? '✓' : '✗'; ?> Vidéo (<?php echo $audit['video_count']; ?>)</p>
        <p style="margin: 5px 0;"><?php echo $audit['has_audio'] ? '✓' : '✗'; ?> Audio (<?php echo $audit['audio_count']; ?>)</p>
    </div>

    <?php if (!empty($audit['recommendations'])): ?>
        <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107;">
            <strong style="font-size: 12px;">Recommandations :</strong>
            <ul style="margin: 5px 0; padding-left: 20px; font-size: 11px;">
                <?php foreach ($audit['recommendations'] as $rec): ?>
                    <li><?php echo esc_html($rec); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <p style="margin-top: 15px;">
        <a href="<?php echo admin_url('edit.php?post_type=entity&page=geo-content-audit'); ?>"
           class="button button-primary button-small"
           style="width: 100%;">
            Audit complet
        </a>
    </p>
    <?php
}