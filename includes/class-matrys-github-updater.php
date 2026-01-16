<?php
/**
 * MATRYS GitHub Plugin Updater
 * Mise à jour automatique via GitHub Releases
 * 
 * @version 1.1.0
 * @author MATRYS - Julien Bataillé
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('MATRYS_GitHub_Updater')) {
    
    class MATRYS_GitHub_Updater {
        
        private $file;
        private $basename;
        private $github_user;
        private $github_repo;
        private $github_token;
        
        public function __construct($file, $github_user, $github_repo, $token = null) {
            $this->file = $file;
            $this->basename = plugin_basename($file);
            $this->github_user = $github_user;
            $this->github_repo = $github_repo;
            $this->github_token = $token;
            
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
            add_filter('plugin_row_meta', [$this, 'plugin_meta'], 10, 2);
        }
        
        private function get_plugin_data() {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            return get_plugin_data($this->file);
        }
        
        private function get_github_release() {
            $cache_key = 'matrys_ghupd_' . md5($this->github_repo);
            $cached = get_transient($cache_key);
            
            if ($cached !== false) {
                return $cached;
            }
            
            $url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
            
            $args = [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'MATRYS-WP-Updater/1.1'
                ]
            ];
            
            if ($this->github_token) {
                $args['headers']['Authorization'] = 'Bearer ' . $this->github_token;
            }
            
            $response = wp_remote_get($url, $args);
            
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }
            
            $release = json_decode(wp_remote_retrieve_body($response));
            
            if ($release && isset($release->tag_name)) {
                set_transient($cache_key, $release, 6 * HOUR_IN_SECONDS);
            }
            
            return $release;
        }
        
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }
            
            if (!isset($transient->checked[$this->basename])) {
                return $transient;
            }
            
            $release = $this->get_github_release();
            
            if (!$release || !isset($release->tag_name)) {
                return $transient;
            }
            
            $remote_version = ltrim($release->tag_name, 'v');
            $local_version = $transient->checked[$this->basename];
            
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
                
                $plugin_data = $this->get_plugin_data();
                
                $transient->response[$this->basename] = (object) [
                    'slug'         => dirname($this->basename),
                    'plugin'       => $this->basename,
                    'new_version'  => $remote_version,
                    'url'          => $plugin_data['PluginURI'] ?: "https://github.com/{$this->github_user}/{$this->github_repo}",
                    'package'      => $package,
                    'icons'        => [],
                    'banners'      => [],
                    'tested'       => get_bloginfo('version'),
                    'requires_php' => $plugin_data['RequiresPHP'] ?? '7.4',
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
            
            $release = $this->get_github_release();
            
            if (!$release) {
                return $result;
            }
            
            $plugin_data = $this->get_plugin_data();
            $remote_version = ltrim($release->tag_name, 'v');
            
            return (object) [
                'name'          => $plugin_data['Name'],
                'slug'          => dirname($this->basename),
                'version'       => $remote_version,
                'author'        => $plugin_data['AuthorName'],
                'author_profile'=> $plugin_data['AuthorURI'],
                'homepage'      => $plugin_data['PluginURI'] ?: "https://github.com/{$this->github_user}/{$this->github_repo}",
                'requires'      => $plugin_data['RequiresWP'] ?? '5.0',
                'requires_php'  => $plugin_data['RequiresPHP'] ?? '7.4',
                'tested'        => get_bloginfo('version'),
                'downloaded'    => 0,
                'last_updated'  => $release->published_at ?? '',
                'sections'      => [
                    'description' => $plugin_data['Description'],
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
            
            if (isset($result['destination']) && $result['destination'] !== $proper_folder) {
                $wp_filesystem->move($result['destination'], $proper_folder);
                $result['destination'] = $proper_folder;
            }
            
            if (is_plugin_active($this->basename)) {
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
