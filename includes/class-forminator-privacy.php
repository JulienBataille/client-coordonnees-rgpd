<?php
/**
 * Lecture des réglages de confidentialité de Forminator (lecture seule)
 *
 * Options lues (Forminator 1.57) :
 * - forminator_retain_submissions_interval_number / _unit : conservation globale des soumissions (0 = sans limite)
 * - forminator_retain_ip_interval_number / _unit : anonymisation de l'adresse IP (global)
 * - forminator_form_privacy_settings[ID] : surcharge propre à un formulaire
 *   (submissions_retention_number / submissions_retention_unit). Un formulaire surchargé est exclu
 *   du nettoyage global : une surcharge à 0 signifie donc « sans limite », même si le global est réglé.
 * - settings['store_submissions'] du formulaire : vide = soumissions non stockées en base
 */
defined('ABSPATH') || exit;

class CCRGPD_Forminator_Privacy
{
    /** Équivalence en jours, pour comparer avec les durées déclarées */
    private const UNIT_DAYS = [
        'days' => 1,
        'weeks' => 7,
        'months' => 30,
        'years' => 365,
    ];

    /** Durées déclarées dans le plugin, en jours (null = non comparable) */
    public const DECLARED_DAYS = [
        '6_months' => 180,
        '1_year' => 365,
        '2_years' => 730,
        '3_years' => 1095,
        '5_years' => 1825,
    ];

    public static function is_active()
    {
        return class_exists('Forminator_API');
    }

    /**
     * Normalise une durée Forminator
     *
     * @return array ['number' => int, 'unit' => string, 'forever' => bool, 'days' => int|null]
     */
    public static function normalize($number, $unit)
    {
        $number = (int) $number;
        $unit = (string) $unit;
        $valid = isset(self::UNIT_DAYS[$unit]);
        // Même règle que Forminator (get_retain_time) : nombre <= 0 ou unité inconnue = aucune suppression
        $forever = $number <= 0 || !$valid;

        return [
            'number' => $forever ? 0 : $number,
            'unit' => $valid ? $unit : 'days',
            'forever' => $forever,
            'days' => $forever ? null : $number * self::UNIT_DAYS[$unit],
        ];
    }

    /**
     * Durée globale
     *
     * @param string $kind submissions | ip
     */
    public static function global_retention($kind = 'submissions')
    {
        $prefix = $kind === 'ip' ? 'forminator_retain_ip_interval' : 'forminator_retain_submissions_interval';
        return self::normalize(get_option($prefix . '_number', 0), get_option($prefix . '_unit', 'days'));
    }

    /**
     * Réglages effectifs d'un formulaire
     *
     * @param int   $form_id  ID du formulaire Forminator
     * @param array $settings Réglages du formulaire (Forminator_Form_Model->settings)
     * @return array ['stored' => bool, 'source' => 'global'|'form', 'retention' => array, 'ip' => array]
     */
    public static function for_form($form_id, $settings = [])
    {
        $settings = is_array($settings) ? $settings : [];
        $stored = isset($settings['store_submissions'])
            ? filter_var($settings['store_submissions'], FILTER_VALIDATE_BOOLEAN)
            : true;

        $overrides = get_option('forminator_form_privacy_settings', []);
        $override = null;
        if (is_array($overrides)) {
            $override = $overrides[$form_id] ?? ($overrides[(string) $form_id] ?? null);
        }

        if (is_array($override) && array_key_exists('submissions_retention_number', $override)) {
            $retention = self::normalize($override['submissions_retention_number'], $override['submissions_retention_unit'] ?? 'days');
            $source = 'form';
        } else {
            $retention = self::global_retention('submissions');
            $source = 'global';
        }

        return [
            'stored' => $stored,
            'source' => $source,
            'retention' => $retention,
            'ip' => self::global_retention('ip'),
        ];
    }

    /**
     * Libellé d'une durée (« 2 ans », « 6 mois », « 3 semaines », « 30 jours »)
     *
     * @param array $retention Durée normalisée
     * @param string $lang fr_FR | en_US
     */
    public static function label($retention, $lang = 'fr_FR')
    {
        if (!empty($retention['forever'])) {
            return $lang === 'en_US' ? 'no limit' : 'sans limite';
        }

        $n = (int) $retention['number'];
        $units = [
            'fr_FR' => [
                'days' => ['jour', 'jours'],
                'weeks' => ['semaine', 'semaines'],
                'months' => ['mois', 'mois'],
                'years' => ['an', 'ans'],
            ],
            'en_US' => [
                'days' => ['day', 'days'],
                'weeks' => ['week', 'weeks'],
                'months' => ['month', 'months'],
                'years' => ['year', 'years'],
            ],
        ];
        $set = $units[$lang] ?? $units['fr_FR'];
        $forms = $set[$retention['unit']] ?? $set['days'];

        return $n . ' ' . ($n > 1 ? $forms[1] : $forms[0]);
    }

    /**
     * Le formulaire utilise-t-il Cloudflare Turnstile ?
     * Champ captcha dont le fournisseur est « turnstile » (Forminator retient « recaptcha » par défaut
     * quand la propriété est absente) et clé de site renseignée.
     *
     * @param array $fields Champs Forminator (objets avec ->raw)
     */
    public static function uses_turnstile($fields)
    {
        if (!get_option('forminator_turnstile_key', '')) {
            return false;
        }
        foreach ((array) $fields as $field) {
            $raw = is_object($field) ? ($field->raw ?? []) : (array) $field;
            $type = is_object($field) ? ($field->type ?? ($raw['type'] ?? '')) : ($raw['type'] ?? '');
            if ($type === 'captcha' && ($raw['captcha_provider'] ?? 'recaptcha') === 'turnstile') {
                return true;
            }
        }
        return false;
    }
}
