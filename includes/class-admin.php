<?php
defined('ABSPATH') || exit;

class CCRGPD_Admin
{
    public static function register()
    {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_ajax_ccrgpd_search_siret', [__CLASS__, 'ajax_search_siret']);
        add_action('wp_ajax_ccrgpd_get_current', [__CLASS__, 'ajax_get_current']);
    }

    public static function add_menu()
    {
        add_menu_page('Coordonnées & RGPD', 'Coordonnées & RGPD', 'manage_options', CCRGPD_Constants::MENU_SLUG, [__CLASS__, 'render_page'], 'dashicons-id', 20);
    }

    public static function register_settings()
    {
        $fields = [
            'client_raison_sociale','client_email','client_tel','client_country','client_address',
            'client_address_siege','client_siret','client_siren','client_rcs','client_capital',
            'client_tva','client_responsable','client_forme_juridique','client_forme_juridique_autre',
            'matrys_name','matrys_url','matrys_address','matrys_tel','matrys_country',
        ];
        foreach ($fields as $f) {
            register_setting(CCRGPD_Constants::OPTION_GROUP, $f);
        }
        register_setting(CCRGPD_Constants::OPTION_GROUP_RGPD, 'rgpd_settings', [__CLASS__, 'sanitize_rgpd']);
    }

    public static function sanitize_rgpd($input)
    {
        $s = ['forms' => []];
        if (!empty($input['forms']) && is_array($input['forms'])) {
            foreach ($input['forms'] as $id => $c) {
                $s['forms'][$id] = [
                    'enabled' => !empty($c['enabled']),
                    'name_override' => sanitize_text_field($c['name_override'] ?? ''),
                    'purpose' => sanitize_text_field($c['purpose'] ?? ''),
                    'legal_basis' => sanitize_text_field($c['legal_basis'] ?? 'consent'),
                    'retention' => sanitize_text_field($c['retention'] ?? '3_years'),
                    'recipients' => sanitize_text_field($c['recipients'] ?? ''),
                    'third_party' => sanitize_text_field($c['third_party'] ?? ''),
                ];
            }
        }
        return $s;
    }

    public static function enqueue_assets($hook)
    {
        if ($hook !== 'toplevel_page_' . CCRGPD_Constants::MENU_SLUG) return;
        wp_enqueue_style('ccrgpd-admin', CCRGPD_URL . 'assets/css/admin.css', [], CCRGPD_VERSION);
        wp_enqueue_script('ccrgpd-admin', CCRGPD_URL . 'assets/js/admin.js', ['jquery'], CCRGPD_VERSION, true);
        wp_localize_script('ccrgpd-admin', 'ccrgpd', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ccrgpd_nonce'),
        ]);
    }

    public static function ajax_search_siret()
    {
        check_ajax_referer('ccrgpd_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission refusée');
        $result = CCRGPD_API_Entreprises::search(sanitize_text_field($_POST['siret'] ?? ''));
        is_wp_error($result) ? wp_send_json_error($result->get_error_message()) : wp_send_json_success($result);
    }

    public static function ajax_get_current()
    {
        check_ajax_referer('ccrgpd_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Permission refusée');
        wp_send_json_success([
            'client_raison_sociale' => get_option('client_raison_sociale', ''),
            'client_address_siege' => get_option('client_address_siege', ''),
            'client_siret' => get_option('client_siret', ''),
            'client_siren' => get_option('client_siren', ''),
            'client_tva' => get_option('client_tva', ''),
            'client_rcs' => get_option('client_rcs', ''),
            'client_responsable' => get_option('client_responsable', ''),
            'client_capital' => get_option('client_capital', ''),
            'client_forme_juridique' => get_option('client_forme_juridique', ''),
        ]);
    }

    public static function render_page()
    {
        $rgpd = get_option('rgpd_settings', []);
        $forms = CCRGPD_Shortcodes::get_forms();
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-id"></span> Coordonnées & RGPD</h1>
            <nav class="nav-tab-wrapper">
                <a href="#tab-coordonnees" class="nav-tab nav-tab-active">👤 Coordonnées</a>
                <a href="#tab-juridique" class="nav-tab">⚖️ Juridique</a>
                <a href="#tab-agence" class="nav-tab">🏢 Agence</a>
                <a href="#tab-rgpd" class="nav-tab">🛡️ RGPD</a>
                <a href="#tab-shortcodes" class="nav-tab">🔧 Shortcodes</a>
            </nav>
            
            <div id="tab-coordonnees" class="tab-content active">
                <form method="post" action="options.php">
                    <?php settings_fields(CCRGPD_Constants::OPTION_GROUP); ?>
                    <div class="ccrgpd-box"><h2>Coordonnées de contact</h2>
                    <table class="form-table">
                        <tr><th>Email *</th><td><input type="email" name="client_email" value="<?php echo esc_attr(get_option('client_email')); ?>" class="regular-text" required></td></tr>
                        <tr><th>Téléphone</th><td>
                            <select name="client_country" style="width:140px"><?php foreach (CCRGPD_Constants::COUNTRIES as $c => $d) echo '<option value="'.$c.'" '.selected(get_option('client_country','FR'),$c,false).'>'.$d['name'].' ('.$d['code'].')</option>'; ?></select>
                            <input type="tel" name="client_tel" value="<?php echo esc_attr(get_option('client_tel')); ?>" class="regular-text">
                        </td></tr>
                        <tr><th>Adresse *</th><td><textarea name="client_address" rows="3" class="large-text" required><?php echo esc_textarea(get_option('client_address')); ?></textarea></td></tr>
                    </table></div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-juridique" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields(CCRGPD_Constants::OPTION_GROUP); ?>
                    <div class="ccrgpd-box"><h2>🔍 Recherche SIRET</h2>
                    <div class="siret-search">
                        <input type="text" id="siret_search" placeholder="Ex: 98562012900017" maxlength="20">
                        <button type="button" id="btn-search-siret" class="button button-secondary">🔍 Rechercher</button>
                        <span class="spinner" id="siret-spinner"></span>
                    </div>
                    <div id="siret-result"></div></div>
                    
                    <div class="ccrgpd-box"><h2>Informations légales</h2>
                    <table class="form-table">
                        <tr><th>Raison sociale *</th><td><input type="text" name="client_raison_sociale" value="<?php echo esc_attr(get_option('client_raison_sociale')); ?>" class="regular-text"></td></tr>
                        <tr><th>Adresse siège</th><td><textarea name="client_address_siege" rows="3" class="large-text"><?php echo esc_textarea(get_option('client_address_siege')); ?></textarea></td></tr>
                        <tr><th>Responsable *</th><td><input type="text" name="client_responsable" value="<?php echo esc_attr(get_option('client_responsable')); ?>" class="regular-text"></td></tr>
                        <tr><th>Forme juridique</th><td>
                            <select name="client_forme_juridique" id="client_forme_juridique"><?php foreach (CCRGPD_Constants::FORMES_JURIDIQUES as $k => $v) echo '<option value="'.$k.'" '.selected(get_option('client_forme_juridique'),$k,false).'>'.esc_html($v).'</option>'; ?></select>
                            <div id="forme_autre_wrap" style="margin-top:10px;display:none;"><input type="text" name="client_forme_juridique_autre" value="<?php echo esc_attr(get_option('client_forme_juridique_autre')); ?>" class="regular-text" placeholder="Précisez..."></div>
                        </td></tr>
                        <tr><th>Capital</th><td><input type="text" name="client_capital" value="<?php echo esc_attr(get_option('client_capital')); ?>" class="regular-text" placeholder="Ex: 10 000 €"></td></tr>
                        <tr><th>SIRET</th><td><input type="text" name="client_siret" value="<?php echo esc_attr(get_option('client_siret')); ?>" class="regular-text"></td></tr>
                        <tr><th>SIREN</th><td><input type="text" name="client_siren" value="<?php echo esc_attr(get_option('client_siren')); ?>" class="regular-text"></td></tr>
                        <tr><th>RCS</th><td><input type="text" name="client_rcs" value="<?php echo esc_attr(get_option('client_rcs')); ?>" class="regular-text"></td></tr>
                        <tr><th>TVA Intracom.</th><td><input type="text" name="client_tva" value="<?php echo esc_attr(get_option('client_tva')); ?>" class="regular-text"></td></tr>
                    </table></div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-agence" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields(CCRGPD_Constants::OPTION_GROUP); ?>
                    <div class="ccrgpd-box"><h2>Agence MATRYS (Hébergeur)</h2>
                    <table class="form-table">
                        <tr><th>Nom</th><td><input type="text" name="matrys_name" value="<?php echo esc_attr(self::opt('matrys_name')); ?>" class="regular-text"></td></tr>
                        <tr><th>URL</th><td><input type="url" name="matrys_url" value="<?php echo esc_attr(self::opt('matrys_url')); ?>" class="regular-text"></td></tr>
                        <tr><th>Adresse</th><td><textarea name="matrys_address" rows="3" class="large-text"><?php echo esc_textarea(self::opt('matrys_address')); ?></textarea></td></tr>
                        <tr><th>Téléphone</th><td>
                            <select name="matrys_country" style="width:140px"><?php foreach (CCRGPD_Constants::COUNTRIES as $c => $d) echo '<option value="'.$c.'" '.selected(self::opt('matrys_country'),$c,false).'>'.$d['name'].'</option>'; ?></select>
                            <input type="tel" name="matrys_tel" value="<?php echo esc_attr(self::opt('matrys_tel')); ?>" class="regular-text">
                        </td></tr>
                    </table></div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-rgpd" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields(CCRGPD_Constants::OPTION_GROUP_RGPD); ?>
                    <div class="ccrgpd-box"><h2>Configuration RGPD</h2>
                    <?php if (empty($forms)) : ?>
                        <p>⚠️ Aucun formulaire Forminator détecté.</p>
                    <?php else : foreach ($forms as $id => $f) : $c = $rgpd['forms'][$id] ?? []; $enabled = !empty($c['enabled']); ?>
                        <div class="rgpd-form <?php echo $enabled ? 'enabled' : ''; ?>">
                            <div class="rgpd-form-header">
                                <label class="toggle"><input type="checkbox" name="rgpd_settings[forms][<?php echo $id; ?>][enabled]" value="1" <?php checked($enabled); ?>><span class="slider"></span></label>
                                <div class="rgpd-form-title"><strong><?php echo esc_html($f['name']); ?></strong><span class="meta">ID: <?php echo $id; ?> • <?php echo count($f['fields']); ?> champs</span></div>
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </div>
                            <div class="rgpd-form-body">
                                <div class="detected">Champs : <?php echo esc_html(implode(', ', array_map(function($t) { return CCRGPD_Constants::FIELD_TYPES[$t] ?? $t; }, $f['fields']))); ?></div>
                                <table class="rgpd-config">
                                    <tr><th>Nom affiché</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][name_override]" value="<?php echo esc_attr($c['name_override'] ?? ''); ?>" placeholder="<?php echo esc_attr($f['name']); ?>"></td></tr>
                                    <tr><th>Finalité</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][purpose]" value="<?php echo esc_attr($c['purpose'] ?? ''); ?>" placeholder="Ex: Répondre à vos demandes"></td></tr>
                                    <tr><th>Base légale</th><td><select name="rgpd_settings[forms][<?php echo $id; ?>][legal_basis]"><?php foreach (CCRGPD_Constants::LEGAL_BASIS as $k => $v) echo '<option value="'.$k.'" '.selected($c['legal_basis'] ?? 'consent', $k, false).'>'.esc_html($v).'</option>'; ?></select></td></tr>
                                    <tr><th>Conservation</th><td><select name="rgpd_settings[forms][<?php echo $id; ?>][retention]"><?php foreach (CCRGPD_Constants::RETENTION as $k => $v) echo '<option value="'.$k.'" '.selected($c['retention'] ?? '3_years', $k, false).'>'.esc_html($v).'</option>'; ?></select></td></tr>
                                    <tr><th>Destinataires</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][recipients]" value="<?php echo esc_attr($c['recipients'] ?? ''); ?>" placeholder="Ex: Service commercial"></td></tr>
                                    <tr><th>Sous-traitants</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][third_party]" value="<?php echo esc_attr($c['third_party'] ?? ''); ?>" placeholder="Ex: Mailchimp"></td></tr>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                    </div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-shortcodes" class="tab-content">
                <div class="ccrgpd-box"><h2>Shortcodes disponibles</h2>
                <div class="shortcode-list">
                    <h3>📍 Coordonnées</h3>
                    <p><code>[client_email]</code> <code>[client_tel]</code> <code>[client_address]</code> <code>[client_address_siege]</code></p>
                    <p><code>[site_title]</code> <code>[site_link]</code> <code>[matrys_block]</code></p>
                    <h3>📄 Pages légales</h3>
                    <p><code>[mentions_legales]</code> <code>[politique_confidentialite]</code></p>
                    <h3>🧩 Composants RGPD</h3>
                    <p><code>[rgpd_mentions]</code> <code>[rgpd_droits]</code> <code>[rgpd_cookies]</code></p>
                </div></div>
            </div>
        </div>
        <?php
    }

    private static function opt($k)
    {
        $v = get_option($k);
        return ($v !== false && $v !== '') ? $v : (CCRGPD_Constants::DEFAULT_OPTIONS[$k] ?? '');
    }
}
