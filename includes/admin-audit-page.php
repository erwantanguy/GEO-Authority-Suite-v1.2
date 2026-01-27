<?php
/**
 * GEO Authority Suite - Admin Audit Page
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=entity',
        'Audit GEO',
        'Audit Entites',
        'manage_options',
        'geo-entity-audit',
        'geo_render_entity_audit'
    );
}, 20);

function geo_render_entity_audit() {

    $audit = geo_run_entity_audit();
    $stats = geo_get_entity_stats();

    ?>
    <div class="wrap">
        <h1>Audit de coherence des entites (GEO)</h1>
        <p class="description">
            Verification de la structure Schema.org pour optimiser votre visibilite dans les moteurs IA.
        </p>

        <div class="card" style="max-width: 100%; margin: 20px 0;">
            <h2>Statistiques</h2>
            <p><strong>Nombre total d'entites :</strong> <?php echo $stats['total']; ?></p>

            <?php if (!empty($stats['types'])): ?>
                <h3>Repartition par type :</h3>
                <ul>
                    <?php foreach ($stats['types'] as $type => $count): ?>
                        <li><strong><?php echo esc_html($type); ?> :</strong> <?php echo $count; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (!empty($audit['info'])): ?>
            <div class="notice notice-info">
                <h2>Informations</h2>
                <ul>
                    <?php foreach ($audit['info'] as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($audit['errors'])): ?>
            <div class="notice notice-error">
                <h2>Erreurs critiques</h2>
                <p><strong>Ces problemes doivent etre corriges pour un bon referencement GEO :</strong></p>
                <ul>
                    <?php foreach ($audit['errors'] as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($audit['warnings'])): ?>
            <div class="notice notice-warning">
                <h2>Avertissements</h2>
                <p><strong>Ces ameliorations renforceront votre visibilite dans les IA :</strong></p>
                <ul>
                    <?php foreach ($audit['warnings'] as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (empty($audit['errors']) && empty($audit['warnings'])): ?>
            <div class="notice notice-success">
                <h2>Excellent !</h2>
                <p>Votre site est correctement structure pour le GEO.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($audit['entities'])): ?>
            <div class="card" style="max-width: 100%; margin: 20px 0;">
                <h2>Detail des entites enregistrees</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>@id</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit['entities'] as $entity): ?>
                            <tr>
                                <td><code><?php echo esc_html($entity['@type'] ?? 'N/A'); ?></code></td>
                                <td><strong><?php echo esc_html($entity['name'] ?? 'Sans nom'); ?></strong></td>
                                <td>
                                    <code style="font-size: 11px; word-break: break-all;">
                                        <?php echo esc_html($entity['@id'] ?? 'N/A'); ?>
                                    </code>
                                </td>
                                <td>
                                    <?php if (!empty($entity['url'])): ?>
                                        <a href="<?php echo esc_url($entity['url']); ?>" target="_blank">
                                            <?php echo esc_html($entity['url']); ?>
                                        </a>
                                    <?php else: ?>
                                        <em>Aucune URL</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card" style="max-width: 100%; margin: 20px 0;">
                <h2>JSON-LD genere</h2>
                <p class="description">Voici le code Schema.org genere par votre site :</p>
                <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 10px;">
<?php
echo json_encode(
    array_values($audit['entities']),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
?>
                </textarea>
                <p class="description">
                    <strong>Conseil :</strong> Testez ce code sur
                    <a href="https://validator.schema.org/" target="_blank">Schema.org Validator</a> ou
                    <a href="https://search.google.com/test/rich-results" target="_blank">Google Rich Results Test</a>.
                </p>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 100%; margin: 20px 0;">
            <h2>Recommandations GEO</h2>
            <h3>Pour ameliorer votre visibilite dans les IA :</h3>
            <ul>
                <li><strong>Organization :</strong> Ajoutez un logo, une description detaillee, et des informations de contact</li>
                <li><strong>Person :</strong> Reliez chaque auteur a l'Organization via la propriete "worksFor"</li>
                <li><strong>Contenu :</strong> Utilisez des citations structurees (blockquote)</li>
                <li><strong>FAQ :</strong> Structurez vos FAQ avec Schema.org FAQPage</li>
                <li><strong>llms.txt :</strong> Creez un fichier llms.txt a la racine de votre site</li>
            </ul>
        </div>

        <p style="margin-top: 30px;">
            <a href="<?php echo admin_url('edit.php?post_type=entity&page=geo-entity-audit'); ?>" class="button button-primary">
                Relancer l'audit
            </a>
        </p>
    </div>

    <style>
        .card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .notice ul {
            margin: 10px 0;
        }
        .notice li {
            margin: 5px 0;
        }
    </style>
    <?php
}
