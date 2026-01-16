<?php
if (!class_exists('MATRYS_GitHub_Updater')) {
    class MATRYS_GitHub_Updater
    {
        private $file;
        private $basename;
        private $username;
        private $repository;
        private $github_response;

        public function __construct($file, $user, $repo)
        {
            $this->file = $file;
            $this->username = $user;
            $this->repository = $repo;
            $this->basename = plugin_basename($file);
            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
        }

        private function get_repo_info()
        {
            if (!empty($this->github_response)) return;
            $key = 'matrys_ghupd_' . md5($this->repository);
            $cached = get_transient($key);
            if ($cached !== false) { $this->github_response = $cached; return; }
            $url = "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest";
            $response = wp_remote_get($url, ['headers' => ['Accept' => 'application/vnd.github.v3+json'], 'timeout' => 10]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $this->github_response = json_decode(wp_remote_retrieve_body($response));
                set_transient($key, $this->github_response, 6 * HOUR_IN_SECONDS);
            }
        }

        public function check_update($transient)
        {
            if (empty($transient->checked)) return $transient;
            $this->get_repo_info();
            if (empty($this->github_response->tag_name)) return $transient;
            $plugin_data = get_plugin_data($this->file);
            $current = $plugin_data['Version'];
            $latest = ltrim($this->github_response->tag_name, 'v');
            if (version_compare($latest, $current, '>')) {
                $package = $this->github_response->zipball_url ?? '';
                if (!empty($this->github_response->assets)) {
                    foreach ($this->github_response->assets as $asset) {
                        if (strpos($asset->name, '.zip') !== false) { $package = $asset->browser_download_url; break; }
                    }
                }
                $transient->response[$this->basename] = (object) [
                    'slug' => dirname($this->basename),
                    'plugin' => $this->basename,
                    'new_version' => $latest,
                    'url' => "https://github.com/{$this->username}/{$this->repository}",
                    'package' => $package,
                ];
            }
            return $transient;
        }

        public function plugin_info($result, $action, $args)
        {
            if ($action !== 'plugin_information') return $result;
            if (!isset($args->slug) || $args->slug !== dirname($this->basename)) return $result;
            $this->get_repo_info();
            if (empty($this->github_response)) return $result;
            $plugin_data = get_plugin_data($this->file);
            return (object) [
                'name' => $plugin_data['Name'],
                'slug' => dirname($this->basename),
                'version' => ltrim($this->github_response->tag_name, 'v'),
                'author' => $plugin_data['Author'],
                'homepage' => $plugin_data['PluginURI'],
                'sections' => ['description' => $plugin_data['Description'], 'changelog' => nl2br($this->github_response->body ?? '')],
                'download_link' => $this->github_response->zipball_url,
            ];
        }

        public function after_install($response, $hook_extra, $result)
        {
            global $wp_filesystem;
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->basename) return $result;
            $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->basename);
            $wp_filesystem->move($result['destination'], $plugin_folder);
            $result['destination'] = $plugin_folder;
            activate_plugin($this->basename);
            return $result;
        }
    }
}
