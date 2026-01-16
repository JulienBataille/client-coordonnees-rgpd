<?php
/**
 * MATRYS GitHub Plugin Updater
 * Permet la mise à jour automatique des plugins via GitHub Releases
 * 
 * @version 1.0.0
 * @author MATRYS - Julien Bataillé
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('MATRYS_GitHub_Updater')) {
    
    class MATRYS_GitHub_Updater {
        
        private $file;
        private $plugin;
        private $basename;
        private $github_user;
        private $github_repo;
        private $github_token;
        private $github_response;
        private $active;
        
        public function __construct($file, $github_user, $github_repo, $token = null) {
            $this->file = $file;
            $this->github_user = $github_user;
            $this->github_repo = $github_repo;
            $this->github_token = $token;
            
            add_action('admin_init', [$this, 'set_plugin_properties']);
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
            add_filter('plugin_row_meta', [$this, 'plugin_meta'], 10, 2);
        }
        
        public function set_plugin_properties() {
            $this->plugin = get_plugin_data($this->file);
            $this->basename = plugin_basename($this->file);
            $this->active = is_plugin_active($this->basename);
        }
        
        private function get_github_release() {
            if (!empty($this->github_response)) {
                return $this->github_response;
            }
            
            $cache_key = 'matrys_ghupd_' . md5($this->github_repo);
            $cached = get_transient($cache_key);
            
            if ($cached !== false) {
                $this->github_response = $cached;
                return $cached;
            }
            
            $url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
            
            $args = [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'MATRYS-WP-Updater/1.0'
                ]
            ];
            
            if ($this->github_token) {
                $args['headers']['Authorization'] = 'Bearer ' . $this->github_token;
            }
            
            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }
            
            $this->github_response = json_decode(wp_remote_retrieve_body($response));
            set_transient($cache_key, $this->github_response, 6 * HOUR_IN_SECONDS);
            
            return $this->github_response;
        }
        
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }
            
            // Récupère les infos directement (ne pas dépendre de admin_init)
            if (empty($this->plugin) || empty($this->basename)) {
                $this->plugin = get_plugin_data($this->file);
                $this->basename = plugin_basename($this->file);
                $this->active = is_plugin_active($this->basename);
            }
            
            $release = $this->get_github_release();
            
            if (!$release || !isset($release->tag_name)) {
                return $transient;
            }
            
            $remote_version = ltrim($release->tag_name, 'v');
            $local_version = $this->plugin['Version'];
            
            if (version_compare($remote_version, $local_version, '>')) {
                $package = $release->zipball_url;
                if (!empty($release->assets)) {
                    foreach ($release->assets as $asset) {
                        if (substr($asset->name, -4) === '.zip') {
                            $package = $asset->browser_download_url;
                            break;
                        }
                    }
                }
                
                if ($this->github_token && strpos($package, 'api.github.com') !== false) {
                    $package = add_query_arg('access_token', $this->github_token, $package);
                }
                
                $transient->response[$this->basename] = (object) [
                    'slug'         => dirname($this->basename),
                    'plugin'       => $this->basename,
                    'new_version'  => $remote_version,
                    'url'          => $this->plugin['PluginURI'] ?: "https://github.com/{$this->github_user}/{$this->github_repo}",
                    'package'      => $package,
                    'icons'        => [],
                    'banners'      => [],
                    'tested'       => get_bloginfo('version'),
                    'requires_php' => $this->plugin['RequiresPHP'] ?? '7.4',
                ];
            }
            
            return $transient;
        }
        
        public function plugin_info($result, $action, $args) {
            if ($action !== 'plugin_information') {
                return $result;
            }
            
            if (!isset($args->slug) || $args->slug !== dirname($this->basename)) {
                return $result;
            }
            
            $this->set_plugin_properties();
            $release = $this->get_github_release();
            
            if (!$release) {
                return $result;
            }
            
            $remote_version = ltrim($release->tag_name, 'v');
            
            return (object) [
                'name'          => $this->plugin['Name'],
                'slug'          => dirname($this->basename),
                'version'       => $remote_version,
                'author'        => $this->plugin['AuthorName'],
                'author_profile'=> $this->plugin['AuthorURI'],
                'homepage'      => $this->plugin['PluginURI'] ?: "https://github.com/{$this->github_user}/{$this->github_repo}",
                'requires'      => $this->plugin['RequiresWP'] ?? '5.0',
                'requires_php'  => $this->plugin['RequiresPHP'] ?? '7.4',
                'tested'        => get_bloginfo('version'),
                'downloaded'    => 0,
                'last_updated'  => $release->published_at ?? '',
                'sections'      => [
                    'description' => $this->plugin['Description'],
                    'changelog'   => $this->format_changelog($release->body ?? ''),
                ],
                'download_link' => $release->zipball_url,
            ];
        }
        
        private function format_changelog($body) {
            if (empty($body)) {
                return '<p>Aucune note de version disponible.</p>';
            }
            
            $html = esc_html($body);
            $html = nl2br($html);
            $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
            $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
            $html = preg_replace('/^- (.+)$/m', '• $1', $html);
            $html = preg_replace('/^## (.+)$/m', '<h4>$1</h4>', $html);
            
            return '<div class="changelog">' . $html . '</div>';
        }
        
        public function after_install($response, $hook_extra, $result) {
            global $wp_filesystem;
            
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->basename) {
                return $result;
            }
            
            $proper_folder = WP_PLUGIN_DIR . '/' . dirname($this->basename);
            
            if ($result['destination'] !== $proper_folder) {
                $wp_filesystem->move($result['destination'], $proper_folder);
                $result['destination'] = $proper_folder;
            }
            
            if ($this->active) {
                activate_plugin($this->basename);
            }
            
            return $result;
        }
        
        public function plugin_meta($links, $file) {
            if ($file !== $this->basename) {
                return $links;
            }
            
            $links[] = sprintf(
                '<a href="https://github.com/%s/%s/releases" target="_blank">Voir sur GitHub</a>',
                $this->github_user,
                $this->github_repo
            );
            
            return $links;
        }
    }
}
