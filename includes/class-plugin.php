<?php
defined('ABSPATH') || exit;

class CCRGPD_Plugin
{
    public static function init()
    {
        CCRGPD_Shortcodes::register();
        CCRGPD_Admin::register();
    }

    public static function activate()
    {
        foreach (CCRGPD_Constants::DEFAULT_OPTIONS as $k => $v) {
            if (get_option($k) === false) {
                update_option($k, $v);
            }
        }
        delete_transient('matrys_ghupd_' . md5('client-coordonnees-rgpd'));
        delete_site_transient('update_plugins');
    }
}
