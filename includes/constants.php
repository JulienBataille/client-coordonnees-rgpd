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
        '' => '-- Sélectionner --',
        'EI' => 'Entrepreneur individuel (EI)',
        'EIRL' => 'EIRL',
        'EURL' => 'EURL',
        'SARL' => 'SARL',
        'SAS' => 'SAS',
        'SASU' => 'SASU',
        'SA' => 'SA',
        'SCI' => 'SCI',
        'SNC' => 'SNC',
        'SCOP' => 'SCOP',
        'Association' => 'Association loi 1901',
        'Autre' => 'Autre',
    ];

    public const NATURE_JURIDIQUE = [
        '1000'=>'EI','1100'=>'EI','1200'=>'EI','1300'=>'EI',
        '5485'=>'EURL','5499'=>'SARL','5498'=>'SARL',
        '5710'=>'SAS','5720'=>'SAS','5785'=>'SASU',
        '5505'=>'SA','5510'=>'SA','5599'=>'SA',
        '6540'=>'SCI','6541'=>'SCI','6542'=>'SCI',
        '5308'=>'SNC','5203'=>'SCOP','5309'=>'SCOP',
        '9210'=>'Association','9220'=>'Association','9221'=>'Association',
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
        'name' => 'Nom, prénom',
        'email' => 'Adresse email',
        'phone' => 'Numéro de téléphone',
        'address' => 'Adresse postale',
        'textarea' => 'Message / Commentaire',
        'text' => 'Texte libre',
        'number' => 'Données numériques',
        'date' => 'Date',
        'upload' => 'Fichiers téléchargés',
        'select' => 'Choix (liste déroulante)',
        'radio' => 'Choix (boutons radio)',
        'checkbox' => 'Choix multiples (cases à cocher)',
        'consent' => 'Consentement',
        'stripe' => 'Données de paiement',
        'paypal' => 'Données de paiement',
        'signature' => 'Signature électronique',
    ];

    public const RETENTION = [
        '6_months' => '6 mois',
        '1_year' => '1 an',
        '2_years' => '2 ans',
        '3_years' => '3 ans',
        '5_years' => '5 ans',
        'until_unsubscribe' => 'Jusqu\'à désinscription',
        'contract_plus_3' => 'Durée du contrat + 3 ans',
    ];

    public const LEGAL_BASIS = [
        'consent' => 'Consentement',
        'contract' => 'Exécution d\'un contrat',
        'legal_obligation' => 'Obligation légale',
        'legitimate_interest' => 'Intérêt légitime',
    ];

    // TEXTES COMPLETS POUR LES PAGES LÉGALES
    public const TRANSLATIONS = [
        'fr_FR' => [
            // Autorité de protection des données
            'dpa_name' => 'CNIL',
            'dpa_url' => 'https://www.cnil.fr',
            
            // Mentions légales - Éditeur
            'ml_editor' => 'Éditeur du site',
            'ml_editor_intro' => 'Le site %s est édité par :',
            'ml_responsible' => 'Responsable de la publication :',
            
            // Mentions légales - Hébergement
            'ml_hosting' => 'Réalisation et hébergement',
            
            // Mentions légales - Propriété intellectuelle
            'ml_intellectual' => 'Propriété intellectuelle',
            'ml_intellectual_text' => 'Le contenu de ce site internet est protégé par les droits de propriété intellectuelle et notamment par le droit d\'auteur. Toute reproduction de ces contenus est conditionnée à un accord explicite préalable, en vertu de l\'article L.122-4 du Code de la Propriété Intellectuelle.',
            'ml_intellectual_contact' => 'Pour toute demande d\'autorisation ou d\'information, veuillez nous contacter par email : %s.',
            
            // Mentions légales - Informations
            'ml_info' => 'Informations et exclusions',
            'ml_info_text1' => 'L\'éditeur de ce site met en œuvre tous les moyens dont il dispose pour assurer une information fiable et une mise à jour des contenus. Toutefois, des erreurs ou omissions peuvent survenir.',
            'ml_info_text2' => 'L\'éditeur du site n\'est en aucun cas responsable de l\'utilisation faite de ces informations, et de tout préjudice direct ou indirect pouvant en découler. Les photos présentes sur ce site sont non contractuelles.',
            'ml_info_text3' => 'Les liens hypertextes mis en place dans le cadre du présent site internet en direction d\'autres ressources présentes sur le réseau Internet ne sauraient engager la responsabilité de l\'éditeur.',
            
            // Mentions légales - Données personnelles
            'ml_personal_data' => 'Données personnelles',
            'ml_personal_data_text' => 'L\'éditeur de ce site s\'engage à ce que les traitements de données personnelles soient conformes au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.',
            'ml_privacy_link' => 'Pour en savoir plus sur le traitement de vos données personnelles, consultez notre %s.',
            'ml_privacy_page' => 'politique de confidentialité',
            
            // Politique de confidentialité - Introduction
            'pc_intro' => '%s s\'engage à ce que les traitements de données personnelles effectués sur %s soient conformes au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.',
            
            // Politique de confidentialité - Données collectées (TEXTES COMPLETS)
            'pc_data_collected' => 'Données collectées',
            'pc_data_text1' => 'Nous nous engageons à ne collecter que le minimum de données nécessaires au bon fonctionnement du service fourni par ce site internet. Le caractère obligatoire ou facultatif des données collectées vous est signalé au moment de leur saisie.',
            'pc_data_text2' => 'Certaines données sont collectées automatiquement du fait de vos actions sur le site afin d\'effectuer des mesures d\'audience ou sont nécessaires à la prévention et la résolution d\'incidents techniques.',
            'pc_data_text3' => 'Les données sont conservées pendant toute la durée d\'utilisation du service puis sont archivées pour une durée supplémentaire en lien avec les durées de prescription et de conservation légale.',
            
            // Politique de confidentialité - Traitements
            'pc_treatments' => 'Traitements de données personnelles',
            'pc_table_data' => 'Données collectées',
            'pc_table_purpose' => 'Finalité',
            'pc_table_legal' => 'Base légale',
            'pc_table_retention' => 'Durée de conservation',
            'pc_recipients' => 'Destinataires des données :',
            'pc_third_party' => 'Sous-traitants :',
            
            // Droits
            'rights_title' => 'Exercer vos droits',
            'rights_intro' => 'Pour toute information ou exercice de vos droits Informatique et Libertés sur les traitements de données personnelles gérés par %s, vous pouvez nous contacter :',
            'rights_email' => 'par email à %s',
            'rights_mail' => 'par courrier à',
            'rights_list' => 'Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez des droits suivants sur vos données : droit d\'accès, droit de rectification, droit d\'effacement (droit à l\'oubli), droit de limitation du traitement, droit à la portabilité des données, droit d\'opposition.',
            'rights_dpa' => 'Vous pouvez également introduire une réclamation auprès de la %s : %s',
            
            // Cookies
            'cookies_title' => 'Cookies',
            'cookies_info_title' => 'Informations sur les cookies',
            'cookies_info_text' => 'Pour des besoins de statistiques et d\'affichage, le présent site utilise des cookies. Il s\'agit de petits fichiers textes stockés sur votre disque dur afin d\'enregistrer des données techniques sur votre navigation.',
            'cookies_purpose_title' => 'Finalité des cookies utilisés',
            'cookies_choices_title' => 'Vos choix concernant les cookies',
            'cookies_choices_text' => 'Vous pouvez configurer votre logiciel de navigation de manière à ce que des cookies soient enregistrés ou rejetés, soit systématiquement, soit selon leur émetteur. Vous pouvez également configurer votre navigateur de manière à ce que l\'acceptation ou le refus des cookies vous soit proposé ponctuellement, avant qu\'un cookie soit susceptible d\'être enregistré.',
            'cookies_manage_text' => 'Vous pouvez gérer vos préférences de cookies à tout moment en utilisant notre gestionnaire de consentement.',
            'cookies_manage_button' => 'Gérer mes préférences cookies',
            'cookies_browser_text' => 'Pour plus d\'informations sur la gestion des cookies selon votre navigateur :',
            
            // Labels
            'email' => 'Email',
            'tel' => 'Tél',
        ],
        
        'en_US' => [
            'dpa_name' => 'ICO',
            'dpa_url' => 'https://ico.org.uk',
            'ml_editor' => 'Website Publisher',
            'ml_editor_intro' => 'The website %s is published by:',
            'ml_responsible' => 'Publication Manager:',
            'ml_hosting' => 'Design and Hosting',
            'ml_intellectual' => 'Intellectual Property',
            'ml_intellectual_text' => 'The content of this website is protected by intellectual property rights, including copyright. Any reproduction of this content requires explicit prior agreement, in accordance with the Intellectual Property Code.',
            'ml_intellectual_contact' => 'For any request for authorization or information, please contact us by email: %s.',
            'ml_info' => 'Information and Disclaimers',
            'ml_info_text1' => 'The publisher of this site uses all available means to ensure reliable information and content updates. However, errors or omissions may occur.',
            'ml_info_text2' => 'The publisher is not responsible for the use made of this information, or any direct or indirect damage that may result. Photos on this site are not contractual.',
            'ml_info_text3' => 'Links to other resources on the Internet do not engage the publisher\'s responsibility.',
            'ml_personal_data' => 'Personal Data',
            'ml_personal_data_text' => 'The publisher is committed to ensuring that personal data processing complies with the GDPR and applicable data protection laws.',
            'ml_privacy_link' => 'For more information about how your data is processed, see our %s.',
            'ml_privacy_page' => 'privacy policy',
            'pc_intro' => '%s is committed to ensuring that personal data processing on %s complies with the GDPR and applicable data protection laws.',
            'pc_data_collected' => 'Data Collected',
            'pc_data_text1' => 'We are committed to collecting only the minimum data necessary for the proper functioning of the service provided by this website. Whether data is mandatory or optional is indicated at the time of entry.',
            'pc_data_text2' => 'Some data is automatically collected as a result of your actions on the site for audience measurement purposes or is necessary for the prevention and resolution of technical incidents.',
            'pc_data_text3' => 'Data is retained for the duration of service use and then archived for an additional period in accordance with legal prescription and retention requirements.',
            'pc_treatments' => 'Personal Data Processing',
            'pc_table_data' => 'Data collected',
            'pc_table_purpose' => 'Purpose',
            'pc_table_legal' => 'Legal basis',
            'pc_table_retention' => 'Retention period',
            'pc_recipients' => 'Data recipients:',
            'pc_third_party' => 'Subcontractors:',
            'rights_title' => 'Exercise Your Rights',
            'rights_intro' => 'For any information or to exercise your data protection rights regarding personal data managed by %s, you can contact us:',
            'rights_email' => 'by email at %s',
            'rights_mail' => 'by mail at',
            'rights_list' => 'Under the GDPR, you have the following rights: right of access, right to rectification, right to erasure (right to be forgotten), right to restriction of processing, right to data portability, right to object.',
            'rights_dpa' => 'You may also lodge a complaint with the %s: %s',
            'cookies_title' => 'Cookies',
            'cookies_info_title' => 'Cookie Information',
            'cookies_info_text' => 'For statistics and display purposes, this site uses cookies. These are small text files stored on your hard drive to record technical data about your browsing.',
            'cookies_purpose_title' => 'Purpose of Cookies Used',
            'cookies_choices_title' => 'Your Cookie Choices',
            'cookies_choices_text' => 'You can configure your browser to accept or reject cookies, either systematically or depending on their source. You can also configure your browser to prompt you before a cookie is stored.',
            'cookies_manage_text' => 'You can manage your cookie preferences at any time using our consent manager.',
            'cookies_manage_button' => 'Manage my cookie preferences',
            'cookies_browser_text' => 'For more information about cookie management in your browser:',
            'email' => 'Email',
            'tel' => 'Phone',
        ],
    ];
}
