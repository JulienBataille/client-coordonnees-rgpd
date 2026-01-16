<?php
defined('ABSPATH') || exit;

class CCRGPD_API_Entreprises
{
    private const API_URL = 'https://recherche-entreprises.api.gouv.fr/search';
    
    // Codes nature juridique des associations et fondations (pas de RCS)
    private const ASSOCIATIONS = ['9210', '9220', '9221', '9222', '9223', '9224', '9230', '9240', '9260', '9300'];

    public static function search($siret)
    {
        $siret = preg_replace('/[^0-9]/', '', $siret);
        if (strlen($siret) < 9) {
            return new WP_Error('invalid', 'SIRET invalide (minimum 9 chiffres)');
        }

        $siren = substr($siret, 0, 9);
        $response = wp_remote_get(self::API_URL . '?q=' . $siren, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json']
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['results'])) {
            return new WP_Error('not_found', 'Aucune entreprise trouvée pour ce numéro');
        }

        return self::format($data['results'][0]);
    }

    private static function format($e)
    {
        $siege = $e['siege'] ?? [];
        $siren = $e['siren'] ?? '';
        $cp = $siege['code_postal'] ?? '';
        $nature = $e['nature_juridique'] ?? '';
        
        // Détecter si c'est une association (pas de RCS, TVA généralement non applicable)
        $is_association = in_array($nature, self::ASSOCIATIONS) || substr($nature, 0, 2) === '92';

        $result = [
            'raison_sociale' => $e['nom_raison_sociale'] ?? '',
            'adresse_siege' => $siege['adresse'] ?? '',
            'siret' => $siege['siret'] ?? '',
            'siren' => $siren,
            'forme_juridique' => CCRGPD_Constants::NATURE_JURIDIQUE[$nature] ?? 'Autre',
            'tva' => $is_association ? '' : self::calc_tva($siren),  // Pas de TVA par défaut pour les associations
            'rcs' => $is_association ? '' : self::format_rcs($siren, $cp),  // Pas de RCS pour les associations
            'is_association' => $is_association,
            'dirigeants' => [],
            'annuaire_url' => 'https://annuaire-entreprises.data.gouv.fr/entreprise/' . $siren,
        ];

        foreach ($e['dirigeants'] ?? [] as $d) {
            if (($d['type_dirigeant'] ?? '') === 'personne physique') {
                $result['dirigeants'][] = [
                    'full_name' => trim(($d['prenoms'] ?? '') . ' ' . ($d['nom'] ?? '')),
                    'qualite' => $d['qualite'] ?? '',
                ];
            }
        }

        return $result;
    }

    public static function calc_tva($siren)
    {
        $siren = preg_replace('/[^0-9]/', '', $siren);
        if (strlen($siren) !== 9) return '';
        $key = (12 + 3 * (intval($siren) % 97)) % 97;
        return 'FR' . str_pad($key, 2, '0', STR_PAD_LEFT) . $siren;
    }

    public static function format_siren($siren)
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})/', '$1 $2 $3', preg_replace('/[^0-9]/', '', $siren));
    }

    public static function format_rcs($siren, $cp)
    {
        $dept = substr($cp, 0, 2);
        if ($dept === '97' || $dept === '98') $dept = substr($cp, 0, 3);
        $greffe = CCRGPD_Constants::GREFFES[$dept] ?? '';
        return self::format_siren($siren) . ' R.C.S ' . $greffe;
    }
}
