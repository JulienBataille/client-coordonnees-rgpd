<?php
/**
 * Analyseur intelligent des champs de formulaires
 * Détecte les catégories RGPD à partir des libellés
 */
defined('ABSPATH') || exit;

class CCRGPD_Form_Analyzer
{
    /**
     * Mapping des mots-clés vers les catégories RGPD
     * L'ordre est important : les catégories plus spécifiques sont testées en premier
     */
    private const CATEGORIES = [
        'documents' => [
            'label' => 'Documents officiels',
            'keywords' => ['permis', 'conduire', 'carte identite', 'carte d\'identite', 'passeport', 'piece identite', 'pièce identité', 'justificatif', 'attestation', 'certificat', 'diplome', 'diplôme', 'licence'],
            'icon' => '📄',
        ],
        'entreprise' => [
            'label' => 'Données professionnelles',
            'keywords' => ['societe', 'société', 'entreprise', 'company', 'siret', 'siren', 'tva', 'fonction', 'poste', 'job', 'service', 'departement', 'professionnel', 'professionnelle', 'employeur'],
            'icon' => '🏢',
        ],
        'naissance' => [
            'label' => 'Date de naissance',
            'keywords' => ['naissance', 'birth', 'anniversaire', 'age', 'âge', 'date de naissance', 'né le', 'née le'],
            'icon' => '🎂',
        ],
        // Catégorie large qui regroupe identité + coordonnées
        'identite' => [
            'label' => 'Identité et coordonnées',
            'keywords' => [
                // Identité (attention: "nom" seul match "nom d'utilisateur", donc on utilise des termes plus spécifiques)
                'prenom', 'prénom', 'firstname', 'lastname', 'nom de famille', 'civilite', 'civilité', 'genre', 'sexe', 'mme', 'mr', 'monsieur', 'madame', 'famille',
                // Email
                'email', 'mail', 'courriel', 'e-mail',
                // Téléphone
                'telephone', 'téléphone', 'phone', 'tel', 'mobile', 'portable', 'fax', 'gsm',
                // Adresse
                'adresse', 'address', 'rue', 'street', 'ville', 'city', 'code postal', 'cp', 'zip', 'pays', 'country', 'region', 'région', 'cedex', 'postale'
            ],
            'icon' => '👤',
        ],
    ];

    /**
     * Mots-clés à ignorer (champs non pertinents pour les mentions légales)
     */
    private const IGNORED_KEYWORDS = [
        'message', 'commentaire', 'comment', 'sujet', 'objet', 'subject', 'question', 
        'demande', 'description', 'detail', 'détail', 'information', 'info',
        'consentement', 'rgpd', 'gdpr', 'accepte', 'accept', 'conditions', 'cgu', 'declaration', 'déclaration',
        'captcha', 'recaptcha', 'securite', 'sécurité', 'verification', 'vérification',
        'fichier', 'file', 'attachment', 'piece jointe', 'pièce jointe', 'upload', 'telecharger',
        'newsletter', 'inscription', 'abonnement', 'submit', 'envoyer', 'send', 'inscrire',
        'hidden', 'honeypot', 'page', 'url', 'site',
        // Données de connexion (anonymes ou hashées)
        'mot de passe', 'password', 'login', 'identifiant', 'username', 'pseudo', 'utilisateur',
        // Contenu contextuel
        'projet', 'titre',
    ];

    /**
     * Types de blocs/champs à ignorer par défaut
     */
    private const IGNORED_TYPES = [
        'submit', 'captcha', 'consent', 'gdpr', 'hidden', 'page-break', 
        'section', 'html', 'divider', 'separator', 'step', 'group',
        'password', // Hashé, pas une donnée personnelle
    ];

    /**
     * Analyse un ensemble de champs et retourne les catégories RGPD détectées
     * 
     * @param array $fields Tableau de champs avec 'label' et 'type'
     * @return array ['categories' => [...], 'unrecognized' => [...], 'ignored' => [...]]
     */
    public static function analyze($fields)
    {
        $result = [
            'categories' => [],  // Catégories détectées avec leurs champs
            'unrecognized' => [], // Champs non reconnus (à signaler)
            'ignored' => [],      // Champs ignorés (message, consentement, etc.)
        ];

        foreach ($fields as $field) {
            $label = $field['label'] ?? '';
            $type = $field['type'] ?? '';
            
            // Ignorer les types de champs non pertinents
            if (self::isIgnoredType($type)) {
                $result['ignored'][] = $field;
                continue;
            }
            
            // Ignorer les champs avec des mots-clés non pertinents
            if (self::isIgnoredLabel($label)) {
                $result['ignored'][] = $field;
                continue;
            }
            
            // Détecter la catégorie
            $category = self::detectCategory($label, $type);
            
            if ($category) {
                if (!isset($result['categories'][$category])) {
                    $result['categories'][$category] = [
                        'info' => self::CATEGORIES[$category],
                        'fields' => [],
                    ];
                }
                // Stocker le label tel quel (pas le type !)
                $fieldLabel = $label ?: self::getDefaultLabelForType($type);
                if (!in_array($fieldLabel, $result['categories'][$category]['fields'])) {
                    $result['categories'][$category]['fields'][] = $fieldLabel;
                }
            } else {
                // Champ non reconnu
                $result['unrecognized'][] = $field;
            }
        }
        
        return $result;
    }

    /**
     * Retourne un label par défaut pour un type de champ
     */
    private static function getDefaultLabelForType($type)
    {
        $defaults = [
            'email' => 'email',
            'phone' => 'téléphone',
            'tel' => 'téléphone',
            'name' => 'nom',
            'address' => 'adresse',
            'date' => 'date',
            'text' => 'texte',
            'textarea' => 'texte',
            'password' => 'mot de passe',
            'upload' => 'fichier téléversé',
        ];
        return $defaults[strtolower($type)] ?? $type;
    }

    /**
     * Détecte la catégorie d'un champ à partir de son libellé et type
     */
    private static function detectCategory($label, $type)
    {
        $normalized = self::normalize($label);
        $normalizedType = self::normalize($type);
        
        // Parcourir les catégories dans l'ordre
        foreach (self::CATEGORIES as $catKey => $catData) {
            foreach ($catData['keywords'] as $keyword) {
                $normalizedKeyword = self::normalize($keyword);
                
                // Vérifier dans le libellé
                if ($normalized && strpos($normalized, $normalizedKeyword) !== false) {
                    return $catKey;
                }
            }
        }
        
        // Fallback : détecter par type de champ standard
        return self::detectByType($type);
    }

    /**
     * Détection par type de champ (fallback)
     */
    private static function detectByType($type)
    {
        $typeMap = [
            'email' => 'identite',
            'phone' => 'identite',
            'tel' => 'identite',
            'name' => 'identite',
            'address' => 'identite',
            'text' => null, // Trop générique
            'textarea' => null,
            'date' => null, // Pourrait être naissance ou autre
            'number' => null,
            'select' => null,
            'radio' => null,
            'checkbox' => null,
            'password' => null, // Hashé, pas une donnée personnelle
            'upload' => null, // Trop générique
        ];
        
        $normalizedType = strtolower(trim($type));
        return $typeMap[$normalizedType] ?? null;
    }

    /**
     * Vérifie si un type de champ doit être ignoré
     */
    private static function isIgnoredType($type)
    {
        $normalizedType = self::normalize($type);
        foreach (self::IGNORED_TYPES as $ignored) {
            if (strpos($normalizedType, self::normalize($ignored)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si un libellé contient des mots-clés à ignorer
     */
    private static function isIgnoredLabel($label)
    {
        $normalized = self::normalize($label);
        if (empty($normalized)) return false;
        
        foreach (self::IGNORED_KEYWORDS as $ignored) {
            if (strpos($normalized, self::normalize($ignored)) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalise une chaîne pour la comparaison
     * Minuscules, sans accents, sans caractères spéciaux
     */
    private static function normalize($str)
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        
        // Supprimer les accents
        $accents = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ç' => 'c', 'ñ' => 'n',
        ];
        $str = strtr($str, $accents);
        
        // Garder seulement lettres, chiffres et espaces
        $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        
        return trim($str);
    }

    /**
     * Génère le texte pour les mentions légales
     * Format liste avec catégories en gras et retours à la ligne
     */
    public static function generateLegalText($analysis)
    {
        if (empty($analysis['categories'])) {
            return '';
        }

        $lines = [];
        foreach ($analysis['categories'] as $catKey => $catData) {
            $label = $catData['info']['label'];
            
            // Nettoyer et simplifier les labels des champs
            $fieldLabels = self::simplifyFieldLabels($catData['fields']);
            
            // Format : "**Catégorie** : champ1, champ2"
            $lines[] = '<strong>' . esc_html($label) . '</strong> : ' . esc_html(implode(', ', $fieldLabels));
        }

        return implode('<br>', $lines);
    }
    
    /**
     * Simplifie les labels de champs pour éviter les redondances
     * Ex: "adresse postale, ville, code postal" → "adresse postale"
     */
    private static function simplifyFieldLabels($fields)
    {
        $simplified = [];
        $hasAddress = false;
        $hasName = false;
        
        // Mots-clés pour regrouper
        $addressKeywords = ['adresse', 'ville', 'city', 'code postal', 'cp', 'zip', 'pays', 'country', 'rue', 'street', 'postale'];
        $nameKeywords = ['prénom', 'prenom', 'nom de famille', 'firstname', 'lastname'];
        
        foreach ($fields as $field) {
            $lower = mb_strtolower(trim($field), 'UTF-8');
            
            // Vérifier si c'est un champ d'adresse
            $isAddress = false;
            foreach ($addressKeywords as $kw) {
                if (strpos($lower, $kw) !== false) {
                    $isAddress = true;
                    break;
                }
            }
            if ($isAddress) {
                if (!$hasAddress) {
                    $simplified[] = 'adresse postale';
                    $hasAddress = true;
                }
                continue;
            }
            
            // Vérifier si c'est un champ nom/prénom
            $isName = false;
            
            // Cas spécial : "nom" exactement (mais pas "nom d'utilisateur", "nom de société", etc.)
            if ($lower === 'nom') {
                $isName = true;
            }
            
            // Sinon vérifier les autres keywords
            if (!$isName) {
                foreach ($nameKeywords as $kw) {
                    if (strpos($lower, $kw) !== false) {
                        $isName = true;
                        break;
                    }
                }
            }
            
            if ($isName) {
                if (!$hasName) {
                    $simplified[] = 'nom et prénom';
                    $hasName = true;
                }
                continue;
            }
            
            // Sinon garder tel quel
            $simplified[] = $lower;
        }
        
        return array_unique($simplified);
    }

    /**
     * Génère le HTML pour l'affichage admin
     */
    public static function generateAdminHtml($analysis)
    {
        $html = '<div class="rgpd-analysis">';
        
        if (!empty($analysis['categories'])) {
            $html .= '<div class="categories-detected">';
            foreach ($analysis['categories'] as $catKey => $catData) {
                $icon = $catData['info']['icon'];
                $label = $catData['info']['label'];
                $fields = implode(', ', $catData['fields']);
                $html .= '<span class="category-tag" title="' . esc_attr($fields) . '">';
                $html .= $icon . ' ' . esc_html($label);
                $html .= '</span> ';
            }
            $html .= '</div>';
        }
        
        if (!empty($analysis['unrecognized'])) {
            $html .= '<div class="unrecognized-warning">';
            $labels = array_map(function($f) { 
                return $f['label'] ?: $f['type']; 
            }, $analysis['unrecognized']);
            $html .= '⚠️ Non catégorisés : ' . esc_html(implode(', ', $labels));
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Retourne les catégories disponibles (pour référence)
     */
    public static function getCategories()
    {
        return self::CATEGORIES;
    }
}
