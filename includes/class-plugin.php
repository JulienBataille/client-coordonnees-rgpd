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
        foreach (CCRGPD_Constants::DEFAULT_OPTIONS as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
        
        delete_transient('matrys_ghupd_' . md5('client-coordonnees-rgpd'));
        delete_site_transient('update_plugins');
    }
}
