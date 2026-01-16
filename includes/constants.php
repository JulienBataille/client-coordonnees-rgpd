<?php
defined('ABSPATH') || exit;

class CCRGPD_Constants
{
    public const MENU_SLUG = 'coordonnees-client';
    public const OPTION_GROUP = 'coordonnees_client_group';
    public const OPTION_GROUP_RGPD = 'coordonnees_rgpd_group';

    public const COUNTRIES = [
        'FR' => ['name' => 'France', 'code' => '+33'],
        'BE' => ['name' => 'Belgique', 'code' => '+32'],
        'CH' => ['name' => 'Suisse', 'code' => '+41'],
        'LU' => ['name' => 'Luxembourg', 'code' => '+352'],
        'DE' => ['name' => 'Allemagne', 'code' => '+49'],
        'ES' => ['name' => 'Espagne', 'code' => '+34'],
        'IT' => ['name' => 'Italie', 'code' => '+39'],
        'GB' => ['name' => 'Royaume-Uni', 'code' => '+44'],
    ];

    public const FORMES_JURIDIQUES = [
        '' => '-- Sélectionner --', 'EI' => 'Entrepreneur individuel', 'EIRL' => 'EIRL',
        'EURL' => 'EURL', 'SARL' => 'SARL', 'SAS' => 'SAS', 'SASU' => 'SASU', 'SA' => 'SA',
        'SCI' => 'SCI', 'SNC' => 'SNC', 'SCOP' => 'SCOP', 'Association' => 'Association loi 1901', 'Autre' => 'Autre',
    ];

    public const NATURE_JURIDIQUE = [
        '1000'=>'EI','5485'=>'EURL','5499'=>'SARL','5710'=>'SAS','5785'=>'SASU',
        '5505'=>'SA','6540'=>'SCI','5308'=>'SNC','9210'=>'Association',
    ];

    public const GREFFES = [
        '01'=>'Bourg-en-Bresse','02'=>'Saint-Quentin','03'=>'Cusset','04'=>'Manosque','05'=>'Gap',
        '06'=>'Nice','07'=>'Aubenas','08'=>'Sedan','09'=>'Foix','10'=>'Troyes','11'=>'Narbonne',
        '12'=>'Rodez','13'=>'Marseille','14'=>'Caen','15'=>'Aurillac','16'=>'Angoulême',
        '17'=>'La Rochelle','18'=>'Bourges','19'=>'Brive-la-Gaillarde','2A'=>'Ajaccio','2B'=>'Bastia',
        '21'=>'Dijon','22'=>'Saint-Brieuc','23'=>'Guéret','24'=>'Périgueux','25'=>'Besançon',
        '26'=>'Valence','27'=>'Évreux','28'=>'Chartres','29'=>'Quimper','30'=>'Nîmes',
        '31'=>'Toulouse','32'=>'Auch','33'=>'Bordeaux','34'=>'Montpellier','35'=>'Rennes',
        '36'=>'Châteauroux','37'=>'Tours','38'=>'Grenoble','39'=>'Lons-le-Saunier','40'=>'Dax',
        '41'=>'Blois','42'=>'Saint-Étienne','43'=>'Le Puy-en-Velay','44'=>'Nantes','45'=>'Orléans',
        '46'=>'Cahors','47'=>'Agen','48'=>'Mende','49'=>'Angers','50'=>'Coutances',
        '51'=>'Châlons-en-Champagne','52'=>'Chaumont','53'=>'Laval','54'=>'Nancy','55'=>'Bar-le-Duc',
        '56'=>'Vannes','57'=>'Metz','58'=>'Nevers','59'=>'Lille','60'=>'Beauvais',
        '61'=>'Alençon','62'=>'Arras','63'=>'Clermont-Ferrand','64'=>'Pau','65'=>'Tarbes',
        '66'=>'Perpignan','67'=>'Strasbourg','68'=>'Mulhouse','69'=>'Lyon','70'=>'Vesoul',
        '71'=>'Mâcon','72'=>'Le Mans','73'=>'Chambéry','74'=>'Annecy','75'=>'Paris',
        '76'=>'Rouen','77'=>'Meaux','78'=>'Versailles','79'=>'Niort','80'=>'Amiens',
        '81'=>'Albi','82'=>'Montauban','83'=>'Fréjus','84'=>'Avignon','85'=>'La Roche-sur-Yon',
        '86'=>'Poitiers','87'=>'Limoges','88'=>'Épinal','89'=>'Auxerre','90'=>'Belfort',
        '91'=>'Évry','92'=>'Nanterre','93'=>'Bobigny','94'=>'Créteil','95'=>'Pontoise',
        '971'=>'Pointe-à-Pitre','972'=>'Fort-de-France','973'=>'Cayenne','974'=>'Saint-Denis','976'=>'Mamoudzou',
    ];

    public const DEFAULT_OPTIONS = [
        'matrys_name' => 'Agence de communication MATRYS',
        'matrys_url' => 'https://matrys.fr',
        'matrys_address' => "28 rue Victor Hugo\n40400 Tartas",
        'matrys_tel' => '05 58 73 41 57',
        'matrys_country' => 'FR',
    ];

    public const FIELD_TYPES = [
        'name'=>'Nom, prénom','email'=>'Email','phone'=>'Téléphone','address'=>'Adresse',
        'textarea'=>'Message','text'=>'Texte','number'=>'Nombre','date'=>'Date',
        'upload'=>'Fichier','select'=>'Liste','checkbox'=>'Cases','consent'=>'Consentement',
    ];

    public const RETENTION = [
        '6_months'=>'6 mois','1_year'=>'1 an','2_years'=>'2 ans','3_years'=>'3 ans',
        '5_years'=>'5 ans','until_unsubscribe'=>'Jusqu\'à désinscription','contract_plus_3'=>'Contrat + 3 ans',
    ];

    public const LEGAL_BASIS = [
        'consent'=>'Consentement','contract'=>'Exécution d\'un contrat',
        'legal_obligation'=>'Obligation légale','legitimate_interest'=>'Intérêt légitime',
    ];

    public const TRANSLATIONS = [
        'fr_FR' => [
            'dpa_name'=>'CNIL','dpa_url'=>'https://www.cnil.fr',
            'ml_editor'=>'Éditeur du site','ml_responsible'=>'Responsable de la publication :',
            'ml_hosting'=>'Réalisation et hébergement','ml_intellectual'=>'Propriété intellectuelle',
            'ml_intellectual_text'=>'Le contenu de ce site est protégé par le droit d\'auteur.',
            'ml_info'=>'Informations','ml_info_text'=>'L\'éditeur n\'est pas responsable de l\'utilisation des informations.',
            'ml_personal_data'=>'Données personnelles','ml_personal_data_text'=>'Les traitements sont conformes au RGPD.',
            'pc_intro'=>'%s s\'engage à la conformité RGPD sur %s.',
            'pc_data_collected'=>'Données collectées','pc_data_text'=>'Nous collectons uniquement les données nécessaires.',
            'pc_treatments'=>'Traitements de données',
            'pc_table_data'=>'Données','pc_table_purpose'=>'Finalité','pc_table_legal'=>'Base légale','pc_table_retention'=>'Conservation',
            'pc_recipients'=>'Destinataires :','pc_third_party'=>'Sous-traitants :',
            'rights_title'=>'Exercer vos droits','rights_intro'=>'Pour exercer vos droits auprès de %s :',
            'rights_email'=>'par email à %s','rights_mail'=>'par courrier à',
            'rights_list'=>'Droits : accès, rectification, effacement, limitation, portabilité, opposition.',
            'rights_dpa'=>'Réclamation possible auprès de la %s : %s',
            'cookies_title'=>'Cookies','cookies_text'=>'Ce site utilise des cookies.',
            'email'=>'Email','tel'=>'Tél',
        ],
        'en_US' => [
            'dpa_name'=>'ICO','dpa_url'=>'https://ico.org.uk',
            'ml_editor'=>'Publisher','ml_responsible'=>'Publication manager:',
            'ml_hosting'=>'Design and hosting','ml_intellectual'=>'Intellectual property',
            'ml_intellectual_text'=>'Content is protected by copyright.',
            'ml_info'=>'Information','ml_info_text'=>'Publisher is not responsible for use of information.',
            'ml_personal_data'=>'Personal data','ml_personal_data_text'=>'Processing complies with GDPR.',
            'pc_intro'=>'%s is committed to GDPR compliance on %s.',
            'pc_data_collected'=>'Data collected','pc_data_text'=>'We only collect necessary data.',
            'pc_treatments'=>'Data processing',
            'pc_table_data'=>'Data','pc_table_purpose'=>'Purpose','pc_table_legal'=>'Legal basis','pc_table_retention'=>'Retention',
            'pc_recipients'=>'Recipients:','pc_third_party'=>'Subcontractors:',
            'rights_title'=>'Exercise your rights','rights_intro'=>'To exercise your rights with %s:',
            'rights_email'=>'by email at %s','rights_mail'=>'by mail at',
            'rights_list'=>'Rights: access, rectification, erasure, restriction, portability, objection.',
            'rights_dpa'=>'You may lodge a complaint with the %s: %s',
            'cookies_title'=>'Cookies','cookies_text'=>'This site uses cookies.',
            'email'=>'Email','tel'=>'Phone',
        ],
    ];
}
