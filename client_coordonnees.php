<?php
/**
 * Plugin Name: Coordonnées & RGPD - By MATRYS
 * Plugin URI: https://github.com/JulienBataille/client-coordonnees-rgpd
 * Description: Coordonnées client/agence + mentions légales et politique de confidentialité conformes LCEN/RGPD avec traduction multi-langues.
 * Version: 3.5.1
 * Author: MATRYS - Julien Bataillé
 * Author URI: https://matrys.fr
 * Text Domain: client-coordonnees
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * GitHub Plugin URI: JulienBataille/client-coordonnees-rgpd
 */

if (!defined('ABSPATH')) exit;

// === MATRYS GitHub Updater ===
require_once plugin_dir_path(__FILE__) . 'includes/class-matrys-github-updater.php';
if (class_exists('MATRYS_GitHub_Updater')) {
    new MATRYS_GitHub_Updater(
        __FILE__,
        'JulienBataille',              // GitHub user/org
        'client-coordonnees-rgpd'     // GitHub repo
        // Pour repo privé : defined('MATRYS_GH_TOKEN') ? MATRYS_GH_TOKEN : null
    );
}

class Client_Coordonnees_RGPD_Plugin
{
    private const OPTION_GROUP = 'coordonnees_client_group';
    private const OPTION_GROUP_RGPD = 'coordonnees_rgpd_group';
    private const MENU_SLUG = 'coordonnees-client';
    
    private const COUNTRIES = [
        'FR' => ['name' => 'France', 'code' => '+33', 'format' => '0X XX XX XX XX'],
        'BE' => ['name' => 'Belgique', 'code' => '+32', 'format' => '0XX XX XX XX'],
        'CH' => ['name' => 'Suisse', 'code' => '+41', 'format' => 'XX XXX XX XX'],
        'LU' => ['name' => 'Luxembourg', 'code' => '+352', 'format' => 'XXX XXX XXX'],
        'CA' => ['name' => 'Canada', 'code' => '+1', 'format' => 'XXX XXX XXXX'],
        'US' => ['name' => 'États-Unis', 'code' => '+1', 'format' => 'XXX XXX XXXX'],
        'GB' => ['name' => 'Royaume-Uni', 'code' => '+44', 'format' => 'XXXX XXX XXX'],
        'DE' => ['name' => 'Allemagne', 'code' => '+49', 'format' => 'XXX XXXXXXX'],
        'ES' => ['name' => 'Espagne', 'code' => '+34', 'format' => 'XXX XXX XXX'],
        'IT' => ['name' => 'Italie', 'code' => '+39', 'format' => 'XXX XXX XXXX'],
        'PT' => ['name' => 'Portugal', 'code' => '+351', 'format' => 'XXX XXX XXX'],
    ];
    
    private const FORMES_JURIDIQUES = [
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

    private const DEFAULT_OPTIONS = [
        'matrys_name' => 'Agence de communication MATRYS',
        'matrys_url' => 'https://matrys.fr',
        'matrys_address' => "28 rue Victor Hugo\n40400 Tartas",
        'matrys_tel' => '05 58 73 41 57',
        'matrys_country' => 'FR',
    ];
    
    private const FIELD_TYPE_MAPPING = [
        'name' => 'name_field', 'email' => 'email_field', 'phone' => 'phone_field',
        'address' => 'address_field', 'textarea' => 'message_field', 'text' => 'text_field',
        'number' => 'number_field', 'date' => 'date_field', 'upload' => 'upload_field',
        'select' => 'select_field', 'radio' => 'radio_field', 'checkbox' => 'checkbox_field',
        'consent' => 'consent_field', 'stripe' => 'payment_field', 'paypal' => 'payment_field',
        'signature' => 'signature_field',
    ];
    
    private const RETENTION_OPTIONS = [
        '6_months' => '6_months', '1_year' => '1_year', '2_years' => '2_years',
        '3_years' => '3_years', '5_years' => '5_years',
        'until_unsubscribe' => 'until_unsubscribe', 'contract_plus_3' => 'contract_plus_3',
    ];
    
    private const TRANSLATIONS = [
        'fr_FR' => [
            'dpa_name' => 'CNIL', 'dpa_url' => 'https://www.cnil.fr',
            'legal_consent' => 'Consentement', 'legal_contract' => 'Exécution d\'un contrat',
            'legal_obligation' => 'Obligation légale', 'legal_legitimate' => 'Intérêt légitime',
            'name_field' => 'Nom, prénom', 'email_field' => 'Adresse email', 'phone_field' => 'Numéro de téléphone',
            'address_field' => 'Adresse postale', 'message_field' => 'Message / Commentaire', 'text_field' => 'Texte libre',
            'number_field' => 'Données numériques', 'date_field' => 'Date', 'upload_field' => 'Fichiers téléchargés',
            'select_field' => 'Choix (liste)', 'radio_field' => 'Choix (boutons)', 'checkbox_field' => 'Choix multiples',
            'consent_field' => 'Consentement', 'payment_field' => 'Données de paiement', 'signature_field' => 'Signature électronique',
            'form_data' => 'Données de formulaire',
            '6_months' => '6 mois', '1_year' => '1 an', '2_years' => '2 ans', '3_years' => '3 ans', '5_years' => '5 ans',
            'until_unsubscribe' => 'Jusqu\'à désinscription', 'contract_plus_3' => 'Durée du contrat + 3 ans',
            'ml_title' => 'Mentions légales', 'ml_editor' => 'Éditeur du site',
            'ml_editor_intro' => 'Le site %s est édité par :',
            'ml_responsible' => 'Responsable de la publication :',
            'ml_hosting' => 'Réalisation et hébergement',
            'ml_intellectual' => 'Propriété intellectuelle',
            'ml_intellectual_text' => 'Le contenu de ce site internet est protégé par les droits de propriété intellectuelle et notamment par le droit d\'auteur. Toute reproduction de ces contenus est conditionnée à un accord explicite préalable, en vertu de l\'article L.122-4 du Code de la Propriété Intellectuelle.',
            'ml_intellectual_contact' => 'Pour toute demande d\'autorisation ou d\'information, veuillez nous contacter par email : %s.',
            'ml_info' => 'Informations et exclusions',
            'ml_info_text1' => 'L\'éditeur de ce site met en œuvre tous les moyens dont il dispose pour assurer une information fiable et une mise à jour des contenus. Toutefois, des erreurs ou omissions peuvent survenir. L\'internaute devra donc s\'assurer de l\'exactitude des informations auprès de l\'éditeur et signaler toutes modifications du site qu\'il jugerait utile.',
            'ml_info_text2' => 'L\'éditeur du site n\'est en aucun cas responsable de l\'utilisation faite de ces informations, et de tout préjudice direct ou indirect pouvant en découler. Les photos sont non contractuelles.',
            'ml_info_text3' => 'Les liens hypertextes mis en place dans le cadre du présent site internet en direction d\'autres ressources présentes sur le réseau Internet ne sauraient engager la responsabilité de l\'éditeur de ce site.',
            'ml_personal_data' => 'Données personnelles',
            'ml_personal_data_text' => 'L\'éditeur de ce site s\'engage à ce que les traitements de données personnelles qui y sont effectués soient conformes au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.',
            'ml_privacy_link' => 'Pour en savoir plus, consultez notre %s.',
            'ml_privacy_page' => 'politique de confidentialité',
            'pc_title' => 'Politique de confidentialité',
            'pc_intro' => '%s s\'engage à ce que les traitements de données personnelles effectués sur %s soient conformes au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.',
            'pc_data_collected' => 'Données collectées',
            'pc_data_text1' => 'Nous nous engageons à ne collecter que le minimum de données nécessaires au bon fonctionnement du service fourni par ce site internet. Le caractère obligatoire ou facultatif des données collectées vous est signalé au moment de leur saisie.',
            'pc_data_text2' => 'Certaines données sont collectées automatiquement du fait de vos actions sur le site afin d\'effectuer des mesures d\'audience ou sont nécessaires à la prévention et la résolution d\'incidents techniques.',
            'pc_data_text3' => 'Les données sont conservées pendant toute la durée d\'utilisation du service puis sont archivées pour une durée supplémentaire en lien avec les durées de prescription et de conservation légale.',
            'pc_treatments' => 'Traitements de données personnelles',
            'pc_table_treatment' => 'Traitement', 'pc_table_data' => 'Données collectées',
            'pc_table_purpose' => 'Finalité', 'pc_table_legal' => 'Base légale', 'pc_table_retention' => 'Conservation',
            'pc_recipients' => 'Destinataires des données :', 'pc_third_party' => 'Sous-traitants :',
            'rights_title' => 'Exercer vos droits',
            'rights_intro' => 'Pour toute information ou exercice de vos droits sur les traitements de données personnelles gérés par %s, vous pouvez nous écrire (avec copie de votre pièce d\'identité en cas d\'exercice de vos droits) :',
            'rights_email' => 'par email à %s', 'rights_mail' => 'par courrier à',
            'rights_list_intro' => 'Conformément au RGPD, vous disposez des droits suivants : accès, rectification, effacement, limitation, portabilité, opposition et retrait du consentement.',
            'rights_dpa' => 'Vous pouvez également introduire une réclamation auprès de la %s : %s',
            'cookies_title' => 'Cookies', 'cookies_info_title' => 'Informations sur les cookies',
            'cookies_info_text' => 'Pour des besoins de statistiques et d\'affichage, le présent site utilise des cookies. Il s\'agit de petits fichiers textes stockés sur votre disque dur afin d\'enregistrer des données techniques sur votre navigation.',
            'cookies_purpose_title' => 'Finalité des cookies utilisés', 'cookies_choices_title' => 'Vos choix concernant les cookies',
            'cookies_choices_text' => 'Vous pouvez configurer votre logiciel de navigation pour accepter ou refuser les cookies :',
            'email' => 'Email', 'tel' => 'Tél', 'address' => 'Adresse',
        ],
        'es_ES' => [
            'dpa_name' => 'AEPD', 'dpa_url' => 'https://www.aepd.es',
            'legal_consent' => 'Consentimiento', 'legal_contract' => 'Ejecución de un contrato',
            'legal_obligation' => 'Obligación legal', 'legal_legitimate' => 'Interés legítimo',
            'name_field' => 'Nombre, apellidos', 'email_field' => 'Correo electrónico', 'phone_field' => 'Número de teléfono',
            'address_field' => 'Dirección postal', 'message_field' => 'Mensaje / Comentario', 'text_field' => 'Texto libre',
            'number_field' => 'Datos numéricos', 'date_field' => 'Fecha', 'upload_field' => 'Archivos subidos',
            'select_field' => 'Selección (lista)', 'radio_field' => 'Selección (botones)', 'checkbox_field' => 'Selección múltiple',
            'consent_field' => 'Consentimiento', 'payment_field' => 'Datos de pago', 'signature_field' => 'Firma electrónica',
            'form_data' => 'Datos del formulario',
            '6_months' => '6 meses', '1_year' => '1 año', '2_years' => '2 años', '3_years' => '3 años', '5_years' => '5 años',
            'until_unsubscribe' => 'Hasta darse de baja', 'contract_plus_3' => 'Duración del contrato + 3 años',
            'ml_title' => 'Aviso legal', 'ml_editor' => 'Editor del sitio',
            'ml_editor_intro' => 'El sitio %s es editado por:', 'ml_responsible' => 'Responsable de la publicación:',
            'ml_hosting' => 'Diseño y alojamiento', 'ml_intellectual' => 'Propiedad intelectual',
            'ml_intellectual_text' => 'El contenido de este sitio web está protegido por los derechos de propiedad intelectual.',
            'ml_intellectual_contact' => 'Para cualquier solicitud, contáctenos por correo electrónico: %s.',
            'ml_info' => 'Información y exclusiones',
            'ml_info_text1' => 'El editor hace todo lo posible para garantizar información confiable y actualizada.',
            'ml_info_text2' => 'El editor no es responsable del uso de esta información. Las fotos no son contractuales.',
            'ml_info_text3' => 'Los enlaces hacia otros recursos en Internet no comprometen la responsabilidad del editor.',
            'ml_personal_data' => 'Datos personales',
            'ml_personal_data_text' => 'El editor se compromete a cumplir con el Reglamento General de Protección de Datos (RGPD).',
            'ml_privacy_link' => 'Para más información, consulte nuestra %s.', 'ml_privacy_page' => 'política de privacidad',
            'pc_title' => 'Política de privacidad',
            'pc_intro' => '%s se compromete a que el tratamiento de datos en %s cumpla con el RGPD.',
            'pc_data_collected' => 'Datos recopilados',
            'pc_data_text1' => 'Nos comprometemos a recopilar solo los datos mínimos necesarios.',
            'pc_data_text2' => 'Algunos datos se recopilan automáticamente para mediciones de audiencia.',
            'pc_data_text3' => 'Los datos se conservan durante la duración del uso del servicio.',
            'pc_treatments' => 'Tratamiento de datos personales',
            'pc_table_treatment' => 'Tratamiento', 'pc_table_data' => 'Datos recopilados',
            'pc_table_purpose' => 'Finalidad', 'pc_table_legal' => 'Base legal', 'pc_table_retention' => 'Conservación',
            'pc_recipients' => 'Destinatarios:', 'pc_third_party' => 'Subcontratistas:',
            'rights_title' => 'Ejercer sus derechos',
            'rights_intro' => 'Para ejercer sus derechos sobre los datos gestionados por %s, puede escribirnos:',
            'rights_email' => 'por correo electrónico a %s', 'rights_mail' => 'por correo postal a',
            'rights_list_intro' => 'De acuerdo con el RGPD, tiene los siguientes derechos: acceso, rectificación, supresión, limitación, portabilidad, oposición.',
            'rights_dpa' => 'También puede presentar una reclamación ante la %s: %s',
            'cookies_title' => 'Cookies', 'cookies_info_title' => 'Información sobre cookies',
            'cookies_info_text' => 'Este sitio utiliza cookies para estadísticas y visualización.',
            'cookies_purpose_title' => 'Finalidad de las cookies', 'cookies_choices_title' => 'Sus opciones',
            'cookies_choices_text' => 'Puede configurar su navegador para aceptar o rechazar cookies:',
            'email' => 'Correo', 'tel' => 'Tel', 'address' => 'Dirección',
        ],
        'en_US' => [
            'dpa_name' => 'ICO', 'dpa_url' => 'https://ico.org.uk',
            'legal_consent' => 'Consent', 'legal_contract' => 'Performance of a contract',
            'legal_obligation' => 'Legal obligation', 'legal_legitimate' => 'Legitimate interest',
            'name_field' => 'Name, surname', 'email_field' => 'Email address', 'phone_field' => 'Phone number',
            'address_field' => 'Postal address', 'message_field' => 'Message / Comment', 'text_field' => 'Free text',
            'number_field' => 'Numeric data', 'date_field' => 'Date', 'upload_field' => 'Uploaded files',
            'select_field' => 'Selection (dropdown)', 'radio_field' => 'Selection (buttons)', 'checkbox_field' => 'Multiple choice',
            'consent_field' => 'Consent', 'payment_field' => 'Payment data', 'signature_field' => 'Electronic signature',
            'form_data' => 'Form data',
            '6_months' => '6 months', '1_year' => '1 year', '2_years' => '2 years', '3_years' => '3 years', '5_years' => '5 years',
            'until_unsubscribe' => 'Until unsubscription', 'contract_plus_3' => 'Contract duration + 3 years',
            'ml_title' => 'Legal Notice', 'ml_editor' => 'Website Publisher',
            'ml_editor_intro' => 'The website %s is published by:', 'ml_responsible' => 'Publication Manager:',
            'ml_hosting' => 'Design and Hosting', 'ml_intellectual' => 'Intellectual Property',
            'ml_intellectual_text' => 'The content of this website is protected by intellectual property rights.',
            'ml_intellectual_contact' => 'For any request, please contact us by email: %s.',
            'ml_info' => 'Information and Disclaimers',
            'ml_info_text1' => 'The publisher makes every effort to ensure reliable and up-to-date information.',
            'ml_info_text2' => 'The publisher is not responsible for the use of this information. Photos are not contractual.',
            'ml_info_text3' => 'Hyperlinks to other Internet resources do not engage the publisher\'s responsibility.',
            'ml_personal_data' => 'Personal Data',
            'ml_personal_data_text' => 'The publisher is committed to complying with the General Data Protection Regulation (GDPR).',
            'ml_privacy_link' => 'For more information, see our %s.', 'ml_privacy_page' => 'privacy policy',
            'pc_title' => 'Privacy Policy',
            'pc_intro' => '%s is committed to ensuring that data processing on %s complies with GDPR.',
            'pc_data_collected' => 'Data Collected',
            'pc_data_text1' => 'We are committed to collecting only the minimum data necessary.',
            'pc_data_text2' => 'Some data is collected automatically for audience measurement.',
            'pc_data_text3' => 'Data is kept for the duration of service use.',
            'pc_treatments' => 'Personal Data Processing',
            'pc_table_treatment' => 'Processing', 'pc_table_data' => 'Data collected',
            'pc_table_purpose' => 'Purpose', 'pc_table_legal' => 'Legal basis', 'pc_table_retention' => 'Retention',
            'pc_recipients' => 'Data recipients:', 'pc_third_party' => 'Subcontractors:',
            'rights_title' => 'Exercise Your Rights',
            'rights_intro' => 'To exercise your rights regarding data managed by %s, you can write to us:',
            'rights_email' => 'by email to %s', 'rights_mail' => 'by post to',
            'rights_list_intro' => 'In accordance with GDPR, you have the following rights: access, rectification, erasure, restriction, portability, objection.',
            'rights_dpa' => 'You can also lodge a complaint with the %s: %s',
            'cookies_title' => 'Cookies', 'cookies_info_title' => 'Cookie Information',
            'cookies_info_text' => 'This site uses cookies for statistics and display purposes.',
            'cookies_purpose_title' => 'Purpose of Cookies', 'cookies_choices_title' => 'Your Cookie Choices',
            'cookies_choices_text' => 'You can configure your browser to accept or reject cookies:',
            'email' => 'Email', 'tel' => 'Phone', 'address' => 'Address',
        ],
        'de_DE' => [
            'dpa_name' => 'BfDI', 'dpa_url' => 'https://www.bfdi.bund.de',
            'legal_consent' => 'Einwilligung', 'legal_contract' => 'Vertragserfüllung',
            'legal_obligation' => 'Rechtliche Verpflichtung', 'legal_legitimate' => 'Berechtigtes Interesse',
            'name_field' => 'Name, Vorname', 'email_field' => 'E-Mail-Adresse', 'phone_field' => 'Telefonnummer',
            'address_field' => 'Postanschrift', 'message_field' => 'Nachricht / Kommentar', 'text_field' => 'Freitext',
            'number_field' => 'Numerische Daten', 'date_field' => 'Datum', 'upload_field' => 'Hochgeladene Dateien',
            'form_data' => 'Formulardaten',
            '6_months' => '6 Monate', '1_year' => '1 Jahr', '2_years' => '2 Jahre', '3_years' => '3 Jahre', '5_years' => '5 Jahre',
            'until_unsubscribe' => 'Bis zur Abmeldung', 'contract_plus_3' => 'Vertragsdauer + 3 Jahre',
            'ml_title' => 'Impressum', 'ml_editor' => 'Herausgeber', 'ml_editor_intro' => 'Die Website %s wird herausgegeben von:',
            'ml_responsible' => 'Verantwortlich:', 'ml_hosting' => 'Gestaltung und Hosting',
            'ml_intellectual' => 'Geistiges Eigentum', 'ml_intellectual_text' => 'Der Inhalt dieser Website ist urheberrechtlich geschützt.',
            'ml_intellectual_contact' => 'Kontaktieren Sie uns per E-Mail: %s.',
            'ml_info' => 'Haftungsausschluss', 'ml_info_text1' => 'Der Herausgeber bemüht sich um zuverlässige Informationen.',
            'ml_info_text2' => 'Der Herausgeber ist nicht verantwortlich für die Nutzung dieser Informationen.',
            'ml_info_text3' => 'Hyperlinks zu anderen Ressourcen begründen keine Verantwortung.',
            'ml_personal_data' => 'Personenbezogene Daten', 'ml_personal_data_text' => 'Der Herausgeber verpflichtet sich zur Einhaltung der DSGVO.',
            'ml_privacy_link' => 'Weitere Informationen in unserer %s.', 'ml_privacy_page' => 'Datenschutzerklärung',
            'pc_title' => 'Datenschutzerklärung', 'pc_intro' => '%s verpflichtet sich zur DSGVO-Konformität auf %s.',
            'pc_data_collected' => 'Erhobene Daten', 'pc_data_text1' => 'Wir erheben nur die notwendigen Mindestdaten.',
            'pc_data_text2' => 'Einige Daten werden automatisch erhoben.', 'pc_data_text3' => 'Die Daten werden während der Nutzungsdauer aufbewahrt.',
            'pc_treatments' => 'Datenverarbeitung', 'pc_table_treatment' => 'Verarbeitung', 'pc_table_data' => 'Daten',
            'pc_table_purpose' => 'Zweck', 'pc_table_legal' => 'Rechtsgrundlage', 'pc_table_retention' => 'Aufbewahrung',
            'pc_recipients' => 'Empfänger:', 'pc_third_party' => 'Auftragsverarbeiter:',
            'rights_title' => 'Ihre Rechte', 'rights_intro' => 'Zur Ausübung Ihrer Rechte bei %s schreiben Sie uns:',
            'rights_email' => 'per E-Mail an %s', 'rights_mail' => 'per Post an',
            'rights_list_intro' => 'Sie haben folgende Rechte: Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit, Widerspruch.',
            'rights_dpa' => 'Sie können auch Beschwerde beim %s einreichen: %s',
            'cookies_title' => 'Cookies', 'cookies_info_title' => 'Cookie-Informationen',
            'cookies_info_text' => 'Diese Website verwendet Cookies.', 'cookies_purpose_title' => 'Zweck der Cookies',
            'cookies_choices_title' => 'Ihre Einstellungen', 'cookies_choices_text' => 'Sie können Ihren Browser konfigurieren:',
            'email' => 'E-Mail', 'tel' => 'Tel', 'address' => 'Adresse',
        ],
        'it_IT' => [
            'dpa_name' => 'Garante Privacy', 'dpa_url' => 'https://www.garanteprivacy.it',
            'legal_consent' => 'Consenso', 'legal_contract' => 'Esecuzione contratto',
            'legal_obligation' => 'Obbligo legale', 'legal_legitimate' => 'Interesse legittimo',
            'form_data' => 'Dati del modulo',
            '6_months' => '6 mesi', '1_year' => '1 anno', '2_years' => '2 anni', '3_years' => '3 anni', '5_years' => '5 anni',
            'until_unsubscribe' => 'Fino alla cancellazione', 'contract_plus_3' => 'Durata contratto + 3 anni',
            'ml_title' => 'Note legali', 'ml_editor' => 'Editore', 'ml_editor_intro' => 'Il sito %s è pubblicato da:',
            'ml_responsible' => 'Responsabile:', 'ml_hosting' => 'Realizzazione e hosting',
            'ml_intellectual' => 'Proprietà intellettuale', 'ml_intellectual_text' => 'Il contenuto è protetto dal diritto d\'autore.',
            'ml_intellectual_contact' => 'Contattateci via email: %s.',
            'ml_info' => 'Informazioni', 'ml_info_text1' => 'L\'editore si impegna per informazioni affidabili.',
            'ml_info_text2' => 'L\'editore non è responsabile. Le foto non sono contrattuali.',
            'ml_info_text3' => 'I link non impegnano la responsabilità dell\'editore.',
            'ml_personal_data' => 'Dati personali', 'ml_personal_data_text' => 'L\'editore rispetta il GDPR.',
            'ml_privacy_link' => 'Consultare la %s.', 'ml_privacy_page' => 'informativa privacy',
            'pc_title' => 'Informativa privacy', 'pc_intro' => '%s rispetta il GDPR su %s.',
            'pc_data_collected' => 'Dati raccolti', 'pc_data_text1' => 'Raccogliamo solo i dati necessari.',
            'pc_data_text2' => 'Alcuni dati sono raccolti automaticamente.', 'pc_data_text3' => 'I dati sono conservati per la durata del servizio.',
            'pc_treatments' => 'Trattamento dati', 'pc_table_treatment' => 'Trattamento', 'pc_table_data' => 'Dati',
            'pc_table_purpose' => 'Finalità', 'pc_table_legal' => 'Base giuridica', 'pc_table_retention' => 'Conservazione',
            'pc_recipients' => 'Destinatari:', 'pc_third_party' => 'Subappaltatori:',
            'rights_title' => 'I vostri diritti', 'rights_intro' => 'Per esercitare i diritti presso %s, scrivete:',
            'rights_email' => 'via email a %s', 'rights_mail' => 'per posta a',
            'rights_list_intro' => 'Avete diritto di: accesso, rettifica, cancellazione, limitazione, portabilità, opposizione.',
            'rights_dpa' => 'Potete presentare reclamo al %s: %s',
            'cookies_title' => 'Cookie', 'cookies_info_title' => 'Informazioni cookie',
            'cookies_info_text' => 'Questo sito utilizza cookie.', 'cookies_purpose_title' => 'Finalità dei cookie',
            'cookies_choices_title' => 'Le vostre scelte', 'cookies_choices_text' => 'Potete configurare il browser:',
            'email' => 'Email', 'tel' => 'Tel', 'address' => 'Indirizzo',
        ],
        'pt_PT' => [
            'dpa_name' => 'CNPD', 'dpa_url' => 'https://www.cnpd.pt',
            'legal_consent' => 'Consentimento', 'legal_contract' => 'Execução de contrato',
            'legal_obligation' => 'Obrigação legal', 'legal_legitimate' => 'Interesse legítimo',
            'form_data' => 'Dados do formulário',
            '6_months' => '6 meses', '1_year' => '1 ano', '2_years' => '2 anos', '3_years' => '3 anos', '5_years' => '5 anos',
            'until_unsubscribe' => 'Até ao cancelamento', 'contract_plus_3' => 'Duração contrato + 3 anos',
            'ml_title' => 'Aviso legal', 'ml_editor' => 'Editor', 'ml_editor_intro' => 'O site %s é editado por:',
            'ml_responsible' => 'Responsável:', 'ml_hosting' => 'Realização e alojamento',
            'ml_intellectual' => 'Propriedade intelectual', 'ml_intellectual_text' => 'O conteúdo está protegido por direitos de autor.',
            'ml_intellectual_contact' => 'Contacte-nos por email: %s.',
            'ml_info' => 'Informações', 'ml_info_text1' => 'O editor esforça-se por informações fiáveis.',
            'ml_info_text2' => 'O editor não é responsável. As fotos não são contratuais.',
            'ml_info_text3' => 'Os links não comprometem a responsabilidade do editor.',
            'ml_personal_data' => 'Dados pessoais', 'ml_personal_data_text' => 'O editor cumpre o RGPD.',
            'ml_privacy_link' => 'Consulte a %s.', 'ml_privacy_page' => 'política de privacidade',
            'pc_title' => 'Política de privacidade', 'pc_intro' => '%s cumpre o RGPD em %s.',
            'pc_data_collected' => 'Dados recolhidos', 'pc_data_text1' => 'Recolhemos apenas os dados necessários.',
            'pc_data_text2' => 'Alguns dados são recolhidos automaticamente.', 'pc_data_text3' => 'Os dados são conservados durante a utilização.',
            'pc_treatments' => 'Tratamento de dados', 'pc_table_treatment' => 'Tratamento', 'pc_table_data' => 'Dados',
            'pc_table_purpose' => 'Finalidade', 'pc_table_legal' => 'Base legal', 'pc_table_retention' => 'Conservação',
            'pc_recipients' => 'Destinatários:', 'pc_third_party' => 'Subcontratantes:',
            'rights_title' => 'Os seus direitos', 'rights_intro' => 'Para exercer os direitos junto de %s, escreva:',
            'rights_email' => 'por email para %s', 'rights_mail' => 'por correio para',
            'rights_list_intro' => 'Tem direito de: acesso, retificação, apagamento, limitação, portabilidade, oposição.',
            'rights_dpa' => 'Pode apresentar reclamação junto da %s: %s',
            'cookies_title' => 'Cookies', 'cookies_info_title' => 'Informações cookies',
            'cookies_info_text' => 'Este site utiliza cookies.', 'cookies_purpose_title' => 'Finalidade dos cookies',
            'cookies_choices_title' => 'As suas escolhas', 'cookies_choices_text' => 'Pode configurar o navegador:',
            'email' => 'Email', 'tel' => 'Tel', 'address' => 'Morada',
        ],
    ];

    public static function init() { (new self())->register_hooks(); }

    private function register_hooks()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        add_shortcode('client_email', [$this, 'shortcode_client_email']);
        add_shortcode('client_tel', [$this, 'shortcode_client_tel']);
        add_shortcode('client_address', [$this, 'shortcode_client_address']);
        add_shortcode('site_title', [$this, 'shortcode_site_title']);
        add_shortcode('site_link', [$this, 'shortcode_site_link']);
        add_shortcode('matrys_block', [$this, 'shortcode_matrys_block']);
        add_shortcode('rgpd_mentions', [$this, 'shortcode_rgpd_mentions']);
        add_shortcode('rgpd_droits', [$this, 'shortcode_rgpd_droits']);
        add_shortcode('rgpd_cookies', [$this, 'shortcode_rgpd_cookies']);
        add_shortcode('mentions_legales', [$this, 'shortcode_mentions_legales']);
        add_shortcode('politique_confidentialite', [$this, 'shortcode_politique_complete']);
        
        register_activation_hook(__FILE__, [$this, 'on_activation']);
    }
    
    private function get_current_language()
    {
        $locale = get_locale();
        if (isset(self::TRANSLATIONS[$locale])) return $locale;
        $prefix = substr($locale, 0, 2);
        $map = ['fr'=>'fr_FR','es'=>'es_ES','en'=>'en_US','de'=>'de_DE','it'=>'it_IT','pt'=>'pt_PT'];
        return $map[$prefix] ?? 'fr_FR';
    }
    
    private function t($key) {
        $lang = $this->get_current_language();
        return self::TRANSLATIONS[$lang][$key] ?? self::TRANSLATIONS['fr_FR'][$key] ?? $key;
    }
    
    private function get_legal_basis_label($key) {
        $map = ['consent'=>'legal_consent','contract'=>'legal_contract','legal_obligation'=>'legal_obligation','legitimate_interest'=>'legal_legitimate'];
        return $this->t($map[$key] ?? 'legal_consent');
    }
    
    private function get_field_type_label($type) {
        return $this->t(self::FIELD_TYPE_MAPPING[$type] ?? 'form_data');
    }
    
    private function get_retention_label($key) { return $this->t($key) ?? $key; }

    public function add_admin_menu()
    {
        add_menu_page('Coordonnées & RGPD', 'Coordonnées & RGPD', 'manage_options', self::MENU_SLUG, [$this, 'render_settings_page'], 'dashicons-id', 20);
    }

    public function register_settings()
    {
        $fields = [
            'client_raison_sociale'=>'sanitize_text_field',
            'client_email'=>'sanitize_email', 'client_tel'=>'sanitize_text_field', 'client_country'=>'sanitize_text_field',
            'client_address'=>'sanitize_textarea_field', 'client_siret'=>'sanitize_text_field', 'client_rcs'=>'sanitize_text_field',
            'client_capital'=>'sanitize_text_field', 'client_tva'=>'sanitize_text_field', 'client_responsable'=>'sanitize_text_field',
            'client_forme_juridique'=>'sanitize_text_field',
            'client_forme_juridique_autre'=>'sanitize_text_field',
            'matrys_name'=>'sanitize_text_field', 'matrys_url'=>'esc_url_raw', 'matrys_address'=>'sanitize_textarea_field',
            'matrys_tel'=>'sanitize_text_field', 'matrys_country'=>'sanitize_text_field',
        ];
        foreach ($fields as $f => $cb) register_setting(self::OPTION_GROUP, $f, ['sanitize_callback' => $cb]);
        register_setting(self::OPTION_GROUP_RGPD, 'rgpd_settings', [$this, 'sanitize_rgpd_settings']);
    }
    
    public function sanitize_rgpd_settings($input)
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

    public function enqueue_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) return;
        wp_add_inline_style('wp-admin', '
            .coordonnees-wrap{background:#fff;padding:20px;margin:20px 0;border:1px solid #ccd0d4;border-radius:4px}
            .coordonnees-wrap h2{margin-top:0;padding-bottom:10px;border-bottom:1px solid #ddd}
            .tab-content{display:none}.tab-content.active{display:block}
            .rgpd-form-item{border:1px solid #ddd;border-radius:4px;margin-bottom:15px;background:#fafafa}
            .rgpd-form-item.enabled{border-color:#2271b1;background:#fff}
            .rgpd-form-header{display:flex;align-items:center;gap:15px;padding:15px;background:#f6f7f7;cursor:pointer}
            .rgpd-form-item.enabled .rgpd-form-header{background:#f0f6fc}
            .rgpd-form-title{flex:1}.rgpd-form-title strong{font-size:14px}
            .rgpd-form-meta{display:flex;gap:10px;margin-top:5px}
            .rgpd-form-meta span{font-size:11px;background:#e0e0e0;padding:2px 8px;border-radius:3px}
            .rgpd-form-meta .count{background:#d4edda;color:#155724}
            .rgpd-form-details{padding:15px;display:none}.rgpd-form-item.open .rgpd-form-details{display:block}
            .rgpd-detected{background:#fff3cd;padding:10px 15px;border-radius:4px;margin-bottom:15px}
            .rgpd-form-config th{width:150px;padding:8px 10px 8px 0}.rgpd-form-config td{padding:8px 0}
            .rgpd-form-config input[type="text"],.rgpd-form-config select{width:100%;max-width:400px}
            .toggle-switch{position:relative;width:50px;height:26px}
            .toggle-switch input{opacity:0;width:0;height:0}
            .toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;transition:.3s;border-radius:26px}
            .toggle-slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:#fff;transition:.3s;border-radius:50%}
            .toggle-switch input:checked+.toggle-slider{background:#2271b1}
            .toggle-switch input:checked+.toggle-slider:before{transform:translateX(24px)}
            .lang-badge{display:inline-block;background:#d4edda;color:#155724;padding:3px 8px;border-radius:3px;font-size:11px;margin-left:10px}
            .shortcode-list{background:#f9f9f9;padding:15px;border-left:4px solid #2271b1;margin-top:20px}
            .shortcode-list code{background:#fff;padding:2px 6px;border:1px solid #ddd;border-radius:3px}
        ');
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($){
                $(".nav-tab").on("click",function(e){e.preventDefault();$(".nav-tab").removeClass("nav-tab-active");$(this).addClass("nav-tab-active");$(".tab-content").removeClass("active");$($(this).attr("href")).addClass("active")});
                $(".rgpd-form-header").on("click",function(e){if($(e.target).closest(".toggle-switch").length)return;$(this).closest(".rgpd-form-item").toggleClass("open")});
                $(".rgpd-form-item .toggle-switch input").on("change",function(){$(this).closest(".rgpd-form-item").toggleClass("enabled",this.checked)});
                function toggleFormeJuridiqueAutre(){$("#forme_juridique_autre_wrap").toggle($("#client_forme_juridique").val()==="Autre")}
                $("#client_forme_juridique").on("change",toggleFormeJuridiqueAutre);
                toggleFormeJuridiqueAutre();
            });
        ');
    }

    public function render_settings_page()
    {
        $rgpd = get_option('rgpd_settings', []);
        $forms = $this->get_forminator_forms();
        $lang = $this->get_current_language();
        $langs = ['fr_FR'=>'🇫🇷 Français','es_ES'=>'🇪🇸 Español','en_US'=>'🇬🇧 English','de_DE'=>'🇩🇪 Deutsch','it_IT'=>'🇮🇹 Italiano','pt_PT'=>'🇵🇹 Português'];
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-id"></span> Coordonnées & RGPD <span class="lang-badge"><?php echo $langs[$lang] ?? $lang; ?> (auto)</span></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="#tab-client" class="nav-tab nav-tab-active">👤 Client</a>
                <a href="#tab-agence" class="nav-tab">🏢 Agence</a>
                <a href="#tab-rgpd" class="nav-tab">🛡️ RGPD</a>
                <a href="#tab-shortcodes" class="nav-tab">🔧 Shortcodes</a>
            </nav>
            
            <div id="tab-client" class="tab-content active">
                <form method="post" action="options.php">
                    <?php settings_fields(self::OPTION_GROUP); ?>
                    <div class="coordonnees-wrap">
                        <h2>Coordonnées du client / Éditeur du site</h2>
                        <p class="description">Informations obligatoires selon la <a href="https://www.economie.gouv.fr/entreprises/site-internet-mentions-obligatoires" target="_blank">LCEN</a>.</p>
                        <table class="form-table">
                            <tr><th>Raison sociale / Dénomination *</th><td><input type="text" name="client_raison_sociale" value="<?php echo esc_attr(get_option('client_raison_sociale')); ?>" class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>"><p class="description">Nom légal de l'entreprise (par défaut : nom du site)</p></td></tr>
                            <tr><th>Email *</th><td><input type="email" name="client_email" value="<?php echo esc_attr(get_option('client_email')); ?>" class="regular-text" required></td></tr>
                            <tr><th>Téléphone *</th><td>
                                <select name="client_country" style="width:120px"><?php foreach (self::COUNTRIES as $c => $d) echo '<option value="'.$c.'" '.selected(get_option('client_country','FR'),$c,false).'>'.$d['name'].' ('.$d['code'].')</option>'; ?></select>
                                <input type="tel" name="client_tel" value="<?php echo esc_attr(get_option('client_tel')); ?>" class="regular-text">
                            </td></tr>
                            <tr><th>Adresse siège social *</th><td><textarea name="client_address" rows="3" class="large-text" required><?php echo esc_textarea(get_option('client_address')); ?></textarea><p class="description">Adresse complète obligatoire (pas de simple boîte postale)</p></td></tr>
                            <tr><th>Responsable publication *</th><td><input type="text" name="client_responsable" value="<?php echo esc_attr(get_option('client_responsable')); ?>" class="regular-text" placeholder="Nom du dirigeant ou représentant légal"></td></tr>
                        </table>
                        
                        <h3>Informations société (obligatoires pour personnes morales)</h3>
                        <table class="form-table">
                            <tr><th>Forme juridique *</th><td>
                                <select name="client_forme_juridique" id="client_forme_juridique">
                                    <?php foreach (self::FORMES_JURIDIQUES as $k => $v) echo '<option value="'.$k.'" '.selected(get_option('client_forme_juridique'),$k,false).'>'.esc_html($v).'</option>'; ?>
                                </select>
                                <div id="forme_juridique_autre_wrap" style="margin-top:10px;display:none;">
                                    <input type="text" name="client_forme_juridique_autre" id="client_forme_juridique_autre" value="<?php echo esc_attr(get_option('client_forme_juridique_autre')); ?>" class="regular-text" placeholder="Ex: Communauté de communes, GIE, Fondation...">
                                </div>
                            </td></tr>
                            <tr><th>Capital social</th><td><input type="text" name="client_capital" value="<?php echo esc_attr(get_option('client_capital')); ?>" class="regular-text" placeholder="Ex: 10 000 €"></td></tr>
                            <tr><th>SIRET</th><td><input type="text" name="client_siret" value="<?php echo esc_attr(get_option('client_siret')); ?>" class="regular-text" placeholder="XXX XXX XXX XXXXX"></td></tr>
                            <tr><th>RCS</th><td><input type="text" name="client_rcs" value="<?php echo esc_attr(get_option('client_rcs')); ?>" class="regular-text" placeholder="RCS Ville XXX XXX XXX"></td></tr>
                            <tr><th>N° TVA Intracommunautaire</th><td><input type="text" name="client_tva" value="<?php echo esc_attr(get_option('client_tva')); ?>" class="regular-text" placeholder="FR XX XXX XXX XXX"></td></tr>
                        </table>
                    </div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-agence" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields(self::OPTION_GROUP); ?>
                    <div class="coordonnees-wrap">
                        <h2>Agence MATRYS (Hébergeur)</h2>
                        <table class="form-table">
                            <tr><th>Nom</th><td><input type="text" name="matrys_name" value="<?php echo esc_attr($this->opt('matrys_name')); ?>" class="regular-text"></td></tr>
                            <tr><th>URL</th><td><input type="url" name="matrys_url" value="<?php echo esc_attr($this->opt('matrys_url')); ?>" class="regular-text"></td></tr>
                            <tr><th>Adresse</th><td><textarea name="matrys_address" rows="3" class="large-text"><?php echo esc_textarea($this->opt('matrys_address')); ?></textarea></td></tr>
                            <tr><th>Téléphone</th><td>
                                <select name="matrys_country" style="width:120px"><?php foreach (self::COUNTRIES as $c => $d) echo '<option value="'.$c.'" '.selected($this->opt('matrys_country'),$c,false).'>'.$d['name'].'</option>'; ?></select>
                                <input type="tel" name="matrys_tel" value="<?php echo esc_attr($this->opt('matrys_tel')); ?>" class="regular-text">
                            </td></tr>
                        </table>
                    </div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-rgpd" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields(self::OPTION_GROUP_RGPD); ?>
                    <div class="coordonnees-wrap">
                        <h2>Formulaires Forminator</h2>
                        <?php if (!class_exists('Forminator_API')) : ?>
                            <p>⚠️ Forminator n'est pas activé.</p>
                        <?php elseif (empty($forms)) : ?>
                            <p>Aucun formulaire détecté.</p>
                        <?php else : foreach ($forms as $id => $form) : $fc = $rgpd['forms'][$id] ?? []; $en = !empty($fc['enabled']); ?>
                            <div class="rgpd-form-item <?php echo $en ? 'enabled' : ''; ?>">
                                <div class="rgpd-form-header">
                                    <label class="toggle-switch"><input type="checkbox" name="rgpd_settings[forms][<?php echo $id; ?>][enabled]" value="1" <?php checked($en); ?>><span class="toggle-slider"></span></label>
                                    <div class="rgpd-form-title"><strong><?php echo esc_html($form['name']); ?></strong><div class="rgpd-form-meta"><span>ID: <?php echo $id; ?></span><span class="count"><?php echo $form['entries']; ?> entrées</span></div></div>
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </div>
                                <div class="rgpd-form-details">
                                    <div class="rgpd-detected"><strong>Données :</strong> <?php echo esc_html(implode(', ', $form['data_types'])); ?></div>
                                    <table class="form-table rgpd-form-config">
                                        <tr><th>Nom affiché</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][name_override]" value="<?php echo esc_attr($fc['name_override'] ?? ''); ?>" placeholder="<?php echo esc_attr($form['name']); ?>"></td></tr>
                                        <tr><th>Finalité</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][purpose]" value="<?php echo esc_attr($fc['purpose'] ?? $this->guess_purpose($form['name'])); ?>"></td></tr>
                                        <tr><th>Base légale</th><td><select name="rgpd_settings[forms][<?php echo $id; ?>][legal_basis]">
                                            <option value="consent" <?php selected($fc['legal_basis'] ?? 'consent', 'consent'); ?>>Consentement</option>
                                            <option value="contract" <?php selected($fc['legal_basis'] ?? '', 'contract'); ?>>Contrat</option>
                                            <option value="legal_obligation" <?php selected($fc['legal_basis'] ?? '', 'legal_obligation'); ?>>Obligation légale</option>
                                            <option value="legitimate_interest" <?php selected($fc['legal_basis'] ?? '', 'legitimate_interest'); ?>>Intérêt légitime</option>
                                        </select></td></tr>
                                        <tr><th>Conservation</th><td><select name="rgpd_settings[forms][<?php echo $id; ?>][retention]">
                                            <?php foreach (self::RETENTION_OPTIONS as $k => $v) echo '<option value="'.$k.'" '.selected($fc['retention'] ?? '3_years', $k, false).'>'.$this->t($k).'</option>'; ?>
                                        </select></td></tr>
                                        <tr><th>Destinataires</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][recipients]" value="<?php echo esc_attr($fc['recipients'] ?? ''); ?>"></td></tr>
                                        <tr><th>Sous-traitants</th><td><input type="text" name="rgpd_settings[forms][<?php echo $id; ?>][third_party]" value="<?php echo esc_attr($fc['third_party'] ?? ''); ?>"></td></tr>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    <?php submit_button('💾 Enregistrer'); ?>
                </form>
            </div>
            
            <div id="tab-shortcodes" class="tab-content">
                <div class="coordonnees-wrap" style="background:#d4edda;border-left:4px solid #28a745;">
                    <h2>⭐ Pages complètes (recommandé)</h2>
                    <p><code>[mentions_legales]</code> → Page mentions légales conforme LCEN</p>
                    <p><code>[politique_confidentialite]</code> → Politique de confidentialité conforme RGPD</p>
                    <p><strong>🌍 Traduction auto :</strong> <?php echo $langs[$lang]; ?> | Langues : 🇫🇷 🇪🇸 🇬🇧 🇩🇪 🇮🇹 🇵🇹</p>
                </div>
                <div class="coordonnees-wrap shortcode-list">
                    <h2>📍 Coordonnées</h2>
                    <p><code>[site_title]</code> <code>[site_link]</code> <code>[client_email]</code> <code>[client_tel]</code> <code>[client_address]</code> <code>[matrys_block]</code></p>
                </div>
                <div class="coordonnees-wrap shortcode-list" style="border-left-color:#46b450;">
                    <h2>🛡️ RGPD</h2>
                    <p><code>[rgpd_mentions]</code> → Tableau traitements | <code>[rgpd_droits]</code> → Droits + CNIL | <code>[rgpd_cookies]</code> → WP Consent</p>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_forminator_forms()
    {
        if (!class_exists('Forminator_API')) return [];
        $forms = [];
        foreach (Forminator_API::get_forms(null, 1, 999) ?: [] as $f) {
            $forms[$f->id] = [
                'id' => $f->id,
                'name' => $f->settings['formName'] ?? $f->name,
                'entries' => count(Forminator_API::get_entries($f->id) ?: []),
                'data_types' => $this->detect_types($f->id),
            ];
        }
        return $forms;
    }
    
    private function detect_types($id)
    {
        $form = Forminator_API::get_form($id);
        $types = [];
        if ($form && !empty($form->fields)) {
            foreach ($form->fields as $f) {
                $t = $f->type ?? ($f->raw['type'] ?? 'text');
                $l = $this->get_field_type_label($t);
                if ($l && !in_array($l, $types)) $types[] = $l;
            }
        }
        return $types ?: [$this->t('form_data')];
    }
    
    private function guess_purpose($name)
    {
        $n = strtolower($name);
        if (strpos($n, 'contact') !== false) return 'Répondre à vos demandes';
        if (strpos($n, 'newsletter') !== false) return 'Envoi de newsletters';
        if (strpos($n, 'devis') !== false) return 'Traitement de devis';
        return 'Traitement de votre demande';
    }
    
    public function shortcode_client_email() { $e = get_option('client_email'); return $e ? '<a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>' : ''; }
    public function shortcode_client_tel() { $t = get_option('client_tel'); return $t ? '<a href="tel:'.$this->phone_href($t, get_option('client_country','FR')).'">'.esc_html($t).'</a>' : ''; }
    public function shortcode_client_address($a) { $ad = get_option('client_address'); if (!$ad) return ''; $a = shortcode_atts(['link'=>'yes'], $a); $h = nl2br(esc_html($ad)); return $a['link']==='yes' ? '<a href="https://www.google.com/maps?q='.urlencode($ad).'" target="_blank">'.$h.'</a>' : $h; }
    public function shortcode_site_title() { return esc_html(get_bloginfo('name')); }
    public function shortcode_site_link() { $u = home_url(); return '<a href="'.esc_url($u).'">'.esc_url($u).'</a>'; }
    public function shortcode_matrys_block() { return '<p><a href="'.esc_url($this->opt('matrys_url')).'" target="_blank">'.esc_html($this->opt('matrys_name')).'</a><br>'.nl2br(esc_html($this->opt('matrys_address'))).'<br>'.$this->t('tel').' : <a href="tel:'.$this->phone_href($this->opt('matrys_tel'), $this->opt('matrys_country')).'">'.esc_html($this->opt('matrys_tel')).'</a></p>'; }
    
    public function shortcode_rgpd_mentions($a)
    {
        $rgpd = get_option('rgpd_settings', []);
        $forms = $this->get_forminator_forms();
        $en = [];
        foreach ($forms as $id => $f) if (!empty($rgpd['forms'][$id]['enabled'])) { $f['config'] = $rgpd['forms'][$id]; $en[$id] = $f; }
        if (empty($en)) return '';
        
        ob_start();
        echo '<table style="width:100%;border-collapse:collapse;margin:20px 0"><thead><tr style="background:#f5f5f5">';
        echo '<th style="padding:12px;border:1px solid #ddd">'.$this->t('pc_table_treatment').'</th>';
        echo '<th style="padding:12px;border:1px solid #ddd">'.$this->t('pc_table_data').'</th>';
        echo '<th style="padding:12px;border:1px solid #ddd">'.$this->t('pc_table_purpose').'</th>';
        echo '<th style="padding:12px;border:1px solid #ddd">'.$this->t('pc_table_legal').'</th>';
        echo '<th style="padding:12px;border:1px solid #ddd">'.$this->t('pc_table_retention').'</th>';
        echo '</tr></thead><tbody>';
        foreach ($en as $f) {
            $c = $f['config'];
            echo '<tr><td style="padding:12px;border:1px solid #ddd"><strong>'.esc_html($c['name_override'] ?: $f['name']).'</strong></td>';
            echo '<td style="padding:12px;border:1px solid #ddd">'.esc_html(implode(', ', $f['data_types'])).'</td>';
            echo '<td style="padding:12px;border:1px solid #ddd">'.esc_html($c['purpose']).'</td>';
            echo '<td style="padding:12px;border:1px solid #ddd">'.esc_html($this->get_legal_basis_label($c['legal_basis'])).'</td>';
            echo '<td style="padding:12px;border:1px solid #ddd">'.esc_html($this->get_retention_label($c['retention'])).'</td></tr>';
        }
        echo '</tbody></table>';
        $r = $t = [];
        foreach ($en as $f) { if (!empty($f['config']['recipients'])) $r[] = $f['config']['recipients']; if (!empty($f['config']['third_party'])) $t[] = $f['config']['third_party']; }
        if ($r = array_unique($r)) echo '<p><strong>'.$this->t('pc_recipients').'</strong> '.esc_html(implode(', ', $r)).'</p>';
        if ($t = array_unique($t)) echo '<p><strong>'.$this->t('pc_third_party').'</strong> '.esc_html(implode(', ', $t)).'</p>';
        return ob_get_clean();
    }
    
    public function shortcode_rgpd_cookies() { return shortcode_exists('wpconsent_cookie_policy') ? do_shortcode('[wpconsent_cookie_policy]') : ''; }
    
    public function shortcode_rgpd_droits()
    {
        $e = get_option('client_email'); $ad = get_option('client_address');
        $n = get_bloginfo('name');
        ob_start();
        echo '<p>'.sprintf($this->t('rights_intro'), '<strong>'.esc_html($n).'</strong>').'</p><ul>';
        if ($e) echo '<li>'.sprintf($this->t('rights_email'), '<a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>').'</li>';
        if ($ad) echo '<li>'.$this->t('rights_mail').'<br><strong>'.esc_html($n).'</strong><br>'.nl2br(esc_html($ad)).'</li>';
        echo '</ul><p>'.$this->t('rights_list_intro').'</p>';
        echo '<p>'.sprintf($this->t('rights_dpa'), $this->t('dpa_name'), '<a href="'.$this->t('dpa_url').'" target="_blank">'.$this->t('dpa_url').'</a>').'</p>';
        return ob_get_clean();
    }
    
    public function shortcode_mentions_legales()
    {
        $site_name = get_bloginfo('name'); $u = home_url();
        $raison_sociale = get_option('client_raison_sociale') ?: $site_name;
        $e = get_option('client_email'); $ad = get_option('client_address'); $t = get_option('client_tel');
        $r = get_option('client_responsable') ?: $raison_sociale;
        $fj = get_option('client_forme_juridique');
        $fj_autre = get_option('client_forme_juridique_autre');
        if ($fj === 'Autre') {
            $fj_display = $fj_autre ?: '';
        } else {
            $fj_display = ($fj && isset(self::FORMES_JURIDIQUES[$fj]) && $fj !== '') ? self::FORMES_JURIDIQUES[$fj] : '';
        }
        $cap = get_option('client_capital');
        $siret = get_option('client_siret'); $rcs = get_option('client_rcs'); $tva = get_option('client_tva');
        $m_n = $this->opt('matrys_name'); $m_u = $this->opt('matrys_url');
        $m_ad = $this->opt('matrys_address'); $m_t = $this->opt('matrys_tel');
        $m_h = $this->phone_href($m_t, $this->opt('matrys_country'));
        
        ob_start();
        echo '<h2>'.$this->t('ml_editor').'</h2>';
        echo '<p>'.sprintf($this->t('ml_editor_intro'), '<a href="'.esc_url($u).'">'.esc_url($u).'</a>').'</p>';
        echo '<p><strong>'.esc_html($raison_sociale).'</strong>';
        if ($fj && $fj_display) echo '<br>'.esc_html($fj_display);
        if ($cap) echo ' au capital de '.esc_html($cap);
        if ($ad) echo '<br>'.nl2br(esc_html($ad));
        if ($e) echo '<br>'.$this->t('email').' : <a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>';
        if ($t) echo '<br>'.$this->t('tel').' : '.esc_html($t);
        if ($siret) echo '<br>SIRET : '.esc_html($siret);
        if ($rcs) echo '<br>'.esc_html($rcs);
        if ($tva) echo '<br>TVA : '.esc_html($tva);
        echo '</p><p><strong>'.$this->t('ml_responsible').'</strong> '.esc_html($r).'</p>';
        
        echo '<h2>'.$this->t('ml_hosting').'</h2>';
        echo '<p><a href="'.esc_url($m_u).'" target="_blank">'.esc_html($m_n).'</a><br>'.nl2br(esc_html($m_ad)).'<br>'.$this->t('tel').' : <a href="tel:'.esc_attr($m_h).'">'.esc_html($m_t).'</a></p>';
        
        echo '<h2>'.$this->t('ml_intellectual').'</h2><p>'.$this->t('ml_intellectual_text').'</p>';
        if ($e) echo '<p>'.sprintf($this->t('ml_intellectual_contact'), '<a href="mailto:'.esc_attr($e).'">'.esc_html($e).'</a>').'</p>';
        
        echo '<h2>'.$this->t('ml_info').'</h2>';
        echo '<p>'.$this->t('ml_info_text1').'</p><p>'.$this->t('ml_info_text2').'</p><p>'.$this->t('ml_info_text3').'</p>';
        
        echo '<h2>'.$this->t('ml_personal_data').'</h2><p>'.$this->t('ml_personal_data_text').'</p>';
        echo '<p>'.sprintf($this->t('ml_privacy_link'), '<a href="'.esc_url(get_privacy_policy_url()).'">'.$this->t('ml_privacy_page').'</a>').'</p>';
        return ob_get_clean();
    }
    
    public function shortcode_politique_complete()
    {
        $n = get_bloginfo('name');
        $u = home_url();
        $rgpd = get_option('rgpd_settings', []); $forms = $this->get_forminator_forms();
        $has = false; foreach ($forms as $id => $f) if (!empty($rgpd['forms'][$id]['enabled'])) $has = true;
        
        ob_start();
        echo '<p>'.sprintf($this->t('pc_intro'), '<strong>'.esc_html($n).'</strong>', '<a href="'.esc_url($u).'">'.esc_url($u).'</a>').'</p>';
        echo '<h2>'.$this->t('pc_data_collected').'</h2>';
        echo '<p>'.$this->t('pc_data_text1').'</p><p>'.$this->t('pc_data_text2').'</p><p>'.$this->t('pc_data_text3').'</p>';
        if ($has) { echo '<h2>'.$this->t('pc_treatments').'</h2>'; echo $this->shortcode_rgpd_mentions([]); }
        echo '<h2>'.$this->t('rights_title').'</h2>'; echo $this->shortcode_rgpd_droits();
        echo '<h2>'.$this->t('cookies_title').'</h2><h3>'.$this->t('cookies_info_title').'</h3><p>'.$this->t('cookies_info_text').'</p>';
        echo '<h3>'.$this->t('cookies_purpose_title').'</h3>'; echo $this->shortcode_rgpd_cookies();
        echo '<h3>'.$this->t('cookies_choices_title').'</h3><p>'.$this->t('cookies_choices_text').'</p>';
        echo '<ul><li><a href="https://support.microsoft.com/microsoft-edge/supprimer-les-cookies" target="_blank">Edge</a></li>';
        echo '<li><a href="https://support.apple.com/guide/safari/sfri11471/mac" target="_blank">Safari</a></li>';
        echo '<li><a href="https://support.google.com/chrome/answer/95647" target="_blank">Chrome</a></li>';
        echo '<li><a href="https://support.mozilla.org/kb/activer-desactiver-cookies" target="_blank">Firefox</a></li>';
        echo '<li><a href="https://help.opera.com/latest/web-preferences/#cookies" target="_blank">Opera</a></li></ul>';
        return ob_get_clean();
    }
    
    private function opt($k) { $v = get_option($k); return ($v !== false && $v !== '') ? $v : (self::DEFAULT_OPTIONS[$k] ?? ''); }
    private function phone_href($t, $c) { $code = self::COUNTRIES[$c]['code'] ?? '+33'; $clean = preg_replace('/[^0-9]/', '', $t); if (substr($clean,0,1)==='0') $clean = substr($clean,1); return $code.$clean; }
    public function on_activation() { foreach (self::DEFAULT_OPTIONS as $k => $v) if (get_option($k) === false) update_option($k, $v); }
}

add_action('plugins_loaded', ['Client_Coordonnees_RGPD_Plugin', 'init']);
