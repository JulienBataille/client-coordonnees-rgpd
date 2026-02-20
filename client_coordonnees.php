<?php
/**
 * Plugin Name: Coordonnées & RGPD - By MATRYS
 * Plugin URI: https://github.com/JulienBataille/client-coordonnees-rgpd
 * Description: Coordonnées client/agence + mentions légales et politique de confidentialité conformes LCEN/RGPD.
 * Version: 4.2.0
 * Author: MATRYS - Julien Bataillé
 * Author URI: https://matrys.fr
 * Text Domain: client-coordonnees
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * GitHub Plugin URI: JulienBataille/client-coordonnees-rgpd
 */

defined('ABSPATH') || exit;

define('CCRGPD_VERSION', '4.2.0');
define('CCRGPD_PATH', plugin_dir_path(__FILE__));
define('CCRGPD_URL', plugin_dir_url(__FILE__));

require_once CCRGPD_PATH . 'includes/constants.php';
require_once CCRGPD_PATH . 'includes/class-matrys-github-updater.php';
require_once CCRGPD_PATH . 'includes/class-api-entreprises.php';
require_once CCRGPD_PATH . 'includes/class-form-analyzer.php';
require_once CCRGPD_PATH . 'includes/class-shortcodes.php';
require_once CCRGPD_PATH . 'includes/class-admin.php';
require_once CCRGPD_PATH . 'includes/class-plugin.php';

// GitHub Updater
if (class_exists('MATRYS_GitHub_Updater')) {
    new MATRYS_GitHub_Updater(__FILE__, 'JulienBataille', 'client-coordonnees-rgpd');
}

// Init
add_action('plugins_loaded', ['CCRGPD_Plugin', 'init']);

// Activation
register_activation_hook(__FILE__, ['CCRGPD_Plugin', 'activate']);
