<?php
/**
 * Compatibilité SEOPress : Knowledge Graph (données structurées de l'organisation)
 *
 * SEOPress (gratuit, 9.8 et plus) imprime lui-même le JSON-LD Organization / LocalBusiness / NGO...
 * sur la page d'accueil. Le plugin n'écrit que dans ses réglages (option seopress_social_option_name) :
 * - champs d'identité (raison sociale, téléphone, e-mail, TVA, adresse) synchronisés depuis les onglets
 *   Coordonnées et Juridique quand la synchronisation est active ;
 * - champs descriptifs (type, nom, description, logo, réseaux sociaux...) édités dans l'onglet SEO.
 *
 * Écriture : tableau complet relu, seules nos clés modifiées, puis seopress_sanitize_options_fields()
 * (même nettoyage que l'écran SEOPress). Jamais par l'API REST de SEOPress, qui remplace tout le tableau.
 */
defined('ABSPATH') || exit;

class CCRGPD_SEOPress
{
    public const OPTION = 'seopress_social_option_name';
    public const SETTINGS = 'ccrgpd_seopress';
    public const MIN_VERSION = '9.8';

    public const TYPES = [
        'LocalBusiness' => 'Entreprise locale (LocalBusiness)',
        'Organization' => 'Organisation (Organization)',
        'NGO' => 'Association, ONG (NGO)',
        'Corporation' => 'Société (Corporation)',
        'EducationalOrganization' => 'Établissement d\'enseignement',
        'GovernmentOrganization' => 'Organisme public',
        'OnlineBusiness' => 'Entreprise en ligne',
        'OnlineStore' => 'Boutique en ligne',
        'NewsMediaOrganization' => 'Média',
        'Person' => 'Personne (Person)',
        'none' => 'Aucun (Knowledge Graph désactivé)',
    ];

    /** Champs d'identité : source = onglets Coordonnées et Juridique */
    public const IDENTITY_FIELDS = [
        'seopress_social_knowledge_legal_name' => 'Raison sociale',
        'seopress_social_knowledge_phone' => 'Téléphone',
        'seopress_social_knowledge_email' => 'E-mail',
        'seopress_social_knowledge_tax_id' => 'N° TVA intracommunautaire',
        'seopress_social_knowledge_street' => 'Rue',
        'seopress_social_knowledge_postal_code' => 'Code postal',
        'seopress_social_knowledge_locality' => 'Ville',
        'seopress_social_knowledge_country' => 'Pays (code ISO)',
    ];

    /** Champs descriptifs : source = SEOPress, édités dans l'onglet SEO */
    public const TEXT_FIELDS = [
        'seopress_social_knowledge_name' => 'Nom affiché',
        'seopress_social_knowledge_region' => 'Région',
        'seopress_social_knowledge_founding_date' => 'Date de création',
        'seopress_social_knowledge_employees' => 'Effectif',
    ];

    public const SOCIAL_FIELDS = [
        'seopress_social_accounts_facebook' => 'Facebook',
        'seopress_social_accounts_instagram' => 'Instagram',
        'seopress_social_accounts_linkedin' => 'LinkedIn',
        'seopress_social_accounts_youtube' => 'YouTube',
        'seopress_social_accounts_pinterest' => 'Pinterest',
        'seopress_social_accounts_twitter' => 'X (Twitter), identifiant @',
    ];

    /** Options du plugin qui déclenchent une synchronisation */
    private const SOURCE_OPTIONS = ['client_raison_sociale', 'client_email', 'client_tel', 'client_country', 'client_address', 'client_address_siege', 'client_tva'];

    private static $sync_scheduled = false;

    public static function register()
    {
        add_action('admin_post_ccrgpd_save_seopress', [__CLASS__, 'handle_save']);
        foreach (self::SOURCE_OPTIONS as $option) {
            add_action('update_option_' . $option, [__CLASS__, 'schedule_sync'], 10, 0);
            add_action('add_option_' . $option, [__CLASS__, 'schedule_sync'], 10, 0);
        }
    }

    // ==================== ÉTAT ====================

    public static function is_active()
    {
        return defined('SEOPRESS_VERSION');
    }

    public static function is_compatible()
    {
        return self::is_active() && version_compare(SEOPRESS_VERSION, self::MIN_VERSION, '>=');
    }

    public static function settings()
    {
        $s = get_option(self::SETTINGS, []);
        $s = is_array($s) ? $s : [];
        return wp_parse_args($s, ['sync' => true, 'last_sync' => 0]);
    }

    public static function is_sync_enabled()
    {
        return (bool) self::settings()['sync'];
    }

    /** Module « Réseaux sociaux » de SEOPress actif (condition d'affichage du Knowledge Graph) */
    public static function is_social_module_on()
    {
        $toggle = get_option('seopress_toggle');
        return is_array($toggle) && isset($toggle['toggle-social']) && (string) $toggle['toggle-social'] === '1';
    }

    public static function current()
    {
        $opts = get_option(self::OPTION);
        return is_array($opts) ? $opts : [];
    }

    // ==================== VALEURS ISSUES DU PLUGIN ====================

    /**
     * Valeurs d'identité calculées depuis les onglets Coordonnées et Juridique.
     * Les clés d'adresse sont absentes si l'adresse n'a pas pu être découpée.
     */
    public static function identity_values()
    {
        $country = get_option('client_country', 'FR') ?: 'FR';
        $values = [
            'seopress_social_knowledge_legal_name' => (string) get_option('client_raison_sociale', ''),
            'seopress_social_knowledge_phone' => self::phone_international(get_option('client_tel', ''), $country),
            'seopress_social_knowledge_email' => (string) get_option('client_email', ''),
            'seopress_social_knowledge_tax_id' => (string) get_option('client_tva', ''),
        ];

        $address = self::parse_address(get_option('client_address') ?: get_option('client_address_siege'));
        if ($address) {
            $values['seopress_social_knowledge_street'] = $address['street'];
            $values['seopress_social_knowledge_postal_code'] = $address['postal_code'];
            $values['seopress_social_knowledge_locality'] = $address['locality'];
            $values['seopress_social_knowledge_country'] = $country;
        }

        return $values;
    }

    /**
     * Découpe une adresse saisie en texte libre (« 28 rue Victor Hugo\n40400 Tartas »)
     * Codes postaux à 4 ou 5 chiffres (FR, BE, CH, LU, DE...). Retourne null si non reconnue.
     */
    public static function parse_address($text)
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
        if (!$lines) {
            return null;
        }
        // Mention du pays en dernière ligne, ignorée (le pays vient du réglage du téléphone)
        if (count($lines) > 1 && preg_match('/^(france|belgique|suisse|luxembourg|allemagne|espagne|italie|royaume-uni)$/iu', end($lines))) {
            array_pop($lines);
        }
        $one = implode(', ', $lines);

        if (!preg_match('/^(?<street>.+?)[\s,]+(?<cp>\d{4,5})\s+(?<city>[^\d,][^,]*?)\s*$/u', $one, $m)) {
            return null;
        }

        return [
            'street' => trim($m['street'], " ,"),
            'postal_code' => $m['cp'],
            'locality' => trim($m['city']),
        ];
    }

    /** Téléphone au format international (+33558734157) */
    public static function phone_international($tel, $country = 'FR')
    {
        $tel = trim((string) $tel);
        if ($tel === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', $tel);
        if (strpos($tel, '+') === 0) {
            return '+' . $digits;
        }
        if (strpos($digits, '00') === 0) {
            return '+' . substr($digits, 2);
        }
        $code = CCRGPD_Constants::COUNTRIES[$country]['code'] ?? '+33';
        return $code . preg_replace('/^0/', '', $digits);
    }

    /** Taille minimale d'un logo d'organisation selon Google */
    public const LOGO_MIN = 112;

    /**
     * Icône carrée du site : icône WordPress, sinon manifeste de Favicon by RealFaviconGenerator
     *
     * @return array|null ['url' => string, 'w' => int, 'h' => int]
     */
    public static function square_icon()
    {
        if (function_exists('has_site_icon') && has_site_icon()) {
            $url = get_site_icon_url(512);
            $size = self::image_size($url);
            return ['url' => $url, 'w' => $size[0] ?? 512, 'h' => $size[1] ?? 512];
        }

        // RealFaviconGenerator : wp-content/uploads/fbrfg/site.webmanifest (icône la plus grande)
        $uploads = wp_upload_dir(null, false);
        $manifest = trailingslashit($uploads['basedir']) . 'fbrfg/site.webmanifest';
        if (is_readable($manifest)) {
            $json = json_decode((string) file_get_contents($manifest), true);
            $best = null;
            foreach ((array) ($json['icons'] ?? []) as $icon) {
                $w = (int) explode('x', (string) ($icon['sizes'] ?? '0x0'))[0];
                if (!empty($icon['src']) && (!$best || $w > $best['w'])) {
                    $best = ['src' => $icon['src'], 'w' => $w];
                }
            }
            if ($best) {
                $url = strpos($best['src'], 'http') === 0 ? $best['src'] : home_url('/' . ltrim($best['src'], '/'));
                $size = self::image_size($url);
                return ['url' => $url, 'w' => $size[0] ?? $best['w'], 'h' => $size[1] ?? $best['w']];
            }
        }
        return null;
    }

    /** Logo personnalisé du thème */
    public static function theme_logo()
    {
        $id = (int) get_theme_mod('custom_logo');
        $url = $id ? wp_get_attachment_image_url($id, 'full') : '';
        if (!$url) {
            return null;
        }
        $size = self::image_size($url);
        return ['url' => $url, 'w' => $size[0] ?? 0, 'h' => $size[1] ?? 0];
    }

    /** Dimensions d'une image hébergée sur le site (null si externe ou illisible) */
    public static function image_size($url)
    {
        $url = strtok((string) $url, '?');
        $uploads = wp_upload_dir(null, false);
        $path = null;
        if ($url && strpos($url, $uploads['baseurl']) === 0) {
            $path = $uploads['basedir'] . substr($url, strlen($uploads['baseurl']));
        } elseif ($url && strpos($url, home_url('/')) === 0) {
            $path = ABSPATH . ltrim(substr($url, strlen(home_url('/'))), '/');
        }
        if (!$path || !is_readable($path)) {
            return null;
        }
        $size = @getimagesize($path);
        return $size ? [(int) $size[0], (int) $size[1]] : null;
    }

    // ==================== ÉCRITURE ====================

    /**
     * Écrit des valeurs dans les réglages SEOPress sans toucher aux autres clés
     */
    public static function write(array $values)
    {
        if (!self::is_compatible()) {
            return false;
        }

        $opts = self::current();
        foreach ($values as $key => $value) {
            $opts[$key] = $value;
        }

        if (function_exists('seopress_sanitize_options_fields')) {
            $opts = seopress_sanitize_options_fields($opts);
        }

        update_option(self::OPTION, $opts);
        do_action('seopress_social_settings_updated', $opts);

        $settings = self::settings();
        $settings['last_sync'] = time();
        update_option(self::SETTINGS, $settings, false);

        self::purge_front_page();
        return true;
    }

    /** Synchronisation des champs d'identité (si activée) */
    public static function sync_identity()
    {
        if (!self::is_compatible() || !self::is_sync_enabled()) {
            return false;
        }
        return self::write(self::identity_values());
    }

    /** Plusieurs options enregistrées dans la même requête : une seule synchronisation, en fin de requête */
    public static function schedule_sync()
    {
        if (self::$sync_scheduled || !self::is_compatible() || !self::is_sync_enabled()) {
            return;
        }
        self::$sync_scheduled = true;
        add_action('shutdown', [__CLASS__, 'sync_identity']);
    }

    /** Le Knowledge Graph n'est imprimé que sur l'accueil : purge W3TC de cette page */
    private static function purge_front_page()
    {
        if (function_exists('w3tc_flush_url')) {
            w3tc_flush_url(home_url('/'));
        }
        $front = (int) get_option('page_on_front');
        if ($front && function_exists('w3tc_flush_post')) {
            w3tc_flush_post($front);
        }
    }

    /** Enregistrement de l'onglet SEO (admin-post.php) */
    public static function handle_save()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Permission refusée');
        }
        check_admin_referer('ccrgpd_save_seopress');

        $back = admin_url('admin.php?page=' . CCRGPD_Constants::MENU_SLUG);
        if (!self::is_compatible()) {
            wp_safe_redirect(add_query_arg('ccrgpd_seo', 'incompatible', $back) . '#tab-seo');
            exit;
        }

        $post = wp_unslash($_POST['seo'] ?? []);
        $post = is_array($post) ? $post : [];

        // Réglage du plugin : synchronisation
        $settings = self::settings();
        $settings['sync'] = !empty($_POST['ccrgpd_sync']);
        update_option(self::SETTINGS, $settings, false);

        $values = [];

        $type = (string) ($post['seopress_social_knowledge_type'] ?? '');
        if (isset(self::TYPES[$type])) {
            $values['seopress_social_knowledge_type'] = $type;
        }

        foreach (array_keys(self::TEXT_FIELDS) as $key) {
            if (array_key_exists($key, $post)) {
                $values[$key] = sanitize_text_field($post[$key]);
            }
        }
        if (isset($values['seopress_social_knowledge_founding_date']) && $values['seopress_social_knowledge_founding_date'] !== ''
            && !preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $values['seopress_social_knowledge_founding_date'])) {
            unset($values['seopress_social_knowledge_founding_date']);
        }
        if (isset($values['seopress_social_knowledge_employees']) && $values['seopress_social_knowledge_employees'] !== '') {
            $values['seopress_social_knowledge_employees'] = (string) absint($values['seopress_social_knowledge_employees']);
        }
        if (array_key_exists('seopress_social_knowledge_desc', $post)) {
            $values['seopress_social_knowledge_desc'] = sanitize_textarea_field($post['seopress_social_knowledge_desc']);
        }
        if (array_key_exists('seopress_social_knowledge_img', $post)) {
            $values['seopress_social_knowledge_img'] = esc_url_raw(trim($post['seopress_social_knowledge_img']));
        }

        foreach (array_keys(self::SOCIAL_FIELDS) as $key) {
            if (!array_key_exists($key, $post)) {
                continue;
            }
            $values[$key] = $key === 'seopress_social_accounts_twitter'
                ? sanitize_text_field($post[$key])
                : esc_url_raw(trim($post[$key]));
        }
        if (array_key_exists('seopress_social_accounts_extra', $post)) {
            $urls = array_filter(array_map(function ($u) { return esc_url_raw(trim($u)); }, preg_split('/\r\n|\r|\n/', (string) $post['seopress_social_accounts_extra'])));
            $values['seopress_social_accounts_extra'] = implode("\n", $urls);
        }

        // Identité : depuis les Coordonnées si synchronisé, sinon depuis le formulaire
        if ($settings['sync']) {
            $values = array_merge($values, self::identity_values());
        } else {
            foreach (array_keys(self::IDENTITY_FIELDS) as $key) {
                if (array_key_exists($key, $post)) {
                    $values[$key] = $key === 'seopress_social_knowledge_email'
                        ? sanitize_email($post[$key])
                        : sanitize_text_field($post[$key]);
                }
            }
        }

        self::write($values);
        wp_safe_redirect(add_query_arg('ccrgpd_seo', 'saved', $back) . '#tab-seo');
        exit;
    }

    // ==================== CONTRÔLES ====================

    /**
     * Points d'attention affichés dans l'onglet SEO
     *
     * @return array [['level' => error|warning|info, 'message' => string]]
     */
    public static function checks()
    {
        $checks = [];
        if (!self::is_compatible()) {
            return $checks;
        }
        $opts = self::current();
        $type = $opts['seopress_social_knowledge_type'] ?? '';

        if (!self::is_social_module_on()) {
            $checks[] = ['level' => 'error', 'message' => 'Le module « Réseaux sociaux » de SEOPress est désactivé : le Knowledge Graph n\'est pas affiché. À activer dans SEOPress > Tableau de bord.'];
        }
        if ($type === '' || $type === 'none') {
            $checks[] = ['level' => 'warning', 'message' => 'Aucun type d\'entité choisi : SEOPress n\'imprime pas le Knowledge Graph.'];
        }
        if (!get_option('client_address') && !get_option('client_address_siege')) {
            $checks[] = ['level' => 'warning', 'message' => 'Aucune adresse saisie dans l\'onglet Coordonnées.'];
        } elseif (!self::parse_address(get_option('client_address') ?: get_option('client_address_siege'))) {
            $checks[] = ['level' => 'warning', 'message' => 'Adresse non reconnue (attendu : rue sur une ligne, puis code postal et ville) : les champs d\'adresse ne sont pas synchronisés.'];
        }
        if ($type === 'LocalBusiness') {
            foreach (['seopress_social_knowledge_street', 'seopress_social_knowledge_postal_code', 'seopress_social_knowledge_locality', 'seopress_social_knowledge_country'] as $key) {
                if (empty($opts[$key])) {
                    $checks[] = ['level' => 'warning', 'message' => 'LocalBusiness exige une adresse complète : rue, code postal, ville et pays.'];
                    break;
                }
            }
        }
        if (empty($opts['seopress_social_knowledge_img'])) {
            $checks[] = ['level' => 'info', 'message' => 'Aucun logo renseigné.'];
        } else {
            $size = self::image_size($opts['seopress_social_knowledge_img']);
            if ($size && min($size) < self::LOGO_MIN) {
                $checks[] = ['level' => 'warning', 'message' => sprintf('Logo de %d x %d px : Google demande au moins %d x %d px pour le logo d\'une organisation. Préférez l\'icône carrée du site.', $size[0], $size[1], self::LOGO_MIN, self::LOGO_MIN)];
            }
        }
        if (empty($opts['seopress_social_knowledge_desc'])) {
            $checks[] = ['level' => 'info', 'message' => 'Description vide : SEOPress affiche alors le nom du site comme description.'];
        }

        // Cohérence avec l'onglet Juridique (cas réel : SIREN d'une SARL importé sur le site d'une association)
        $forme = (string) get_option('client_forme_juridique', '');
        $is_asso = $forme === 'Association';
        if ($type === 'NGO' && $forme !== '' && !$is_asso) {
            $checks[] = ['level' => 'warning', 'message' => sprintf('Type « Association (NGO) » alors que la forme juridique est « %s » : vérifiez l\'onglet Juridique.', $forme)];
        }
        if ($is_asso && in_array($type, ['Corporation', 'OnlineStore', 'OnlineBusiness'], true)) {
            $checks[] = ['level' => 'warning', 'message' => 'Forme juridique « Association » : le type NGO est plus approprié.'];
        }
        if ($is_asso && get_option('client_tva')) {
            $checks[] = ['level' => 'warning', 'message' => 'N° de TVA renseigné pour une association : à vérifier, la plupart ne sont pas assujetties. Il est publié dans les mentions légales et le Knowledge Graph.'];
        }
        return $checks;
    }
}
