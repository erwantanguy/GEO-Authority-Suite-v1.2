# GEO Authority Suite

**Version :** 1.3.0  
**Compatibilité :** WordPress 6.0+, PHP 7.4+  
**Auteur :** Erwan Tanguy - Ticoët  
**Licence :** GPL2+

## Description

GEO Authority Suite est un plugin WordPress complet pour le **GEO (Generative Engine Optimization)**. Il permet d'optimiser votre site pour les moteurs de recherche génératifs (ChatGPT, Claude, Perplexity, Google AI Overview) en gérant les entités Schema.org, le JSON-LD, et les directives d'indexation IA.

## Fonctionnalités

### Gestion des entités Schema.org

- **Types supportés** : Organization, Person, LocalBusiness, Product, Service, Place, Event
- **Relations entre entités** : worksFor, memberOf, affiliation
- **Génération JSON-LD** automatique et optimisée
- **Shortcode** `[entity id=X]` pour mentionner les entités dans le contenu
- **Détection des doublons** pour éviter les entités en double

### Fichier llms.txt

- Génération automatique du fichier `/llms.txt`
- Index structuré pour les crawlers IA
- Inclut les articles récents, pages principales et entités
- Scores GEO par contenu (si GEO Content Optimizer installé)
- Section dédiée à l'indexation IA (v1.1)

### Indexation IA (nouveau v1.3)

- **Directives HTML** : `data-noai="true"` et `data-nollm="true"`
- **Meta robots** : `<meta name="robots" content="noai, nollm">`
- **Déclaration de contenu** : `ai-content-declaration` (original, ai-assisted, ai-generated)
- **Exclusions globales** par type de contenu
- **Exclusions individuelles** via metabox sur chaque post/page

### Sitemap IA (/ai-sitemap.xml)

- Sitemap XML dédié aux crawlers IA
- Métadonnées enrichies par URL :
  - `ai:score` : Score GEO du contenu
  - `ai:declaration` : Type de contenu
  - `ai:summary` : Résumé automatique
  - `ai:entities` : Entités mentionnées
- Filtrage par score minimum
- Exclusion automatique des contenus marqués `noai`

### Audits

- **Audit des entités** : Vérification de la complétude des données Schema.org
- **Audit du contenu** : Analyse de la qualité GEO des articles

## Installation

1. Télécharger le plugin et extraire dans `/wp-content/plugins/`
2. Activer le plugin dans **Extensions > Extensions installées**
3. Configurer dans **Entités > Indexation IA**

## Configuration

### Entités

1. Aller dans **Entités > Ajouter**
2. Créer une Organization principale (votre entreprise/site)
3. Créer les Person (auteurs, employés) et les relier à l'Organization
4. Utiliser le shortcode `[entity id=X]` dans vos articles

### Indexation IA

1. Aller dans **Entités > Indexation IA**
2. Configurer les exclusions globales par type de contenu
3. Définir la déclaration de contenu par défaut
4. Activer/configurer le sitemap IA

### llms.txt

1. Aller dans **Entités > llms.txt**
2. Configurer le nombre d'articles à inclure
3. Générer le fichier manuellement ou activer la génération automatique

## Utilisation

### Shortcode entity

```
J'ai rencontré [entity id=5] lors de la conférence organisée par [entity id=2].
```

### Metabox Indexation IA

Sur chaque article/page, une metabox permet de :
- Exclure le contenu de l'indexation IA
- Exclure le contenu des LLM spécifiquement
- Déclarer le type de contenu (original, assisté IA, généré IA)

### Vérification

- **JSON-LD** : Afficher le code source (Ctrl+U) et chercher `<script type="application/ld+json">`
- **Validation** : [Schema.org Validator](https://validator.schema.org/)
- **Sitemap IA** : Visiter `/ai-sitemap.xml`
- **llms.txt** : Visiter `/llms.txt`

## Structure des fichiers

```
geo-authority-suite/
├── geo-authority-suite.php     # Fichier principal
├── README.md                   # Cette documentation
├── LICENSE                     # Licence GPL2
├── assets/
│   └── admin.css               # Styles admin
└── includes/
    ├── ai-indexing.php         # Directives d'indexation IA
    ├── ai-sitemap.php          # Générateur sitemap IA
    ├── admin-ai-indexing-page.php  # Interface admin indexation IA
    ├── admin-audit-page.php    # Interface audits
    ├── content-audit.php       # Audit de contenu
    ├── cpt-entity.php          # Custom Post Type Entity
    ├── duplicate-detection.php # Détection doublons
    ├── entity-audit.php        # Audit des entités
    ├── entity-id.php           # Gestion des IDs d'entités
    ├── entity-registry.php     # Registre des entités
    ├── jsonld-output.php       # Génération JSON-LD
    ├── llms-generator.php      # Générateur llms.txt
    ├── meta-boxes.php          # Metaboxes admin
    ├── schema-organization.php # Schema Organization
    └── schema-person.php       # Schema Person
```

## Hooks et filtres

### Filtres

| Filtre | Description |
|--------|-------------|
| `geo_llms_content` | Modifier le contenu du llms.txt |
| `geo_ai_indexing_post_types` | Types de posts pour la metabox IA |
| `geo_jsonld_output` | Modifier le JSON-LD avant output |

### Actions

| Action | Description |
|--------|-------------|
| `geo_after_entity_save` | Après sauvegarde d'une entité |
| `geo_before_llms_generate` | Avant génération du llms.txt |

## Compatibilité

- **GEO Content Optimizer** : Affichage des scores GEO dans llms.txt et sitemap IA
- **Yoast SEO** : Compatible, pas de conflit sur les meta tags
- **Rank Math** : Compatible

## Changelog

### 1.3.0
- Ajout des directives d'indexation IA (data-noai, data-nollm)
- Ajout du sitemap IA (/ai-sitemap.xml)
- Ajout des meta tags ai-content-declaration
- Extension du llms.txt avec sections IA (v1.1)
- Nouvelle interface admin "Indexation IA"
- Metabox Indexation IA sur posts/pages

### 1.2.0
- Amélioration de la génération JSON-LD
- Ajout de la détection des doublons
- Interface d'audit améliorée

### 1.1.0
- Ajout du générateur llms.txt
- Support des relations entre entités

### 1.0.0
- Version initiale
- Gestion des entités Schema.org
- Génération JSON-LD

## Support

Pour toute question ou suggestion :
- **Site web** : [ticoet.fr](https://www.ticoet.fr/)
- **Documentation** : Menu **Entités > Aide** dans WordPress

## Licence

Ce plugin est distribué sous licence GPL2+. Voir le fichier LICENSE pour plus de détails.
