<?php
defined('ABSPATH') || exit;

class CCRGPD_Shortcodes
{
    public static function register()
    {
        $shortcodes = [
            'client_email' => 'email',
            'client_tel' => 'tel',
            'client_address' => 'address',
            'client_address_siege' => 'address_siege',
            'site_title' => 'site_title',
            'site_link' => 'site_link',
            'matrys_block' => 'matrys_block',
            'rgpd_mentions' => 'rgpd_mentions',
            'rgpd_droits' => 'rgpd_droits',
            'rgpd_cookies' => 'rgpd_cookies',
            'mentions_legales' => 'mentions_legales',
            'politique_confidentialite' => 'politique_confidentialite',
        ];
        
        foreach ($shortcodes as $tag => $method) {
            add_shortcode($tag, [__CLASS__, $method]);
        }
    }

    // ==================== COORDONNÉES ====================

    public static function email()
    {
        $e = get_option('client_email');
        return $e ? '<a href="mailto:' . esc_attr($e) . '">' . esc_html($e) . '</a>' : '';
    }

    public static function tel()
    {
        $t = get_option('client_tel');
        if (!$t) return '';
        $c = get_option('client_country', 'FR');
        $code = CCRGPD_Constants::COUNTRIES[$c]['code'] ?? '+33';
        $clean = preg_replace('/^0/', '', preg_replace('/[^0-9]/', '', $t));
        return '<a href="tel:' . $code . $clean . '">' . esc_html($t) . '</a>';
    }

    public static function address()
    {
        return nl2br(esc_html(get_option('client_address')));
    }

    public static function address_siege()
    {
        $siege = get_option('client_address_siege');
        return nl2br(esc_html($siege ?: get_option('client_address')));
    }

    public static function site_title()
    {
        return esc_html(get_bloginfo('name'));
    }

    public static function site_link()
    {
        return '<a href="' . esc_url(home_url()) . '">' . esc_html(get_bloginfo('name')) . '</a>';
    }

    public static function matrys_block()
    {
        $n = self::opt('matrys_name');
        $u = self::opt('matrys_url');
        $a = self::opt('matrys_address');
        $t = self::opt('matrys_tel');

        $out = '<div class="matrys-block">';
        $out .= '<strong><a href="' . esc_url($u) . '" target="_blank" rel="noopener">' . esc_html($n) . '</a></strong><br>';
        $out .= nl2br(esc_html($a));
        if ($t) $out .= '<br>' . self::t('tel') . ' : ' . esc_html($t);
        $out .= '</div>';
        return $out;
    }

    // ==================== RGPD ====================

    public static function rgpd_mentions($atts = [])
    {
        $rgpd = get_option('rgpd_settings', []);
        $forms = self::get_forms();
        $out = '';

        foreach ($forms as $id => $f) {
            if (empty($rgpd['forms'][$id]['enabled'])) continue;
            
            $c = $rgpd['forms'][$id];
            $name = $c['name_override'] ?: $f['name'];
            $data = array_map(function($t) { 
                return CCRGPD_Constants::FIELD_TYPES[$t] ?? $t; 
            }, $f['fields']);

            $out .= '<table class="rgpd-table" style="width:100%;border-collapse:collapse;margin-bottom:25px">';
            $out .= '<tr style="background:#f5f5f5"><th colspan="2" style="padding:12px 15px;text-align:left;border:1px solid #ddd;font-size:1.1em">' . esc_html($name) . '</th></tr>';
            $out .= '<tr><td style="padding:10px 15px;border:1px solid #ddd;width:35%;vertical-align:top;background:#fafafa"><strong>' . self::t('pc_table_data') . '</strong></td><td style="padding:10px 15px;border:1px solid #ddd">' . esc_html(implode(', ', $data)) . '</td></tr>';
            $out .= '<tr><td style="padding:10px 15px;border:1px solid #ddd;vertical-align:top;background:#fafafa"><strong>' . self::t('pc_table_purpose') . '</strong></td><td style="padding:10px 15px;border:1px solid #ddd">' . esc_html($c['purpose'] ?: '-') . '</td></tr>';
            $out .= '<tr><td style="padding:10px 15px;border:1px solid #ddd;vertical-align:top;background:#fafafa"><strong>' . self::t('pc_table_legal') . '</strong></td><td style="padding:10px 15px;border:1px solid #ddd">' . esc_html(CCRGPD_Constants::LEGAL_BASIS[$c['legal_basis']] ?? '-') . '</td></tr>';
            $out .= '<tr><td style="padding:10px 15px;border:1px solid #ddd;vertical-align:top;background:#fafafa"><strong>' . self::t('pc_table_retention') . '</strong></td><td style="padding:10px 15px;border:1px solid #ddd">' . esc_html(CCRGPD_Constants::RETENTION[$c['retention']] ?? '-') . '</td></tr>';
            
            if (!empty($c['recipients'])) {
                $out .= '<tr><td style="padding:10px 15px;border:1px solid #ddd;vertical-align:top;background:#fafafa"><strong>' . self::t('pc_recipients') . '</strong></td><td style="padding:10px 15px;border:1px solid #ddd">' . esc_html($c['recipients']) . '</td></tr>';
            }
            if (!empty($c['third_party'])) {
                $out .= '<tr><td style="padding:10px 15px;border:1px solid #ddd;vertical-align:top;background:#fafafa"><strong>' . self::t('pc_third_party') . '</strong></td><td style="padding:10px 15px;border:1px solid #ddd">' . esc_html($c['third_party']) . '</td></tr>';
            }
            $out .= '</table>';
        }
        return $out;
    }

    public static function rgpd_droits()
    {
        $n = get_option('client_raison_sociale') ?: get_bloginfo('name');
        $e = get_option('client_email');
        $a = get_option('client_address_siege') ?: get_option('client_address');
        $dpa = self::t('dpa_name');
        $dpa_url = self::t('dpa_url');

        $out = '<p>' . sprintf(self::t('rights_intro'), '<strong>' . esc_html($n) . '</strong>') . '</p>';
        $out .= '<ul>';
        if ($e) {
            $out .= '<li>' . sprintf(self::t('rights_email'), '<a href="mailto:' . esc_attr($e) . '">' . esc_html($e) . '</a>') . '</li>';
        }
        if ($a) {
            $out .= '<li>' . self::t('rights_mail') . ' <strong>' . esc_html($n) . '</strong>, ' . esc_html(str_replace("\n", ', ', $a)) . '</li>';
        }
        $out .= '</ul>';
        $out .= '<p>' . self::t('rights_list') . '</p>';
        $out .= '<p>' . sprintf(self::t('rights_dpa'), '<strong>' . esc_html($dpa) . '</strong>', '<a href="' . esc_url($dpa_url) . '" target="_blank" rel="noopener">' . esc_url($dpa_url) . '</a>') . '</p>';
        
        return $out;
    }

    public static function rgpd_cookies()
    {
        $out = '';
        
        // Utiliser le shortcode WP Consent si disponible
        if (shortcode_exists('wpconsent_cookie_policy')) {
            $out .= do_shortcode('[wpconsent_cookie_policy]');
        } else {
            // Fallback si WP Consent n'est pas installé
            $out .= '<ul>';
            $out .= '<li><strong>Google Analytics</strong> : _ga, _gid, _gat — Mesure d\'audience (durée : 13 mois maximum)</li>';
            $out .= '<li><strong>WordPress</strong> : wordpress_*, wp-settings-* — Gestion de session utilisateur (durée : session)</li>';
            $out .= '</ul>';
        }
        
        // Bouton pour gérer les préférences
        if (function_exists('wp_has_consent') || shortcode_exists('wpconsent_cookie_policy')) {
            $out .= '<p>' . self::t('cookies_manage_text') . '</p>';
            $out .= '<p><button type="button" class="wp-consent-api-toggle button" style="cursor:pointer" onclick="';
            $out .= 'if(typeof wpconsent !== \'undefined\' && wpconsent.openSettings){wpconsent.openSettings();}';
            $out .= 'else if(typeof cmplz_open_popup === \'function\'){cmplz_open_popup();}';
            $out .= 'else if(typeof Cookiebot === \'object\'){Cookiebot.renew();}';
            $out .= 'else if(typeof __tcfapi === \'function\'){__tcfapi(\'displayConsentUi\',2,function(){});}';
            $out .= 'else{alert(\'Gestionnaire de cookies non disponible\');}';
            $out .= '">' . self::t('cookies_manage_button') . '</button></p>';
        }
        
        return $out;
    }

    // ==================== PAGES LÉGALES COMPLÈTES ====================

    public static function mentions_legales()
    {
        $n = get_option('client_raison_sociale') ?: get_bloginfo('name');
        $e = get_option('client_email');
        $t = get_option('client_tel');
        $a = get_option('client_address_siege') ?: get_option('client_address');
        $r = get_option('client_responsable');
        $siret = get_option('client_siret');
        $rcs = get_option('client_rcs');
        $cap = get_option('client_capital');
        $tva = get_option('client_tva');
        $forme = get_option('client_forme_juridique');
        $forme_autre = get_option('client_forme_juridique_autre');
        $forme_display = ($forme === 'Autre' && $forme_autre) ? $forme_autre : (CCRGPD_Constants::FORMES_JURIDIQUES[$forme] ?? '');
        $u = home_url();

        ob_start();
        
        // === ÉDITEUR DU SITE ===
        echo '<h2>' . esc_html(self::t('ml_editor')) . '</h2>';
        echo '<p>' . sprintf(self::t('ml_editor_intro'), '<a href="' . esc_url($u) . '">' . esc_url($u) . '</a>') . '</p>';
        
        echo '<p><strong>' . esc_html($n) . '</strong>';
        if ($forme_display) echo '<br>' . esc_html($forme_display);
        if ($cap) echo ' au capital de ' . esc_html($cap);
        echo '<br>' . nl2br(esc_html($a));
        if ($e) echo '<br>' . self::t('email') . ' : <a href="mailto:' . esc_attr($e) . '">' . esc_html($e) . '</a>';
        if ($t) echo '<br>' . self::t('tel') . ' : ' . esc_html($t);
        if ($siret) echo '<br>SIRET : ' . esc_html($siret);
        if ($rcs) echo '<br>RCS : ' . esc_html($rcs);
        if ($tva) echo '<br>N° TVA intracommunautaire : ' . esc_html($tva);
        echo '</p>';
        
        if ($r) {
            echo '<p><strong>' . esc_html(self::t('ml_responsible')) . '</strong> ' . esc_html($r) . '</p>';
        }

        // === HÉBERGEMENT ===
        echo '<h2>' . esc_html(self::t('ml_hosting')) . '</h2>';
        echo self::matrys_block();

        // === PROPRIÉTÉ INTELLECTUELLE ===
        echo '<h2>' . esc_html(self::t('ml_intellectual')) . '</h2>';
        echo '<p>' . esc_html(self::t('ml_intellectual_text')) . '</p>';
        if ($e) {
            echo '<p>' . sprintf(self::t('ml_intellectual_contact'), '<a href="mailto:' . esc_attr($e) . '">' . esc_html($e) . '</a>') . '</p>';
        }

        // === INFORMATIONS ET EXCLUSIONS ===
        echo '<h2>' . esc_html(self::t('ml_info')) . '</h2>';
        echo '<p>' . esc_html(self::t('ml_info_text1')) . '</p>';
        echo '<p>' . esc_html(self::t('ml_info_text2')) . '</p>';
        echo '<p>' . esc_html(self::t('ml_info_text3')) . '</p>';

        // === DONNÉES PERSONNELLES ===
        echo '<h2>' . esc_html(self::t('ml_personal_data')) . '</h2>';
        echo '<p>' . esc_html(self::t('ml_personal_data_text')) . '</p>';
        
        $privacy_url = get_privacy_policy_url();
        if ($privacy_url) {
            echo '<p>' . sprintf(self::t('ml_privacy_link'), '<a href="' . esc_url($privacy_url) . '">' . esc_html(self::t('ml_privacy_page')) . '</a>') . '</p>';
        }

        return ob_get_clean();
    }

    public static function politique_confidentialite()
    {
        $n = get_option('client_raison_sociale') ?: get_bloginfo('name');
        $u = home_url();
        $rgpd = get_option('rgpd_settings', []);
        $forms = self::get_forms();
        
        // Vérifier s'il y a des formulaires activés
        $has_forms = false;
        foreach ($forms as $id => $f) {
            if (!empty($rgpd['forms'][$id]['enabled'])) {
                $has_forms = true;
                break;
            }
        }

        ob_start();

        // === INTRODUCTION ===
        echo '<p>' . sprintf(self::t('pc_intro'), '<strong>' . esc_html($n) . '</strong>', '<a href="' . esc_url($u) . '">' . esc_url($u) . '</a>') . '</p>';

        // === DONNÉES COLLECTÉES (TEXTES COMPLETS) ===
        echo '<h2>' . esc_html(self::t('pc_data_collected')) . '</h2>';
        echo '<p>' . esc_html(self::t('pc_data_text1')) . '</p>';
        echo '<p>' . esc_html(self::t('pc_data_text2')) . '</p>';
        echo '<p>' . esc_html(self::t('pc_data_text3')) . '</p>';

        // === TRAITEMENTS DE DONNÉES ===
        if ($has_forms) {
            echo '<h2>' . esc_html(self::t('pc_treatments')) . '</h2>';
            echo self::rgpd_mentions([]);
        }

        // === EXERCER VOS DROITS ===
        echo '<h2>' . esc_html(self::t('rights_title')) . '</h2>';
        echo self::rgpd_droits();

        // === COOKIES ===
        echo '<h2>' . esc_html(self::t('cookies_title')) . '</h2>';
        
        echo '<h3>' . esc_html(self::t('cookies_info_title')) . '</h3>';
        echo '<p>' . esc_html(self::t('cookies_info_text')) . '</p>';
        
        echo '<h3>' . esc_html(self::t('cookies_purpose_title')) . '</h3>';
        echo self::rgpd_cookies();
        
        echo '<h3>' . esc_html(self::t('cookies_choices_title')) . '</h3>';
        echo '<p>' . esc_html(self::t('cookies_choices_text')) . '</p>';
        
        // Liens vers les paramètres des navigateurs
        echo '<p>' . esc_html(self::t('cookies_browser_text')) . '</p>';
        echo '<ul>';
        echo '<li><a href="https://support.microsoft.com/fr-fr/microsoft-edge/supprimer-les-cookies-dans-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Microsoft Edge</a></li>';
        echo '<li><a href="https://support.apple.com/fr-fr/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Safari</a></li>';
        echo '<li><a href="https://support.google.com/chrome/answer/95647?hl=fr" target="_blank" rel="noopener">Google Chrome</a></li>';
        echo '<li><a href="https://support.mozilla.org/fr/kb/activer-desactiver-cookies" target="_blank" rel="noopener">Mozilla Firefox</a></li>';
        echo '<li><a href="https://help.opera.com/fr/latest/web-preferences/#cookies" target="_blank" rel="noopener">Opera</a></li>';
        echo '</ul>';

        return ob_get_clean();
    }

    // ==================== HELPERS ====================

    private static function t($key)
    {
        $lang = self::get_lang();
        return CCRGPD_Constants::TRANSLATIONS[$lang][$key] 
            ?? CCRGPD_Constants::TRANSLATIONS['fr_FR'][$key] 
            ?? $key;
    }

    private static function get_lang()
    {
        $locale = get_locale();
        if (isset(CCRGPD_Constants::TRANSLATIONS[$locale])) return $locale;
        return (substr($locale, 0, 2) === 'en') ? 'en_US' : 'fr_FR';
    }

    private static function opt($key)
    {
        $value = get_option($key);
        return ($value !== false && $value !== '') ? $value : (CCRGPD_Constants::DEFAULT_OPTIONS[$key] ?? '');
    }

    public static function get_forms()
    {
        if (!class_exists('Forminator_API')) return [];
        
        $forms = [];
        $all = Forminator_API::get_forms(null, 1, 100, 'publish');
        if (empty($all)) return [];
        
        foreach ($all as $form) {
            $fields = [];
            
            // Méthode 1: Via Forminator_API::get_form_fields (recommandée)
            if (method_exists('Forminator_API', 'get_form_fields')) {
                $form_fields = Forminator_API::get_form_fields($form->id);
                if (!empty($form_fields) && !is_wp_error($form_fields)) {
                    foreach ($form_fields as $f) {
                        $type = $f['type'] ?? ($f->type ?? 'text');
                        if (!in_array($type, $fields)) $fields[] = $type;
                    }
                }
            }
            
            // Méthode 2: Via $form->fields (Forminator récent)
            if (empty($fields) && !empty($form->fields)) {
                foreach ($form->fields as $f) {
                    $type = is_array($f) ? ($f['type'] ?? 'text') : ($f->type ?? 'text');
                    if (!in_array($type, $fields)) $fields[] = $type;
                }
            }
            
            // Méthode 3: Via wrappers (ancienne structure)
            if (empty($fields) && !empty($form->settings['wrappers'])) {
                foreach ($form->settings['wrappers'] as $w) {
                    foreach ($w['fields'] ?? [] as $f) {
                        $type = $f['type'] ?? 'text';
                        if (!in_array($type, $fields)) $fields[] = $type;
                    }
                }
            }
            
            // Méthode 4: Charger le modèle complet
            if (empty($fields) && class_exists('Forminator_Base_Form_Model')) {
                $model = Forminator_Base_Form_Model::get_model($form->id);
                if ($model && !empty($model->fields)) {
                    foreach ($model->fields as $f) {
                        $type = is_array($f) ? ($f['type'] ?? 'text') : ($f->type ?? 'text');
                        if (!in_array($type, $fields)) $fields[] = $type;
                    }
                }
            }
            
            $forms[$form->id] = [
                'name' => $form->settings['formName'] ?? ($form->name ?? 'Formulaire #' . $form->id),
                'fields' => $fields
            ];
        }
        return $forms;
    }
}
