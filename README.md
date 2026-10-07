# Coordonnées & RGPD

Plugin WordPress qui centralise les coordonnées d'un site et génère ses pages légales conformes LCEN/RGPD.

Développé pour industrialiser la production de sites vitrines : les coordonnées sont saisies une seule fois dans un écran d'administration, puis diffusées partout via des shortcodes. Un changement de numéro de téléphone se répercute sur l'ensemble du site sans toucher au contenu.

**Version courante :** 4.3.0 · **WordPress :** 5.0+ · **PHP :** 7.4+

---

## Ce que fait le plugin

**Centralisation des coordonnées**
Téléphone, email, adresse d'établissement, adresse de siège, forme juridique, SIRET, TVA, RCS, directeur de publication, hébergeur. Saisi une fois, réutilisé partout.

**Génération des pages légales**
Mentions légales et politique de confidentialité produites à partir des données saisies, avec les mentions imposées par la LCEN et le RGPD. Le texte s'adapte à la forme juridique de la structure.

**Recherche SIRET automatique**
Saisie du SIRET dans l'administration, le plugin interroge l'API publique `recherche-entreprises.api.gouv.fr` et pré-remplit la raison sociale, l'adresse, la forme juridique et le SIREN. Le numéro de TVA intracommunautaire et le RCS sont calculés à partir du SIREN. Requête en AJAX, protégée par nonce.

**Analyse des formulaires**
Le plugin lit les formulaires Forminator et SureForms présents sur le site, classe chaque champ par catégorie de données personnelles, et génère la section correspondante de la politique de confidentialité : finalité du traitement, base légale et durée de conservation. Des valeurs par défaut sont proposées selon le type de formulaire détecté (contact, devis, candidature, newsletter), et restent modifiables.

**Durées de conservation alignées sur Forminator**
Pour un formulaire Forminator, la durée par défaut est « Selon Forminator » : la politique affiche la durée réellement appliquée par Forminator (réglage global ou propre au formulaire), avec l'anonymisation de l'adresse IP. Si une durée est saisie à la main, l'onglet RGPD signale les incohérences : conservation sans limite dans Forminator, ou Forminator qui conserve plus longtemps que la durée annoncée. Lecture seule : le plugin n'écrit jamais dans les réglages de Forminator.

**Cloudflare Turnstile**
Quand un formulaire Forminator utilise Turnstile (sans cookie, donc absent de la liste de WPConsent), la politique de confidentialité ajoute un paragraphe « Protection contre les robots » : données transmises à Cloudflare, base légale, transfert vers les États-Unis encadré par le Data Privacy Framework.

**Données structurées via SEOPress**
L'onglet SEO écrit dans le Knowledge Graph de SEOPress (9.8 minimum) au lieu de produire un JSON-LD concurrent : type d'entité (LocalBusiness, NGO, Organization...), nom, description, logo, réseaux sociaux. Les champs d'identité (raison sociale, téléphone au format international, e-mail, TVA, adresse découpée en rue, code postal et ville) sont synchronisés automatiquement depuis les onglets Coordonnées et Juridique à chaque enregistrement. Seules ces clés sont modifiées, les autres réglages de SEOPress restent intacts, et la page d'accueil est purgée dans W3 Total Cache.

**Multilingue et multi-pays**
Jeux de textes légaux en français et en anglais. Indicatifs téléphoniques gérés pour la France, la Belgique, la Suisse, le Luxembourg, l'Allemagne, l'Espagne, l'Italie et le Royaume-Uni.

**Mise à jour automatique depuis GitHub**
Le plugin se met à jour depuis ce dépôt, directement dans l'écran des extensions de WordPress. Détail d'implémentation dans la section dédiée plus bas.

---

## Shortcodes

### Coordonnées

| Shortcode | Rendu |
|---|---|
| `[client_email]` | Email cliquable |
| `[client_tel]` | Téléphone formaté et cliquable |
| `[client_address]` | Adresse de l'établissement |
| `[client_address_siege]` | Adresse du siège social |
| `[site_title]` | Nom du site |
| `[site_link]` | Lien vers l'accueil |

### Blocs RGPD

| Shortcode | Rendu |
|---|---|
| `[rgpd_mentions]` | Mention de consentement, à placer sous un formulaire |
| `[rgpd_droits]` | Rappel des droits de la personne concernée |
| `[rgpd_cookies]` | Bloc d'information sur les cookies |

### Pages légales

| Shortcode | Rendu |
|---|---|
| `[mentions_legales]` | Page de mentions légales complète |
| `[politique_confidentialite]` | Politique de confidentialité complète |

### Divers

| Shortcode | Rendu |
|---|---|
| `[matrys_block]` | Bloc de crédit de l'agence |

---

## Écran d'administration

Un menu unique, six onglets :

- **Coordonnées** : téléphone, email, adresses
- **Juridique** : forme juridique, SIRET, TVA, RCS, directeur de publication, hébergeur, avec la recherche SIRET
- **Agence** : coordonnées du prestataire affichées en pied de page
- **RGPD** : analyse des formulaires détectés, configuration des traitements, contrôle des durées Forminator
- **SEO** : Knowledge Graph de SEOPress (type d'entité, logo, réseaux sociaux, synchronisation de l'identité)
- **Shortcodes** : liste de référence à copier

Chaque onglet possède son propre formulaire, de sorte que la validation des champs obligatoires d'un onglet ne bloque pas l'enregistrement d'un autre.

---

## Mise à jour depuis GitHub

Le plugin s'accroche à `pre_set_site_transient_update_plugins` et `plugins_api` pour apparaître dans les mises à jour natives de WordPress.

Il ne consulte pas l'API GitHub. Il envoie une requête HEAD sur `/releases/latest` et lit le tag dans l'en-tête `Location` de la redirection 302. Deux avantages : aucun quota d'API à respecter, et aucun jeton d'authentification à distribuer sur les sites installés. Le tag est lu tel quel dans l'en-tête et jamais reconstruit, pour rester compatible avec des conventions de nommage variables.

Le résultat est mis en cache dans un transient, avec une durée de vie plus courte en cas d'échec afin de retenter rapidement sans marteler le serveur.

Le paquet téléchargé est l'archive du tag. Comme GitHub y ajoute un dossier racine du type `nom-du-depot-4.1.6`, un filtre `upgrader_source_selection` renomme ce dossier avant l'installation, faute de quoi WordPress installerait une seconde extension au lieu de mettre à jour l'existante.

**Publier une mise à jour** revient donc à créer une release taguée sur ce dépôt, après avoir incrémenté la version dans l'en-tête du plugin et la constante `CCRGPD_VERSION`.

---

## Installation

1. Télécharger l'archive de la [dernière release](https://github.com/JulienBataille/client-coordonnees-rgpd/releases/latest)
2. Extensions → Ajouter → Téléverser une extension
3. Activer
4. Ouvrir le menu **Coordonnées & RGPD** et renseigner l'onglet Coordonnées, puis l'onglet Juridique
5. Créer les pages Mentions légales et Politique de confidentialité, et y placer les shortcodes correspondants

Les mises à jour suivantes remontent automatiquement dans WordPress.

Note : l'archive téléchargée directement depuis le bouton « Code » de GitHub contient un dossier racine suffixé par le nom de la branche. Il faut le renommer avant l'installation manuelle, ou passer par une release.

---

## Structure

```
client_coordonnees.php          Amorce : constantes, chargement, hooks d'activation
includes/
  constants.php                 Champs, pays, jeux de textes légaux FR et EN
  class-plugin.php              Initialisation et activation
  class-admin.php               Écran d'administration, Settings API, endpoints AJAX
  class-shortcodes.php          Déclaration et rendu des 12 shortcodes
  class-form-analyzer.php       Lecture Forminator/SureForms, classification des champs
  class-forminator-privacy.php  Lecture des durées de conservation et de Turnstile dans Forminator
  class-retention.php           Durée affichée dans la politique et contrôle de cohérence
  class-seopress.php            Onglet SEO : écriture dans le Knowledge Graph de SEOPress
  class-api-entreprises.php     Client de l'API Recherche d'entreprises, calculs TVA et RCS
  class-matrys-github-updater.php   Mise à jour depuis les releases GitHub
assets/                         CSS et JS de l'administration
```

---

## Dépendances

Aucune dépendance obligatoire. L'onglet RGPD ne devient utile qu'en présence de [Forminator](https://wordpress.org/plugins/forminator/) ou de [SureForms](https://wordpress.org/plugins/sureforms/), et propose leur installation le cas échéant. L'onglet SEO demande [SEOPress](https://wordpress.org/plugins/wp-seopress/) 9.8 ou plus.

La recherche SIRET utilise l'API publique [Recherche d'entreprises](https://recherche-entreprises.api.gouv.fr), qui ne demande pas de clé.

---

## Avertissement

Ce plugin produit des textes légaux à partir des informations saisies. Il facilite la mise en conformité, il ne la garantit pas. Les textes générés doivent être relus et adaptés à la situation réelle de chaque structure.

---

## Licence

GPL v2 ou ultérieure, comme WordPress.

---

## Historique

**4.3.0**
- Correctif : les durées suggérées n'existaient pas dans la liste des durées. Un formulaire jamais configuré affichait « - » dans la politique, et le premier enregistrement de l'onglet RGPD enregistrait « 6 mois » sans que personne l'ait choisi. Les sites déjà configurés gardent leur valeur : l'onglet RGPD signale désormais l'écart avec Forminator.
- Correctif : la section « Traitements » n'apparaissait pas dans la politique tant que l'onglet RGPD n'avait jamais été enregistré.
- Correctif : un champ « Adresse e-mail » était annoncé comme « adresse postale » dans les données collectées.
- Correctif : un formulaire « Désinscription » recevait la finalité d'un formulaire d'inscription.
- Durées de conservation « Selon Forminator », alertes de cohérence, anonymisation de l'IP.
- Paragraphe Cloudflare Turnstile dans la politique.
- Onglet SEO : écriture dans le Knowledge Graph de SEOPress, synchronisation automatique de l'identité.
- Plus de tiret cadratin dans les textes générés.
- Purge du cache de page (W3 Total Cache, WP Rocket) quand une coordonnée, une donnée juridique ou la configuration RGPD change : elles sont affichées sur toutes les pages.
- Import SIRET : forme juridique, code NAF et état affichés avant l'import ; si le SIREN change, un avertissement demande de vérifier le responsable de publication (conservé pour une association, à choisir parmi les dirigeants pour une société), le capital précédent n'est pas repris et il est vidé en mode écrasement.
- Onglet SEO : icône carrée du site proposée comme logo (icône WordPress ou Favicon by RealFaviconGenerator), alerte sous 112 x 112 px, alertes de cohérence avec l'onglet Juridique (type NGO pour une société, TVA renseignée pour une association).
