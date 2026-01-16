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
        add_menu_page(
            'Coordonnées & RGPD',
            'Coordonnées & RGPD',
            'manage_options',
            CCRGPD_Constants::MENU_SLUG,
            [__CLASS__, 'render_page'],
            'dashicons-id',
            20
        );
    }

    public static function register_settings()
    {
        // TOUS les champs dans le MÊME groupe (sauf RGPD)
        $fields = [
            'client_raison_sociale', 'client_email', 'client_tel', 'client_country', 'client_address',
            'client_address_siege', 'client_siret', 'client_siren', 'client_rcs', 'client_capital',
            'client_tva', 'client_responsable', 'client_forme_juridique', 'client_forme_juridique_autre',
            'matrys_name', 'matrys_url', 'matrys_address', 'matrys_tel', 'matrys_country',
        ];
        
        foreach ($fields as $field) {
            register_setting(CCRGPD_Constants::OPTION_GROUP, $field);
        }
        
        // RGPD dans un groupe séparé (OK car onglet distinct avec son propre bouton)
        register_setting(CCRGPD_Constants::OPTION_GROUP_RGPD, 'rgpd_settings', [__CLASS__, 'sanitize_rgpd']);
    }

    public static function sanitize_rgpd($input)
    {
        $sanitized = ['forms' => []];
        
        if (!empty($input['forms']) && is_array($input['forms'])) {
            foreach ($input['forms'] as $id => $config) {
                $sanitized['forms'][$id] = [
                    'enabled' => !empty($config['enabled']),
                    'name_override' => sanitize_text_field($config['name_override'] ?? ''),
                    'purpose' => sanitize_text_field($config['purpose'] ?? ''),
                    'legal_basis' => sanitize_text_field($config['legal_basis'] ?? 'consent'),
                    'retention' => sanitize_text_field($config['retention'] ?? '3_years'),
                    'recipients' => sanitize_text_field($config['recipients'] ?? ''),
                    'third_party' => sanitize_text_field($config['third_party'] ?? ''),
                ];
            }
        }
        
        return $sanitized;
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
        $forms = CCRGPD_Shortcodes::get_all_forms();
        $has_forminator = class_exists('Forminator_API');
        $has_sureforms = post_type_exists('sureforms_form');
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
            
            <!-- ============================================================ -->
            <!-- FORMULAIRE UNIQUE pour Coordonnées + Juridique + Agence      -->
            <!-- Tous les champs sont dans le même formulaire = pas d'écrasement -->
            <!-- ============================================================ -->
            <form method="post" action="options.php" id="form-main">
                <?php settings_fields(CCRGPD_Constants::OPTION_GROUP); ?>
                
                <!-- ONGLET COORDONNÉES -->
                <div id="tab-coordonnees" class="tab-content active">
                    <div class="ccrgpd-box">
                        <h2>Coordonnées de contact</h2>
                        <p class="description">Informations affichées sur le site (footer, page contact...)</p>
                        <table class="form-table">
                            <tr>
                                <th><label for="client_email">Email *</label></th>
                                <td><input type="email" name="client_email" id="client_email" value="<?php echo esc_attr(get_option('client_email')); ?>" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="client_tel">Téléphone</label></th>
                                <td>
                                    <select name="client_country" id="client_country" style="width:150px">
                                        <?php foreach (CCRGPD_Constants::COUNTRIES as $code => $data) : ?>
                                            <option value="<?php echo $code; ?>" <?php selected(get_option('client_country', 'FR'), $code); ?>><?php echo $data['name']; ?> (<?php echo $data['code']; ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" name="client_tel" id="client_tel" value="<?php echo esc_attr(get_option('client_tel')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="client_address">Adresse établissement *</label></th>
                                <td>
                                    <textarea name="client_address" id="client_address" rows="3" class="large-text" required><?php echo esc_textarea(get_option('client_address')); ?></textarea>
                                    <p class="description">Adresse physique affichée dans le footer et la page contact</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- ONGLET JURIDIQUE -->
                <div id="tab-juridique" class="tab-content">
                    <div class="ccrgpd-box">
                        <h2>🔍 Recherche automatique par SIRET</h2>
                        <p class="description">Remplissez automatiquement les informations légales via l'API Recherche d'Entreprises (data.gouv.fr)</p>
                        <div class="siret-search">
                            <input type="text" id="siret_search" placeholder="Ex: 123 456 789 00012" maxlength="20">
                            <button type="button" id="btn-search-siret" class="button button-secondary">🔍 Rechercher</button>
                            <span class="spinner" id="siret-spinner"></span>
                        </div>
                        <div id="siret-result"></div>
                    </div>
                    
                    <div class="ccrgpd-box">
                        <h2>Informations légales (Éditeur du site)</h2>
                        <p class="description">Informations obligatoires pour les mentions légales (LCEN)</p>
                        <table class="form-table">
                            <tr>
                                <th><label for="client_raison_sociale">Raison sociale *</label></th>
                                <td><input type="text" name="client_raison_sociale" id="client_raison_sociale" value="<?php echo esc_attr(get_option('client_raison_sociale')); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="client_address_siege">Adresse siège social</label></th>
                                <td>
                                    <textarea name="client_address_siege" id="client_address_siege" rows="3" class="large-text"><?php echo esc_textarea(get_option('client_address_siege')); ?></textarea>
                                    <p class="description">Si différente de l'adresse établissement. Sinon laissez vide.</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="client_responsable">Responsable publication *</label></th>
                                <td><input type="text" name="client_responsable" id="client_responsable" value="<?php echo esc_attr(get_option('client_responsable')); ?>" class="regular-text" placeholder="Nom du dirigeant"></td>
                            </tr>
                            <tr>
                                <th><label for="client_forme_juridique">Forme juridique</label></th>
                                <td>
                                    <select name="client_forme_juridique" id="client_forme_juridique">
                                        <?php foreach (CCRGPD_Constants::FORMES_JURIDIQUES as $key => $label) : ?>
                                            <option value="<?php echo $key; ?>" <?php selected(get_option('client_forme_juridique'), $key); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="forme_autre_wrap" style="margin-top:10px;display:none;">
                                        <input type="text" name="client_forme_juridique_autre" id="client_forme_juridique_autre" value="<?php echo esc_attr(get_option('client_forme_juridique_autre')); ?>" class="regular-text" placeholder="Précisez...">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="client_capital">Capital social</label></th>
                                <td><input type="text" name="client_capital" id="client_capital" value="<?php echo esc_attr(get_option('client_capital')); ?>" class="regular-text" placeholder="Ex: 10 000 €"></td>
                            </tr>
                            <tr>
                                <th><label for="client_siret">SIRET</label></th>
                                <td><input type="text" name="client_siret" id="client_siret" value="<?php echo esc_attr(get_option('client_siret')); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="client_siren">SIREN</label></th>
                                <td><input type="text" name="client_siren" id="client_siren" value="<?php echo esc_attr(get_option('client_siren')); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="client_rcs">RCS</label></th>
                                <td><input type="text" name="client_rcs" id="client_rcs" value="<?php echo esc_attr(get_option('client_rcs')); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="client_tva">N° TVA Intracommunautaire</label></th>
                                <td><input type="text" name="client_tva" id="client_tva" value="<?php echo esc_attr(get_option('client_tva')); ?>" class="regular-text"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- ONGLET AGENCE -->
                <div id="tab-agence" class="tab-content">
                    <div class="ccrgpd-box">
                        <h2>Agence MATRYS (Hébergeur)</h2>
                        <p class="description">Informations affichées dans "Réalisation et hébergement" des mentions légales</p>
                        <table class="form-table">
                            <tr>
                                <th><label for="matrys_name">Nom de l'agence</label></th>
                                <td><input type="text" name="matrys_name" id="matrys_name" value="<?php echo esc_attr(self::opt('matrys_name')); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="matrys_url">URL du site</label></th>
                                <td><input type="url" name="matrys_url" id="matrys_url" value="<?php echo esc_attr(self::opt('matrys_url')); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="matrys_address">Adresse</label></th>
                                <td><textarea name="matrys_address" id="matrys_address" rows="3" class="large-text"><?php echo esc_textarea(self::opt('matrys_address')); ?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="matrys_tel">Téléphone</label></th>
                                <td>
                                    <select name="matrys_country" id="matrys_country" style="width:150px">
                                        <?php foreach (CCRGPD_Constants::COUNTRIES as $code => $data) : ?>
                                            <option value="<?php echo $code; ?>" <?php selected(self::opt('matrys_country'), $code); ?>><?php echo $data['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" name="matrys_tel" id="matrys_tel" value="<?php echo esc_attr(self::opt('matrys_tel')); ?>" class="regular-text">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Bouton submit pour le formulaire principal -->
                <p class="submit" id="submit-main">
                    <input type="submit" class="button button-primary" value="💾 Enregistrer">
                </p>
            </form>
            
            <!-- ============================================================ -->
            <!-- ONGLET RGPD - Formulaire séparé (c'est OK car indépendant)   -->
            <!-- ============================================================ -->
            <div id="tab-rgpd" class="tab-content">
                <form method="post" action="options.php" id="form-rgpd">
                    <?php settings_fields(CCRGPD_Constants::OPTION_GROUP_RGPD); ?>
                    <div class="ccrgpd-box">
                        <h2>Configuration RGPD des formulaires</h2>
                        <p class="description">Activez et configurez chaque formulaire pour la politique de confidentialité.</p>
                        
                        <?php if (!$has_forminator && !$has_sureforms) : ?>
                            <div class="notice notice-info inline">
                                <p>ℹ️ Aucun plugin de formulaire compatible détecté.</p>
                                <p>
                                    <a href="<?php echo admin_url('plugin-install.php?s=forminator&tab=search&type=term'); ?>">Installer Forminator</a> ou 
                                    <a href="<?php echo admin_url('plugin-install.php?s=sureforms&tab=search&type=term'); ?>">Installer SureForms</a>
                                </p>
                            </div>
                        <?php elseif (empty($forms)) : ?>
                            <div class="notice notice-warning inline">
                                <p>⚠️ Aucun formulaire détecté.</p>
                                <p>
                                    <?php if ($has_forminator) : ?>
                                        <a href="<?php echo admin_url('admin.php?page=forminator-cform'); ?>">Créer un formulaire Forminator</a>
                                    <?php endif; ?>
                                    <?php if ($has_forminator && $has_sureforms) echo ' ou '; ?>
                                    <?php if ($has_sureforms) : ?>
                                        <a href="<?php echo admin_url('admin.php?page=sureforms_menu'); ?>">Créer un formulaire SureForms</a>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($forms as $id => $form) : 
                                $config = $rgpd['forms'][$id] ?? [];
                                $enabled = !empty($config['enabled']);
                                $plugin_badge = isset($form['plugin']) ? '<span class="plugin-badge plugin-' . strtolower($form['plugin']) . '">' . esc_html($form['plugin']) . '</span>' : '';
                            ?>
                            <div class="rgpd-form <?php echo $enabled ? 'enabled' : ''; ?>">
                                <div class="rgpd-form-header">
                                    <label class="toggle">
                                        <input type="checkbox" name="rgpd_settings[forms][<?php echo esc_attr($id); ?>][enabled]" value="1" <?php checked($enabled); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="rgpd-form-title">
                                        <strong><?php echo esc_html($form['name']); ?></strong> <?php echo $plugin_badge; ?>
                                        <span class="meta"><?php echo count($form['fields']); ?> champs</span>
                                    </div>
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </div>
                                <div class="rgpd-form-body">
                                    <?php 
                                    // Utiliser l'analyse si disponible
                                    if (!empty($form['analysis'])) {
                                        $analysis = $form['analysis'];
                                        
                                        // Afficher les catégories détectées
                                        if (!empty($analysis['categories'])) {
                                            echo '<div class="detected categories-detected">';
                                            echo '<strong>📊 Données collectées :</strong> ';
                                            $parts = [];
                                            foreach ($analysis['categories'] as $catKey => $catData) {
                                                $icon = $catData['info']['icon'];
                                                $label = $catData['info']['label'];
                                                $fields = implode(', ', array_map('strtolower', $catData['fields']));
                                                $parts[] = '<span class="category-tag" title="' . esc_attr($fields) . '">' . $icon . ' ' . esc_html($label) . '</span>';
                                            }
                                            echo implode(' ', $parts);
                                            echo '</div>';
                                        }
                                        
                                        // Bannière d'avertissement pour les champs non reconnus
                                        if (!empty($analysis['unrecognized'])) {
                                            $unrecognized = array_map(function($f) { 
                                                return $f['label'] ?: $f['type']; 
                                            }, $analysis['unrecognized']);
                                            echo '<div class="notice notice-warning inline" style="margin:10px 0;padding:8px 12px">';
                                            echo '⚠️ <strong>Champs non catégorisés :</strong> ' . esc_html(implode(', ', $unrecognized));
                                            echo '<br><small>Ces champs ne seront pas mentionnés dans la politique de confidentialité.</small>';
                                            echo '</div>';
                                        }
                                    } else {
                                        // Fallback ancien affichage
                                        echo '<div class="detected">';
                                        echo '<strong>Champs détectés :</strong> ';
                                        $fieldLabels = array_map(function($f) {
                                            if (is_array($f)) {
                                                return $f['label'] ?: (CCRGPD_Constants::FIELD_TYPES[$f['type']] ?? $f['type']);
                                            }
                                            return CCRGPD_Constants::FIELD_TYPES[$f] ?? $f;
                                        }, $form['fields']);
                                        echo esc_html(implode(', ', $fieldLabels));
                                        echo '</div>';
                                    }
                                    ?>
                                    <table class="rgpd-config">
                                        <tr>
                                            <th>Nom affiché</th>
                                            <td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][name_override]" value="<?php echo esc_attr($config['name_override'] ?? ''); ?>" placeholder="<?php echo esc_attr($form['name']); ?>"></td>
                                        </tr>
                                        <tr>
                                            <th>Finalité</th>
                                            <td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][purpose]" value="<?php echo esc_attr($config['purpose'] ?? ''); ?>" placeholder="Ex: Répondre à vos demandes de contact"></td>
                                        </tr>
                                        <tr>
                                            <th>Base légale</th>
                                            <td>
                                                <select name="rgpd_settings[forms][<?php echo $id; ?>][legal_basis]">
                                                    <?php foreach (CCRGPD_Constants::LEGAL_BASIS as $key => $label) : ?>
                                                        <option value="<?php echo $key; ?>" <?php selected($config['legal_basis'] ?? 'consent', $key); ?>><?php echo esc_html($label); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Conservation</th>
                                            <td>
                                                <select name="rgpd_settings[forms][<?php echo $id; ?>][retention]">
                                                    <?php foreach (CCRGPD_Constants::RETENTION as $key => $label) : ?>
                                                        <option value="<?php echo $key; ?>" <?php selected($config['retention'] ?? '3_years', $key); ?>><?php echo esc_html($label); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Destinataires</th>
                                            <td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][recipients]" value="<?php echo esc_attr($config['recipients'] ?? ''); ?>" placeholder="Ex: Service commercial"></td>
                                        </tr>
                                        <tr>
                                            <th>Sous-traitants</th>
                                            <td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][third_party]" value="<?php echo esc_attr($config['third_party'] ?? ''); ?>" placeholder="Ex: Mailchimp, Sendinblue"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php submit_button('💾 Enregistrer la configuration RGPD'); ?>
                </form>
            </div>
            
            <!-- ONGLET SHORTCODES -->
            <div id="tab-shortcodes" class="tab-content">
                <div class="ccrgpd-box">
                    <h2>Shortcodes disponibles</h2>
                    <div class="shortcode-list">
                        <h3>📍 Coordonnées</h3>
                        <p><code>[client_email]</code> → Email cliquable (mailto:)</p>
                        <p><code>[client_tel]</code> → Téléphone cliquable (tel:)</p>
                        <p><code>[client_address]</code> → Adresse de l'établissement</p>
                        <p><code>[client_address_siege]</code> → Adresse siège (ou établissement si non renseignée)</p>
                        <p><code>[site_title]</code> → Nom du site WordPress</p>
                        <p><code>[site_link]</code> → Lien vers l'accueil</p>
                        <p><code>[matrys_block]</code> → Bloc complet agence MATRYS</p>
                        
                        <h3>📄 Pages légales complètes</h3>
                        <p><code>[mentions_legales]</code> → Page Mentions Légales complète</p>
                        <p><code>[politique_confidentialite]</code> → Politique de Confidentialité complète</p>
                        
                        <h3>🧩 Composants RGPD</h3>
                        <p><code>[rgpd_mentions]</code> → Tableau des traitements de données</p>
                        <p><code>[rgpd_droits]</code> → Bloc "Exercer vos droits" + contact CNIL</p>
                        <p><code>[rgpd_cookies]</code> → Liste des cookies + bouton WP Consent</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function opt($key)
    {
        $value = get_option($key);
        return ($value !== false && $value !== '') ? $value : (CCRGPD_Constants::DEFAULT_OPTIONS[$key] ?? '');
    }
}
