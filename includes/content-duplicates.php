<?php
/**
 * GEO Authority Suite - Content Duplicate Detection
 *
 * Detecte les contenus (articles/pages) trop similaires entre eux
 * via une comparaison de shingles (n-grammes de mots).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calcule les shingles (n-grammes de mots) d'un texte.
 *
 * @param string $text Texte brut.
 * @param int    $size Taille des n-grammes.
 * @return array Set de shingles.
 */
function geo_content_shingles($text, $size = 4) {
    $text = wp_strip_all_tags($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

    if (count($words) < $size) {
        return $words;
    }

    $shingles = [];
    $count = count($words) - $size + 1;
    for ($i = 0; $i < $count; $i++) {
        $shingle = implode(' ', array_slice($words, $i, $size));
        $shingles[$shingle] = true;
    }

    return array_keys($shingles);
}

/**
 * Calcule la similarite de Jaccard entre deux sets de shingles.
 *
 * @param array $a Set A.
 * @param array $b Set B.
 * @return float Similarite 0-1.
 */
function geo_jaccard_similarity($a, $b) {
    if (empty($a) || empty($b)) {
        return 0.0;
    }

    $intersection = count(array_intersect($a, $b));
    $union = count(array_unique(array_merge($a, $b)));

    if ($union === 0) {
        return 0.0;
    }

    return $intersection / $union;
}

/**
 * Trouve les paires de contenus trop similaires.
 *
 * @param array $args Arguments (post_type, threshold, limit).
 * @return array Paires similaires triees par similarite decroissante.
 */
function geo_find_similar_content_pairs($args = []) {
    $defaults = [
        'post_type' => ['post'],
        'threshold'  => 0.6,
        'limit'      => 60,
        'min_words'  => 100,
    ];

    $args = wp_parse_args($args, $defaults);
    $threshold = (float) $args['threshold'];
    $min_words = (int) $args['min_words'];

    $posts = get_posts([
        'post_type'      => $args['post_type'],
        'posts_per_page' => $args['limit'],
        'post_status'    => 'publish',
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ]);

    if (count($posts) < 2) {
        return [];
    }

    // Pre-calculer les shingles
    $shingles = [];
    $word_counts = [];
    foreach ($posts as $post) {
        $content = $post->post_content;
        $word_counts[$post->ID] = str_word_count(wp_strip_all_tags($content));
        if ($word_counts[$post->ID] >= $min_words) {
            $shingles[$post->ID] = geo_content_shingles($content);
        }
    }

    $pairs = [];
    $ids = array_keys($shingles);

    for ($i = 0; $i < count($ids); $i++) {
        for ($j = $i + 1; $j < count($ids); $j++) {
            $similarity = geo_jaccard_similarity($shingles[$ids[$i]], $shingles[$ids[$j]]);

            if ($similarity >= $threshold) {
                $post_a = get_post($ids[$i]);
                $post_b = get_post($ids[$j]);

                $pairs[] = [
                    'post_a'     => $post_a,
                    'post_b'     => $post_b,
                    'similarity' => round($similarity * 100, 1),
                ];
            }
        }
    }

    usort($pairs, function ($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    return $pairs;
}

/**
 * Section de detection de contenus similaires dans l'audit.
 */
add_action('geo_content_audit_extra_section', function ($posts) {
    $pairs = geo_find_similar_content_pairs();

    ?>
    <div class="card" style="padding: 20px; margin: 20px 0;">
        <h2 style="margin-top: 0;">Detection de contenus similaires</h2>
        <p class="description">
            Contenus dont la similarite depasse 60%. Pensez a les fusionner, les differencier
            ou appliquer une redirection 301 vers la version canonique.
        </p>

        <?php if (empty($pairs)) : ?>
            <div style="padding: 12px; background: #d4edda; border-left: 3px solid #28a745; margin-top: 10px;">
                <strong style="color: #155724;">Aucun contenu trop similaire detecte</strong>
                <p style="margin: 5px 0 0; color: #155724; font-size: 12px;">
                    Vos contenus sont suffisamment differencies.
                </p>
            </div>
        <?php else : ?>
            <div style="padding: 12px; background: #fff3cd; border-left: 3px solid #ffc107; margin-top: 10px; margin-bottom: 15px;">
                <strong style="color: #856404;"><?php echo count($pairs); ?> paire(s) de contenus similaires detectee(s)</strong>
            </div>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width: 35%;">Contenu A</th>
                        <th style="width: 35%;">Contenu B</th>
                        <th style="width: 15%;">Similarite</th>
                        <th style="width: 15%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($pairs, 0, 20) as $pair) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($pair['post_a']->ID)); ?>">
                                    <?php echo esc_html($pair['post_a']->post_title); ?>
                                </a>
                                <br>
                                <small style="color: #666;"><?php echo esc_html(get_the_date('d/m/Y', $pair['post_a'])); ?></small>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($pair['post_b']->ID)); ?>">
                                    <?php echo esc_html($pair['post_b']->post_title); ?>
                                </a>
                                <br>
                                <small style="color: #666;"><?php echo esc_html(get_the_date('d/m/Y', $pair['post_b'])); ?></small>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; font-weight: 600;
                                    <?php echo $pair['similarity'] >= 80 ? 'background: #f8d7da; color: #721c24;' : 'background: #fff3cd; color: #856404;'; ?>">
                                    <?php echo esc_html($pair['similarity']); ?>%
                                </span>
                            </td>
                            <td>
                                <small style="color: #666;">
                                    <?php
                                    if ($pair['similarity'] >= 80) {
                                        esc_html_e('Fusionner / rediriger', 'geo-authority-suite');
                                    } else {
                                        esc_html_e('Differencier', 'geo-authority-suite');
                                    }
                                    ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
});
