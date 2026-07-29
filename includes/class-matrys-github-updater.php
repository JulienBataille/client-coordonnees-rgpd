<?php
/**
 * Updater GitHub pour les plugins MATRYS.
 *
 * Ne passe jamais par api.github.com : le quota anonyme est de 60 requetes/heure
 * et par IP, donc partage par tous les sites d'une meme VM. On utilise a la place
 * la redirection 302 de /releases/latest pour connaitre le dernier tag, puis
 * l'archive source du tag. Ces deux routes ne sont pas soumises au quota.
 *
 * @since 4.1.6 Abandon de l'API GitHub.
 */
if (!class_exists('MATRYS_GitHub_Updater')) {
    class MATRYS_GitHub_Updater
    {
        /** Duree de cache d'une reponse valide (6 h). */
        const TTL_OK = 21600;

        /** Duree de cache d'un echec (1 h), pour eviter de marteler GitHub. */
        const TTL_FAIL = 3600;

        private $file;
        private $basename;
        private $slug;
        private $username;
        private $repository;
        private $latest;

        public function __construct($file, $user, $repo)
        {
            $this->file       = $file;
            $this->username   = $user;
            $this->repository = $repo;
            $this->basename   = plugin_basename($file);
            $this->slug       = dirname($this->basename);

            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            add_filter('upgrader_source_selection', [$this, 'fix_source_dir']);
        }

        /**
         * Version installee. get_plugin_data() vit dans wp-admin/includes/plugin.php,
         * qui n'est pas charge quand le controle de mise a jour part depuis le cron.
         */
        private function plugin_header()
        {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            return get_plugin_data($this->file, false, false);
        }

        /**
         * Base des URLs publiques du depot.
         */
        private function repo_url()
        {
            return 'https://github.com/' . $this->username . '/' . $this->repository;
        }

        /**
         * Derniere release publiee, via la redirection de /releases/latest.
         *
         * @return array|null ['tag' => 'v4.1.6', 'version' => '4.1.6', 'package' => '...zip']
         */
        private function get_latest()
        {
            if (null !== $this->latest) {
                return $this->latest ?: null;
            }

            $key    = 'matrys_ghupd_' . md5($this->username . '/' . $this->repository);
            $cached = get_transient($key);

            if (false !== $cached) {
                $this->latest = is_array($cached) ? $cached : false;
                return $this->latest ?: null;
            }

            $res = wp_remote_head($this->repo_url() . '/releases/latest', [
                'redirection' => 0,
                'timeout'     => 10,
            ]);

            $loc = is_wp_error($res) ? '' : wp_remote_retrieve_header($res, 'location');

            // Le tag est lu dans le Location, jamais reconstruit : certains tags
            // du depot n'ont pas de prefixe "v" (ex. 4.1.4) et une URL fabriquee
            // a partir du numero de version renverrait un 404.
            if (!$loc || !preg_match('#/releases/tag/(.+)$#', $loc, $m)) {
                set_transient($key, 'fail', self::TTL_FAIL);
                $this->latest = false;
                return null;
            }

            $tag = urldecode($m[1]);

            $this->latest = [
                'tag'     => $tag,
                'version' => ltrim($tag, 'v'),
                'package' => $this->repo_url() . '/archive/refs/tags/' . rawurlencode($tag) . '.zip',
            ];

            set_transient($key, $this->latest, self::TTL_OK);

            return $this->latest;
        }

        /**
         * Signale la mise a jour disponible a WordPress.
         */
        public function check_update($transient)
        {
            if (empty($transient->checked)) {
                return $transient;
            }

            $latest = $this->get_latest();
            if (!$latest) {
                return $transient;
            }

            $plugin_data = $this->plugin_header();
            $current     = $plugin_data['Version'];

            if (version_compare($latest['version'], $current, '>')) {
                $transient->response[$this->basename] = (object) [
                    'slug'        => $this->slug,
                    'plugin'      => $this->basename,
                    'new_version' => $latest['version'],
                    'url'         => $this->repo_url(),
                    'package'     => $latest['package'],
                ];
            } else {
                unset($transient->response[$this->basename]);

                if (!isset($transient->no_update) || !is_array($transient->no_update)) {
                    $transient->no_update = [];
                }
                $transient->no_update[$this->basename] = (object) [
                    'slug'        => $this->slug,
                    'plugin'      => $this->basename,
                    'new_version' => $current,
                    'url'         => $this->repo_url(),
                    'package'     => '',
                ];
            }

            return $transient;
        }

        /**
         * Alimente la fiche affichee par WordPress.
         */
        public function plugin_info($result, $action, $args)
        {
            if ('plugin_information' !== $action) {
                return $result;
            }
            if (!isset($args->slug) || $args->slug !== $this->slug) {
                return $result;
            }

            $latest      = $this->get_latest();
            $plugin_data = $this->plugin_header();

            return (object) [
                'name'          => $plugin_data['Name'],
                'slug'          => $this->slug,
                'version'       => $latest ? $latest['version'] : $plugin_data['Version'],
                'author'        => $plugin_data['Author'],
                'homepage'      => $plugin_data['PluginURI'],
                'download_link' => $latest ? $latest['package'] : '',
                'sections'      => [
                    'description' => $plugin_data['Description'],
                    'changelog'   => sprintf(
                        '<p><a href="%s" target="_blank" rel="noopener">Notes de version sur GitHub</a></p>',
                        esc_url($this->repo_url() . '/releases')
                    ),
                ],
            ];
        }

        /**
         * L'archive source de GitHub se decompresse dans un dossier "depot-tag"
         * (ex. client-coordonnees-rgpd-4.1.6). On le renomme avant installation,
         * sinon WordPress cree un second dossier de plugin a cote du premier.
         */
        public function fix_source_dir($source)
        {
            global $wp_filesystem;

            $dir = basename(untrailingslashit($source));

            if (0 !== strpos($dir, $this->repository . '-')) {
                return $source;
            }
            if (!$wp_filesystem->exists(trailingslashit($source) . basename($this->file))) {
                return $source;
            }

            $target = trailingslashit(dirname(untrailingslashit($source))) . $this->repository;

            if (untrailingslashit($source) === untrailingslashit($target)) {
                return $source;
            }
            if (!$wp_filesystem->move(untrailingslashit($source), untrailingslashit($target))) {
                return new WP_Error(
                    'matrys_ghupd_rename',
                    sprintf('Renommage impossible : %s vers %s', $dir, $this->repository)
                );
            }

            return trailingslashit($target);
        }
    }
}
