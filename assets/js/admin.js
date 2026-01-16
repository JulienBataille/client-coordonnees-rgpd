/**
 * Coordonnées & RGPD - Admin JS
 */
jQuery(document).ready(function($) {
    
    var currentValues = {};
    
    // ==================== GESTION DES ONGLETS ====================
    
    // Fonction pour mettre à jour la visibilité du bouton
    function updateSubmitButton(target) {
        if (target === '#tab-rgpd' || target === '#tab-shortcodes') {
            $('#submit-main').hide();
        } else {
            $('#submit-main').show();
        }
    }
    
    // Initialisation: afficher le bouton sur l'onglet initial
    var initialTab = $('.nav-tab-active').attr('href') || '#tab-coordonnees';
    updateSubmitButton(initialTab);
    
    // Clic sur les onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Changer l'onglet actif
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Afficher le contenu correspondant
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Mettre à jour le bouton submit
        updateSubmitButton(target);
    });
    
    // ==================== ACCORDÉON RGPD ====================
    $('.rgpd-form-header').on('click', function(e) {
        // Ne pas toggle si on clique sur le checkbox
        if ($(e.target).closest('.toggle').length) return;
        $(this).closest('.rgpd-form').toggleClass('open');
    });
    
    // Toggle enabled class quand on active/désactive un formulaire
    $('.rgpd-form .toggle input').on('change', function() {
        $(this).closest('.rgpd-form').toggleClass('enabled', this.checked);
    });
    
    // ==================== FORME JURIDIQUE "AUTRE" ====================
    function toggleFormeAutre() {
        var isAutre = $('#client_forme_juridique').val() === 'Autre';
        $('#forme_autre_wrap').toggle(isAutre);
    }
    $('#client_forme_juridique').on('change', toggleFormeAutre);
    toggleFormeAutre(); // Init
    
    // ==================== HELPER POUR STATUT IMPORT ====================
    function getStatus(field, newVal) {
        var current = currentValues[field] || '';
        if (!newVal) return { cls: '', txt: '' };
        if (!current) return { cls: 'status-new', txt: '← nouveau' };
        if (current === newVal) return { cls: 'status-existing', txt: '✓ identique' };
        return { 
            cls: 'status-replace', 
            txt: '⚠️ remplace: ' + current.substring(0, 30) + (current.length > 30 ? '...' : '') 
        };
    }
    
    // ==================== RECHERCHE SIRET ====================
    $('#btn-search-siret').on('click', function(e) {
        e.preventDefault();
        
        var siret = $('#siret_search').val().replace(/\s/g, '');
        if (siret.length < 9) {
            $('#siret-result').html('<div class="siret-result error"><p>⚠️ Veuillez entrer un SIRET ou SIREN valide (minimum 9 chiffres)</p></div>');
            return;
        }
        
        $('#siret-spinner').addClass('active');
        $('#btn-search-siret').prop('disabled', true);
        
        // D'abord récupérer les valeurs actuelles
        $.post(ccrgpd.ajax_url, {
            action: 'ccrgpd_get_current',
            nonce: ccrgpd.nonce
        }, function(response) {
            if (response.success) {
                currentValues = response.data;
            }
            
            // Ensuite rechercher l'entreprise
            $.post(ccrgpd.ajax_url, {
                action: 'ccrgpd_search_siret',
                nonce: ccrgpd.nonce,
                siret: siret
            }, function(r) {
                $('#siret-spinner').removeClass('active');
                $('#btn-search-siret').prop('disabled', false);
                
                if (r.success) {
                    var d = r.data;
                    var html = '<div class="siret-result success">';
                    html += '<h4>✅ ' + d.raison_sociale + '</h4>';
                    
                    // Tableau d'aperçu
                    html += '<div class="import-preview"><table>';
                    var fields = [
                        { key: 'client_raison_sociale', label: 'Raison sociale', val: d.raison_sociale },
                        { key: 'client_forme_juridique', label: 'Forme juridique', val: d.forme_juridique },
                        { key: 'client_address_siege', label: 'Adresse siège', val: d.adresse_siege },
                        { key: 'client_siret', label: 'SIRET', val: d.siret },
                        { key: 'client_siren', label: 'SIREN', val: d.siren },
                        { key: 'client_tva', label: 'TVA Intracommunautaire', val: d.tva },
                        { key: 'client_rcs', label: 'RCS', val: d.rcs }
                    ];
                    
                    for (var i = 0; i < fields.length; i++) {
                        var f = fields[i];
                        var st = getStatus(f.key, f.val);
                        html += '<tr><td>' + f.label + '</td><td>' + (f.val || '-') + ' <span class="' + st.cls + '">' + st.txt + '</span></td></tr>';
                    }
                    html += '</table></div>';
                    
                    // Choix du dirigeant
                    if (d.dirigeants && d.dirigeants.length > 0) {
                        html += '<div class="dirigeants-choice">';
                        html += '<strong>👤 Choisir le responsable de publication :</strong>';
                        for (var j = 0; j < d.dirigeants.length; j++) {
                            var checked = j === 0 ? ' checked' : '';
                            html += '<label><input type="radio" name="sel_dirigeant" value="' + j + '"' + checked + '> ';
                            html += d.dirigeants[j].full_name + ' — <em>' + d.dirigeants[j].qualite + '</em></label>';
                        }
                        html += '</div>';
                    }
                    
                    // Capital (non dispo via API)
                    html += '<div class="capital-input">';
                    html += '<strong>💰 Capital social</strong> <small>(non disponible via l\'API)</small><br>';
                    html += '<a href="' + d.annuaire_url + '" target="_blank" rel="noopener">👉 Consulter sur Annuaire Entreprises</a><br><br>';
                    html += '<label>Capital : <input type="text" id="import_capital" value="' + (currentValues.client_capital || '') + '" placeholder="Ex: 10 000 €" style="width:180px"></label>';
                    html += '</div>';
                    
                    // Options d'import
                    html += '<div class="import-options">';
                    html += '<label><input type="checkbox" id="import_overwrite"> Écraser les données existantes</label>';
                    html += '<p class="description">Si décoché, seuls les champs vides seront remplis</p>';
                    html += '</div>';
                    
                    // Bouton d'import
                    html += '<div class="siret-actions">';
                    html += '<button type="button" id="btn-import" class="button button-primary">📥 Importer les données</button>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    $('#siret-result').html(html).data('entreprise', d);
                    
                } else {
                    $('#siret-result').html('<div class="siret-result error"><p>❌ ' + r.data + '</p></div>');
                }
            });
        });
    });
    
    // ==================== IMPORT DES DONNÉES ====================
    $(document).on('click', '#btn-import', function() {
        var d = $('#siret-result').data('entreprise');
        if (!d) return;
        
        var overwrite = $('#import_overwrite').is(':checked');
        var dirIdx = $('input[name=sel_dirigeant]:checked').val() || 0;
        var dirigeant = '';
        if (d.dirigeants && d.dirigeants[dirIdx]) {
            dirigeant = d.dirigeants[dirIdx].full_name;
            if (d.dirigeants[dirIdx].qualite) {
                dirigeant += ' (' + d.dirigeants[dirIdx].qualite + ')';
            }
        }
        var capital = $('#import_capital').val();
        
        // Fonction pour remplir un champ
        function setField(name, value) {
            var $el = $('[name="' + name + '"]');
            if (overwrite || !$el.val()) {
                $el.val(value);
            }
        }
        
        // Remplir les champs
        setField('client_raison_sociale', d.raison_sociale);
        setField('client_address_siege', d.adresse_siege);
        setField('client_siret', d.siret);
        setField('client_siren', d.siren);
        setField('client_tva', d.tva);
        setField('client_rcs', d.rcs);
        setField('client_responsable', dirigeant);
        
        if (capital) {
            setField('client_capital', capital);
        }
        
        // Forme juridique
        var $select = $('#client_forme_juridique');
        if (overwrite || !$select.val()) {
            // Vérifier si la forme existe dans le select
            if ($select.find('option[value="' + d.forme_juridique + '"]').length) {
                $select.val(d.forme_juridique).trigger('change');
            } else {
                // Sinon, mettre "Autre" et remplir le champ
                $select.val('Autre').trigger('change');
                $('[name="client_forme_juridique_autre"]').val(d.forme_juridique);
            }
        }
        
        // Message de confirmation
        var mode = overwrite ? 'remplacées' : 'complétées';
        $('#siret-result').append('<div style="margin-top:20px;padding:15px;background:#d4edda;border:1px solid #28a745;border-radius:4px;color:#155724"><strong>✅ Données ' + mode + ' avec succès !</strong><br>N\'oubliez pas de cliquer sur "Enregistrer" pour sauvegarder.</div>');
        
        // Scroll vers le premier champ modifié
        $('html, body').animate({
            scrollTop: $('[name="client_raison_sociale"]').offset().top - 100
        }, 500);
    });
    
});
