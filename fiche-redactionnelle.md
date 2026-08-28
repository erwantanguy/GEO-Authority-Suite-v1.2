# GEO Authority Suite — Fiche rédactionnelle

> **Plugin WordPress** — Gestion avancée des entités Schema.org, JSON-LD, llms.txt, indexation IA et audit de contenu pour le **SEO**, l’**AEO** et le **GEO**.

---

## Téléchargement

- **Version actuelle :** 1.6.1
- **Fichier ZIP :** [https://dl.ticoet.me/downloads/pluginsWP/geo-authority-suite/geo-authority-suite.zip](https://dl.ticoet.me/downloads/pluginsWP/geo-authority-suite/geo-authority-suite.zip)
- **Mise à jour :** automatique via le tableau de bord WordPress (plugin-update-checker)

---

## Qu’est-ce que GEO Authority Suite ?

**GEO Authority Suite** est un plugin WordPress qui transforme votre site en une base de connaissances structurée pour les moteurs de recherche et les IA génératives.

Il permet de créer, organiser et relier des **entités Schema.org** (Organization, Person, LocalBusiness, Service, Product, Place, etc.) et de générer automatiquement le JSON-LD correspondant dans le `<head>` de chaque page.

L’objectif : améliorer la **compréhension sémantique** du site, renforcer l’**E-E-A-T** et maximiser les chances d’être cité ou affiché par Google, ChatGPT, Claude, Perplexity et autres assistants.

---

## Pourquoi utiliser GEO Authority Suite ?

| Objectif | Comment GEO Authority Suite aide |
|---|---|
| **SEO local** | Entités LocalBusiness + geo + hasMap + horaires d’ouverture |
| **E-E-A-T** | Personnes, auteurs, organisations et relations claires |
| **AEO** | Réponses structurées et entités connectées |
| **GEO** | Données exploitables par les moteurs de réponse et les IA |
| **Knowledge Graph** | `@id` canoniques et relations `sameAs`, `worksFor`, `parentOrganization` |
| **Indexation IA** | Génération de `llms.txt` et signaux d’autorité |

---

## Les entités disponibles

### 1. Organization
Représente l’entreprise ou la marque principale.

Champs : nom, URL, téléphone, adresse postale, logo, description, réseaux sociaux (`sameAs`).

### 2. LocalBusiness / ProfessionalService / Restaurant / Store
Version locale d’une Organization avec :

- coordonnées GPS (`geo`)
- lien Google Maps (`hasMap`)
- horaires d’ouverture (`openingHoursSpecification`)
- gamme de prix (`priceRange`)
- avis agrégés (`aggregateRating`)
- activité/sport (`sport`)

### 3. Person
Représente un auteur, un expert ou un fondateur.

Peut être lié automatiquement à un utilisateur WordPress via la page de profil.

Relations : `worksFor`, `memberOf`, `alumniOf`, `knowsAbout`.

### 4. Service / Product / OfferCatalog
Décrit les services ou produits proposés.

Peut être relié automatiquement à une Organization via `provider` ou `hasOfferCatalog`.

### 5. Place / Event / Article / WebPage / FAQPage
Autres types d’entités supportés pour enrichir le graphe de connaissances du site.

---

## Fonctionnalités clés

### JSON-LD automatique
Chaque entité publiée est injectée dans le `<head>` sous forme de `@graph` Schema.org.

### Multi-type
Une Organization peut devenir automatiquement `["LocalBusiness", "SportsOrganization"]` si un sport est renseigné.

### parentOrganization
Pour éviter la confusion entre Organization et LocalBusiness, il est possible de lier une entité locale à son Organization parente.

```json
"parentOrganization": {
  "@id": "https://karting-rennes.fr/#organization-racing-kart-rennais"
}
```

### Filtre public `geo_jsonld_output`
Les développeurs peuvent enrichir ou modifier le graphe JSON-LD depuis le `functions.php` du thème.

### Liaison utilisateur ↔ entité Person
Dans chaque profil WordPress, on peut lier un utilisateur à une entité Person existante. Le JSON-LD généré utilisera alors cette entité au lieu du profil WordPress basique.

### llms.txt
Génération automatique d’un fichier `llms.txt` contenant les entités et contenus clés du site, facilitant l’indexation par les IA.

### Audit de contenu
Outils d’analyse pour identifier les pages manquant de structure, d’entités ou de signaux d’autorité.

---

## Cas d’usage concret : Karting Rennes Cap Malo

Avec GEO Authority Suite, on crée les entités suivantes :

1. **RACING KART RENNAIS** — type `Organization`
2. **Karting Rennes Cap Malo** — type `LocalBusiness` avec `parentOrganization`

Le JSON-LD généré inclut :

```json
{
  "@type": ["LocalBusiness", "SportsOrganization"],
  "@id": "https://karting-rennes.fr/#localbusiness-karting-rennes-cap-malo",
  "name": "Karting Rennes Cap Malo",
  "parentOrganization": {
    "@id": "https://karting-rennes.fr/#organization-racing-kart-rennais"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 48.200048,
    "longitude": -1.722774
  },
  "hasMap": "https://maps.google.com/?cid=662770831234903851",
  "openingHoursSpecification": [...],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": 4.2,
    "reviewCount": 826
  },
  "sport": "Karting",
  "priceRange": "€€"
}
```

Résultat : Google et les IA comprennent parfaitement l’activité, l’emplacement, les horaires et la réputation du lieu.

---

## Compatibilité

- **WordPress :** 6.0+
- **PHP :** 7.4+
- **Multilingue :** compatible WPML / Polylang (via filtres)

---

## Installation

1. Télécharger le ZIP : [geo-authority-suite.zip](https://dl.ticoet.me/downloads/pluginsWP/geo-authority-suite/geo-authority-suite.zip)
2. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**
3. Activer le plugin
4. Aller dans **GEO Authority → Entités** et créer votre première Organization

---

## À qui s’adresse ce plugin ?

- Agences SEO / AEO / GEO
- Sites locaux et commerces
- Entreprises B2B et B2C
- Sites de contenu souhaitant structurer leur autorité sémantique
- Toute organisation voulant améliorer sa visibilité auprès des IA génératives

---

## Auteur

**Erwan Tanguy — Ticoët**
- Site : [https://www.ticoet.fr/](https://www.ticoet.fr/)
- Téléchargement : [https://dl.ticoet.me/downloads/pluginsWP/geo-authority-suite/](https://dl.ticoet.me/downloads/pluginsWP/geo-authority-suite/)
