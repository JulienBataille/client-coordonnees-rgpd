<?php
defined('ABSPATH') || exit;

class CCRGPD_Shortcodes
{
    public static function register()
    {
        $sc = [
            'client_email'=>'email','client_tel'=>'tel','client_address'=>'address',
            'client_address_siege'=>'address_siege','site_title'=>'site_title','site_link'=>'site_link',
            'matrys_block'=>'matrys_block','rgpd_mentions'=>'rgpd_mentions','rgpd_droits'=>'rgpd_droits',
            'rgpd_cookies'=>'rgpd_cookies','mentions_legales'=>'mentions_legales',
            'politique_confidentialite'=>'politique_confidentialite',
        ];
        foreach ($sc as $tag => $method) {
            add_shortcode($tag, [__CLASS__, $method]);
        }
    }

    public static function email()
    {
        $e = get_option('client_email');
        return $e ? '<a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>' : '';
    }

    public static function tel()
    {
        $t = get_option('client_tel');
        if (!$t) return '';
        $c = get_option('client_country', 'FR');
        $code = CCRGPD_Constants::COUNTRIES[$c]['code'] ?? '+33';
        $clean = preg_replace('/^0/', '', preg_replace('/[^0-9]/', '', $t));
        return '<a href="tel:'.$code.$clean.'">'.esc_html($t).'</a>';
    }

    public static function address()
    {
        return nl2br(esc_html(get_option('client_address')));
    }

    public static function address_siege()
    {
        $s = get_option('client_address_siege');
        return nl2br(esc_html($s ?: get_option('client_address')));
    }

    public static function site_title()
    {
        return esc_html(get_bloginfo('name'));
    }

    public static function site_link()
    {
        return '<a href="'.esc_url(home_url()).'">'.esc_html(get_bloginfo('name')).'</a>';
    }

    public static function matrys_block()
    {
        $n = self::opt('matrys_name');
        $u = self::opt('matrys_url');
        $a = self::opt('matrys_address');
        $t = self::opt('matrys_tel');
        $out = '<div class="matrys-block">';
        $out .= '<strong><a href="'.esc_url($u).'" target="_blank" rel="noopener">'.esc_html($n).'</a></strong><br>';
        $out .= nl2br(esc_html($a));
        if ($t) $out .= '<br>'.self::t('tel').' : '.esc_html($t);
        $out .= '</div>';
        return $out;
    }

    public static function rgpd_mentions($atts = [])
    {
        $rgpd = get_option('rgpd_settings', []);
        $forms = self::get_forms();
        $out = '';

        foreach ($forms as $id => $f) {
            if (empty($rgpd['forms'][$id]['enabled'])) continue;
            $c = $rgpd['forms'][$id];
            $name = $c['name_override'] ?: $f['name'];
            $data = array_map(function($t) { return CCRGPD_Constants::FIELD_TYPES[$t] ?? $t; }, $f['fields']);

            $out .= '<table style="width:100%;border-collapse:collapse;margin-bottom:20px">';
            $out .= '<tr style="background:#f5f5f5"><th colspan="2" style="padding:10px;text-align:left;border:1px solid #ddd">'.esc_html($name).'</th></tr>';
            $out .= '<tr><td style="padding:8px;border:1px solid #ddd;width:30%"><strong>'.self::t('pc_table_data').'</strong></td><td style="padding:8px;border:1px solid #ddd">'.esc_html(implode(', ',$data)).'</td></tr>';
            $out .= '<tr><td style="padding:8px;border:1px solid #ddd"><strong>'.self::t('pc_table_purpose').'</strong></td><td style="padding:8px;border:1px solid #ddd">'.esc_html($c['purpose'] ?: '-').'</td></tr>';
            $out .= '<tr><td style="padding:8px;border:1px solid #ddd"><strong>'.self::t('pc_table_legal').'</strong></td><td style="padding:8px;border:1px solid #ddd">'.(CCRGPD_Constants::LEGAL_BASIS[$c['legal_basis']] ?? '-').'</td></tr>';
            $out .= '<tr><td style="padding:8px;border:1px solid #ddd"><strong>'.self::t('pc_table_retention').'</strong></td><td style="padding:8px;border:1px solid #ddd">'.(CCRGPD_Constants::RETENTION[$c['retention']] ?? '-').'</td></tr>';
            if (!empty($c['recipients'])) $out .= '<tr><td style="padding:8px;border:1px solid #ddd"><strong>'.self::t('pc_recipients').'</strong></td><td style="padding:8px;border:1px solid #ddd">'.esc_html($c['recipients']).'</td></tr>';
            if (!empty($c['third_party'])) $out .= '<tr><td style="padding:8px;border:1px solid #ddd"><strong>'.self::t('pc_third_party').'</strong></td><td style="padding:8px;border:1px solid #ddd">'.esc_html($c['third_party']).'</td></tr>';
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

        $out = '<p>'.sprintf(self::t('rights_intro'), '<strong>'.esc_html($n).'</strong>').'</p><ul>';
        if ($e) $out .= '<li>'.sprintf(self::t('rights_email'), '<a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>').'</li>';
        if ($a) $out .= '<li>'.self::t('rights_mail').' <strong>'.esc_html($n).'</strong>, '.esc_html(str_replace("\n",', ',$a)).'</li>';
        $out .= '</ul><p>'.self::t('rights_list').'</p>';
        $out .= '<p>'.sprintf(self::t('rights_dpa'), '<strong>'.$dpa.'</strong>', '<a href="'.esc_url($dpa_url).'" target="_blank">'.$dpa_url.'</a>').'</p>';
        return $out;
    }

    public static function rgpd_cookies()
    {
        return '<ul><li><strong>Google Analytics</strong> : _ga, _gid</li><li><strong>WordPress</strong> : wordpress_*, wp-settings-*</li></ul>';
    }

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

        ob_start();
        echo '<h2>'.self::t('ml_editor').'</h2>';
        echo '<p><strong>'.esc_html($n).'</strong>';
        if ($forme_display) echo '<br>'.esc_html($forme_display);
        if ($cap) echo ' au capital de '.esc_html($cap);
        echo '<br>'.nl2br(esc_html($a));
        if ($e) echo '<br>'.self::t('email').' : <a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>';
        if ($t) echo '<br>'.self::t('tel').' : '.esc_html($t);
        if ($siret) echo '<br>SIRET : '.esc_html($siret);
        if ($rcs) echo '<br>RCS : '.esc_html($rcs);
        if ($tva) echo '<br>TVA : '.esc_html($tva);
        echo '</p>';
        if ($r) echo '<p><strong>'.self::t('ml_responsible').'</strong> '.esc_html($r).'</p>';

        echo '<h2>'.self::t('ml_hosting').'</h2>';
        echo self::matrys_block();

        echo '<h2>'.self::t('ml_intellectual').'</h2><p>'.self::t('ml_intellectual_text').'</p>';
        echo '<h2>'.self::t('ml_info').'</h2><p>'.self::t('ml_info_text').'</p>';
        echo '<h2>'.self::t('ml_personal_data').'</h2><p>'.self::t('ml_personal_data_text').'</p>';

        return ob_get_clean();
    }

    public static function politique_confidentialite()
    {
        $n = get_option('client_raison_sociale') ?: get_bloginfo('name');
        $rgpd = get_option('rgpd_settings', []);
        $forms = self::get_forms();
        $has = false;
        foreach ($forms as $id => $f) if (!empty($rgpd['forms'][$id]['enabled'])) $has = true;

        ob_start();
        echo '<p>'.sprintf(self::t('pc_intro'), '<strong>'.esc_html($n).'</strong>', '<a href="'.home_url().'">'.home_url().'</a>').'</p>';
        echo '<h2>'.self::t('pc_data_collected').'</h2><p>'.self::t('pc_data_text').'</p>';
        if ($has) { echo '<h2>'.self::t('pc_treatments').'</h2>'; echo self::rgpd_mentions([]); }
        echo '<h2>'.self::t('rights_title').'</h2>'; echo self::rgpd_droits();
        echo '<h2>'.self::t('cookies_title').'</h2><p>'.self::t('cookies_text').'</p>'; echo self::rgpd_cookies();
        return ob_get_clean();
    }

    private static function t($k)
    {
        $l = self::lang();
        return CCRGPD_Constants::TRANSLATIONS[$l][$k] ?? CCRGPD_Constants::TRANSLATIONS['fr_FR'][$k] ?? $k;
    }

    private static function lang()
    {
        $loc = get_locale();
        if (isset(CCRGPD_Constants::TRANSLATIONS[$loc])) return $loc;
        return (substr($loc, 0, 2) === 'en') ? 'en_US' : 'fr_FR';
    }

    private static function opt($k)
    {
        $v = get_option($k);
        return ($v !== false && $v !== '') ? $v : (CCRGPD_Constants::DEFAULT_OPTIONS[$k] ?? '');
    }

    public static function get_forms()
    {
        if (!class_exists('Forminator_API')) return [];
        $forms = [];
        $all = Forminator_API::get_forms(null, 1, 100, 'publish');
        if (empty($all)) return [];
        foreach ($all as $form) {
            $fields = [];
            foreach ($form->settings['wrappers'] ?? [] as $w) {
                foreach ($w['fields'] ?? [] as $f) {
                    $t = $f['type'] ?? 'text';
                    if (!in_array($t, $fields)) $fields[] = $t;
                }
            }
            $forms[$form->id] = ['name' => $form->settings['formName'] ?? 'Formulaire #'.$form->id, 'fields' => $fields];
        }
        return $forms;
    }
}
