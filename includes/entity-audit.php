<?php
/**
 * GEO Authority Suite - Entity Audit
 */

if (!defined('ABSPATH')) {
    exit;
}

function geo_run_entity_audit(): array {

    do_action('wp_head');

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
