<?php
defined('ABSPATH') || exit;

class CCRGPD_Plugin
{
    /** Options dont la valeur est affichée sur le site (shortcodes, pied de page, pages légales) */
    private const DISPLAYED_OPTIONS = [
        'client_raison_sociale', 'client_email', 'client_tel', 'client_country', 'client_address',
        'client_address_siege', 'client_siret', 'client_siren', 'client_rcs', 'client_capital',
        'client_tva', 'client_responsable', 'client_forme_juridique', 'client_forme_juridique_autre',
        'matrys_name', 'matrys_url', 'matrys_address', 'matrys_tel', 'matrys_country',
        'rgpd_settings',
    ];

    private static $flush_scheduled = false;

    public static function init()
    {
        CCRGPD_Shortcodes::register();
        CCRGPD_Admin::register();
        CCRGPD_SEOPress::register();

        foreach (self::DISPLAYED_OPTIONS as $option) {
            add_action('update_option_' . $option, [__CLASS__, 'schedule_page_cache_flush'], 10, 0);
            add_action('add_option_' . $option, [__CLASS__, 'schedule_page_cache_flush'], 10, 0);
        }

        // MCP (MATRYS MCP Bridge, ability matrys/option) : client_* et ccrgpd_* sont déjà
        // sur la liste blanche du bridge ; on y ajoute les réglages de l'onglet RGPD.
        add_filter('matrys_bridge_option_whitelist', [__CLASS__, 'bridge_whitelist']);

        // Le nettoyage de rgpd_settings n'est branché par register_setting() que sur admin_init :
        // on l'applique aussi aux écritures hors écran de réglages (MCP, WP-CLI, code).
        add_filter('sanitize_option_rgpd_settings', [CCRGPD_Admin::class, 'sanitize_rgpd']);
    }

    /** Options du plugin rendues lisibles et modifiables par le bridge MCP MATRYS */
    public static function bridge_whitelist($list)
    {
        $list = (array) $list;
        if (!in_array('rgpd_settings', $list, true)) {
            $list[] = 'rgpd_settings';
        }
        return $list;
    }

    /** Une seule purge en fin de requête, même si plusieurs options changent */
    public static function schedule_page_cache_flush()
    {
        if (self::$flush_scheduled) return;
        self::$flush_scheduled = true;
        add_action('shutdown', [__CLASS__, 'flush_page_cache']);
    }

    /**
     * Les coordonnées sont affichées sur toutes les pages (pied de page, pages légales) :
     * purge du cache de page uniquement (pas d'OPcache, pas de cache objet).
     */
    public static function flush_page_cache()
    {
        if (function_exists('w3tc_flush_posts')) {
            w3tc_flush_posts();
        } elseif (function_exists('w3tc_pgcache_flush')) {
            w3tc_pgcache_flush();
        }
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
    }

    public static function activate()
    {
        foreach (CCRGPD_Constants::DEFAULT_OPTIONS as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
        
        delete_transient('matrys_ghupd_' . md5('client-coordonnees-rgpd'));
        delete_site_transient('update_plugins');
    }
}
