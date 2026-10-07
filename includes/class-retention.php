<?php
/**
 * Durée de conservation affichée dans la politique pour un formulaire,
 * avec contrôle de cohérence par rapport aux réglages Forminator.
 */
defined('ABSPATH') || exit;

class CCRGPD_Retention
{
    /**
     * @param array $form   Entrée de CCRGPD_Shortcodes::get_all_forms()
     * @param array $config Configuration enregistrée (rgpd_settings['forms'][id])
     * @return array [
     *   'key'    => clé retenue (forminator, 3_years...),
     *   'label'  => texte affiché dans la politique,
     *   'source' => forminator | manual | default,
     *   'alerts' => [['level' => error|warning|info, 'message' => string], ...],
     * ]
     */
    public static function resolve($form, $config = [])
    {
        $lang = CCRGPD_Shortcodes::get_lang();
        $suggestions = CCRGPD_Form_Analyzer::getSuggestions($form['name'] ?? '');
        $privacy = $form['privacy'] ?? null; // renseigné pour Forminator uniquement
        $fallback_key = isset(CCRGPD_Constants::RETENTION[$suggestions['retention']]) ? $suggestions['retention'] : '3_years';
        $fallback = CCRGPD_Constants::RETENTION[$fallback_key];

        $key = ($config['retention'] ?? '') ?: ($privacy ? 'forminator' : $fallback_key);
        if ($key === 'forminator' && !$privacy) {
            $key = $fallback_key;
        }

        $alerts = [];
        $source = 'manual';
        $shown_days = CCRGPD_Forminator_Privacy::DECLARED_DAYS[$fallback_key] ?? null; // durée affichée, en jours

        if ($key === 'forminator') {
            $ret = $privacy['retention'];
            $where = $privacy['source'] === 'form' ? 'réglage propre au formulaire' : 'réglage global';
            if (!$privacy['stored']) {
                $label = $fallback;
                $source = 'default';
                // durée affichée = durée par défaut ($shown_days déjà calculé)
                $alerts[] = self::alert('info', sprintf('Forminator ne stocke pas les soumissions de ce formulaire : la politique affiche la durée par défaut (%s), qui doit couvrir les e-mails reçus.', $fallback));
            } elseif ($ret['forever']) {
                $label = $fallback;
                $source = 'default';
                $alerts[] = self::alert('error', sprintf('Forminator conserve les soumissions sans limite (%s). La politique affiche %s : réglez une durée dans Forminator.', $where, $fallback));
            } else {
                $label = CCRGPD_Forminator_Privacy::label($ret, $lang);
                $source = 'forminator';
                $shown_days = $ret['days'];
            }
        } else {
            $label = CCRGPD_Constants::RETENTION[$key] ?? $fallback;
            $shown_days = isset(CCRGPD_Constants::RETENTION[$key]) ? (CCRGPD_Forminator_Privacy::DECLARED_DAYS[$key] ?? null) : $shown_days;
            if ($privacy && $privacy['stored']) {
                $ret = $privacy['retention'];
                $declared = CCRGPD_Forminator_Privacy::DECLARED_DAYS[$key] ?? null;
                $fm = CCRGPD_Forminator_Privacy::label($ret);
                if ($ret['forever']) {
                    $alerts[] = self::alert('error', sprintf('Forminator conserve les soumissions sans limite, alors que la politique annonce %s.', $label));
                } elseif ($declared !== null && $ret['days'] > $declared) {
                    $alerts[] = self::alert('warning', sprintf('Forminator conserve les soumissions %s, plus longtemps que la durée annoncée (%s).', $fm, $label));
                } elseif ($declared !== null && $ret['days'] < $declared) {
                    $alerts[] = self::alert('info', sprintf('Forminator supprime les soumissions après %s ; la politique annonce %s (durée globale, e-mails compris).', $fm, $label));
                }
            }
        }

        // Adresse IP : mentionnée quand Forminator l'anonymise avant la fin de la durée affichée
        if ($privacy && $privacy['stored']) {
            $ip = $privacy['ip'];
            if (!$ip['forever'] && ($shown_days === null || $ip['days'] < $shown_days)) {
                $label = sprintf(CCRGPD_Shortcodes::t('pc_retention_ip'), $label, CCRGPD_Forminator_Privacy::label($ip, $lang));
            }
        }

        return [
            'key' => $key,
            'label' => $label,
            'source' => $source,
            'alerts' => $alerts,
        ];
    }

    private static function alert($level, $message)
    {
        return ['level' => $level, 'message' => $message];
    }
}
