<?php
/**
 * GEO Authority Suite - Entity Audit
 */

if (!defined('ABSPATH')) {
    exit;
}

function geo_run_entity_audit(): array {

    // Peupler le registre d'entités en contexte admin (wp_head ne s'exécute pas ici)
    if (function_exists('geo_register_all_entities') && geo_count_entities() === 0) {
        geo_register_all_entities();
    }

    $entities = geo_get_entities();
    $count = count($entities);

    $results = [
        'errors'   => [],
        'warnings' => [],
        'info'     => [],
        'entities' => $entities,
        'count'    => $count,
        'sources'  => [],
    ];

    $results['info'][] = sprintf('Nombre total d\'entites detectees : %d', $count);

    if ($count === 0) {
        $results['errors'][] = 'Aucune entite n\'a ete enregistree.';
        return $results;
    }

    $organizations = array_filter($entities, function ($entity) {
        return ($entity['@type'] ?? '') === 'Organization';
    });

    $org_count = count($organizations);
    $results['info'][] = sprintf('Entites Organization trouvees : %d', $org_count);

    if ($org_count === 0) {
        $results['errors'][] = 'Aucune entite Organization detectee. Votre site doit avoir une organisation principale.';
    } elseif ($org_count > 1) {
        $results['errors'][] = sprintf(
            'Plusieurs entites Organization detectees (%d). Il ne devrait y en avoir qu\'une seule.',
            $org_count
        );
    } else {
        $org = array_values($organizations)[0];

        if (empty($org['name'])) {
            $results['errors'][] = 'L\'Organization n\'a pas de nom.';
        }

        if (empty($org['url'])) {
            $results['warnings'][] = 'L\'Organization n\'a pas d\'URL.';
        }

        if (empty($org['description'])) {
            $results['warnings'][] = 'L\'Organization n\'a pas de description.';
        }

        if (empty($org['logo'])) {
            $results['warnings'][] = 'L\'Organization n\'a pas de logo.';
        }

        $results['info'][] = 'Organization valide : "' . ($org['name'] ?? 'Sans nom') . '"';
    }

    $persons = array_filter($entities, function ($entity) {
        return ($entity['@type'] ?? '') === 'Person';
    });

    $person_count = count($persons);
    $results['info'][] = sprintf('Entites Person trouvees : %d', $person_count);

    foreach ($persons as $person) {
        $person_name = $person['name'] ?? 'Personne sans nom';

        if (empty($person['@id'])) {
            $results['errors'][] = sprintf('La personne "%s" n\'a pas de @id.', $person_name);
        }

        if (empty($person['name'])) {
            $results['errors'][] = 'Une entite Person n\'a pas de nom.';
        }

        if (empty($person['worksFor'])) {
            $results['warnings'][] = sprintf(
                'La personne "%s" n\'est pas reliee a une Organization (worksFor manquant).',
                $person_name
            );
        }
    }

    foreach ($entities as $entity) {
        $entity_type = $entity['@type'] ?? 'Type inconnu';
        $entity_name = $entity['name'] ?? 'Sans nom';

        if (empty($entity['@id'])) {
            $results['errors'][] = sprintf('Une entite de type "%s" est depourvue de @id.', $entity_type);
        }

        if (!empty($entity['@id'])) {
            $id = $entity['@id'];
            $home_url = home_url();

            if (strpos($id, $home_url) !== 0) {
                $results['warnings'][] = sprintf(
                    'Le @id de "%s" ne commence pas par l\'URL du site.',
                    $entity_name
                );
            }

            if (strpos($id, '#') === false) {
                $results['warnings'][] = sprintf(
                    'Le @id de "%s" ne contient pas de fragment (#).',
                    $entity_name
                );
            }
        }
    }

    $ids = array_column($entities, '@id');
    $duplicates = array_filter(array_count_values($ids), function ($count) {
        return $count > 1;
    });

    if (!empty($duplicates)) {
        foreach ($duplicates as $id => $count) {
            $results['errors'][] = sprintf(
                'Le @id "%s" est utilise %d fois. Chaque entite doit avoir un identifiant unique.',
                $id,
                $count
            );
        }
    }

    if (empty($results['errors']) && empty($results['warnings'])) {
        $results['info'][] = 'Aucune incoherence detectee. Le graphe d\'entites est propre.';
    } else {
        $error_count = count($results['errors']);
        $warning_count = count($results['warnings']);

        if ($error_count > 0) {
            $results['info'][] = sprintf('%d erreur(s) critique(s) a corriger.', $error_count);
        }

        if ($warning_count > 0) {
            $results['info'][] = sprintf('%d avertissement(s) a considerer.', $warning_count);
        }
    }

    return $results;
}

function geo_get_entity_stats(): array {
    $entities = geo_get_entities();

    $stats = [
        'total' => count($entities),
        'types' => [],
    ];

    foreach ($entities as $entity) {
        $type = $entity['@type'] ?? 'Unknown';
        $stats['types'][$type] = ($stats['types'][$type] ?? 0) + 1;
    }

    return $stats;
}

/**
 * Audit de coherence entite <-> contenu.
 *
 * Verifie que chaque entite publiee est mentionnee dans au moins un contenu
 * (shortcode [entity], nom de l'entite ou synonyme presents dans le contenu).
 *
 * @return array Resultats d'audit.
 */
function geo_run_entity_content_coherence(): array {
    $results = [
        'checked'     => 0,
        'mentioned'  => 0,
        'orphan'      => [],
        'linked_only' => [],
    ];

    $entity_posts = get_posts([
        'post_type'      => 'entity',
        'posts_per_page' => 100,
        'post_status'    => 'publish',
    ]);

    if (empty($entity_posts)) {
        return $results;
    }

    // Indexer les contenus publiés une seule fois
    $contents = get_posts([
        'post_type'      => ['post', 'page'],
        'posts_per_page' => 200,
        'post_status'    => 'publish',
    ]);

    $corpus = [];
    foreach ($contents as $content) {
        $corpus[$content->ID] = mb_strtolower($content->post_content . ' ' . $content->post_title, 'UTF-8');
    }
    $full_corpus = implode("\n", $corpus);

    foreach ($entity_posts as $entity_post) {
        $results['checked']++;

        $canonical = get_post_meta($entity_post->ID, '_entity_canonical', true);
        $name = !empty($canonical) ? $canonical : get_the_title($entity_post);
        $name_lower = mb_strtolower($name, 'UTF-8');

        // 1. Verification via shortcode [entity id="X"]
        $has_shortcode = false;
        foreach ($corpus as $content_id => $text) {
            if (has_shortcode($text, 'entity') === false && strpos($text, '[entity') === false) {
                continue;
            }
            $content_post = get_post($content_id);
            if ($content_post && preg_match('/\[entity[^\]]*id=["\']?' . $entity_post->ID . '["\']?/i', $content_post->post_content)) {
                $has_shortcode = true;
                break;
            }
        }

        // 2. Verification de la mention textuelle (nom ou synonymes)
        $has_mention = $name_lower !== '' && mb_strpos($full_corpus, $name_lower) !== false;

        $synonyms = get_post_meta($entity_post->ID, '_entity_synonyms', true);
        $has_synonym = false;
        if (!empty($synonyms)) {
            $synonym_list = array_filter(array_map('trim', explode(',', $synonyms)));
            foreach ($synonym_list as $synonym) {
                if (mb_strpos($full_corpus, mb_strtolower($synonym, 'UTF-8')) !== false) {
                    $has_synonym = true;
                    break;
                }
            }
        }

        if ($has_shortcode || $has_mention || $has_synonym) {
            $results['mentioned']++;
            if ($has_shortcode && !$has_mention && !$has_synonym) {
                $results['linked_only'][] = [
                    'id'    => $entity_post->ID,
                    'name'  => $name,
                    'edit'  => get_edit_post_link($entity_post->ID, 'raw'),
                ];
            }
        } else {
            $results['orphan'][] = [
                'id'    => $entity_post->ID,
                'name'  => $name,
                'edit'  => get_edit_post_link($entity_post->ID, 'raw'),
            ];
        }
    }

    return $results;
}

/**
 * Section de coherence entite <-> contenu dans l'audit d'entites.
 */
add_action('geo_entity_audit_extra_section', function () {
    $coherence = geo_run_entity_content_coherence();
    $orphan_count = count($coherence['orphan']);
    $linked_only_count = count($coherence['linked_only']);
    ?>
    <div class="card" style="padding: 20px; margin: 20px 0;">
        <h2 style="margin-top: 0;">Coherence entite &harr; contenu</h2>
        <p class="description">
            Verifie que vos entites sont mentionnees dans vos contenus.
            Une entite jamais citee n'a presque aucune valeur pour Google et les IA.
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin: 15px 0;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <div style="font-size: 26px; font-weight: 600; color: #0073aa;"><?php echo (int) $coherence['checked']; ?></div>
                <div style="font-size: 12px; color: #666;">Entites verifiees</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f0fdf4; border-radius: 4px;">
                <div style="font-size: 26px; font-weight: 600; color: #16a34a;"><?php echo (int) $coherence['mentioned']; ?></div>
                <div style="font-size: 12px; color: #666;">Mentionnees</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #fffbeb; border-radius: 4px;">
                <div style="font-size: 26px; font-weight: 600; color: #d97706;"><?php echo $orphan_count; ?></div>
                <div style="font-size: 12px; color: #666;">Orphelines</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f0f7ff; border-radius: 4px;">
                <div style="font-size: 26px; font-weight: 600; color: #2271b1;"><?php echo $linked_only_count; ?></div>
                <div style="font-size: 12px; color: #666;">Shortcode uniquement</div>
            </div>
        </div>

        <?php if ($orphan_count > 0) : ?>
            <h3 style="color: #d97706;">Entites orphelines (jamais mentionnees)</h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Entite</th>
                        <th>Action recommandee</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($coherence['orphan'], 0, 20) as $orphan) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($orphan['edit']); ?>"><?php echo esc_html($orphan['name']); ?></a>
                            </td>
                            <td>
                                <small style="color: #666;">
                                    <?php
                                    $entity_count = (int) $coherence['checked'];
                                    if ($orphan_count > $entity_count * 0.5) {
                                        esc_html_e('Citez-la dans un article pertinent ou supprimez-la', 'geo-authority-suite');
                                    } else {
                                        esc_html_e('Creez du contenu la mentionnant ou ciblez-la dans un article existant', 'geo-authority-suite');
                                    }
                                    ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($linked_only_count > 0) : ?>
            <h3 style="color: #2271b1; margin-top: 15px;">Entites liees par shortcode uniquement (pas de mention texte)</h3>
            <p class="description">
                Ces entites sont utilisees via le shortcode [entity] mais leur nom n'apparait pas
                naturellement dans le texte. Pensez a egalement les citer en texte brut.
            </p>
            <ul style="list-style: disc; padding-left: 20px;">
                <?php foreach (array_slice($coherence['linked_only'], 0, 10) as $item) : ?>
                    <li>
                        <a href="<?php echo esc_url($item['edit']); ?>"><?php echo esc_html($item['name']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($orphan_count === 0 && $linked_only_count === 0) : ?>
            <div style="padding: 12px; background: #d4edda; border-left: 3px solid #28a745;">
                <strong style="color: #155724;">Coherence parfaite</strong>
                <p style="margin: 5px 0 0; color: #155724; font-size: 12px;">
                    Toutes vos entites sont mentionnees dans vos contenus.
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php
});
