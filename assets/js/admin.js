/**
 * Coordonnées & RGPD - Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    var currentValues = {};
    
    // ==================== GESTION DES ONGLETS ====================
    
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        var formType = $(this).data('form');
        
        // Mise à jour des onglets
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mise à jour du contenu
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
        
        // Afficher/masquer le bon bouton submit
        if (formType === 'main') {
            $('#submit-main').show();
        } else {
            $('#submit-main').hide();
        }
    });
    
    // ==================== RGPD ACCORDION ====================
    
    $('.rgpd-form-header').on('click', function(e) {
        // Ne pas toggler si on clique sur le toggle switch
        if ($(e.target).closest('.toggle').length) return;
        $(this).closest('.rgpd-form').toggleClass('open');
    });
    
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
    
    // ==================== RECHERCHE SIRET ====================
    
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
    
    $('#btn-search-siret').on('click', function(e) {
        e.preventDefault();
        
        var siret = $('#siret_search').val().replace(/\s/g, '');
        
        if (siret.length < 9) {
            $('#siret-result').html('<div class="siret-result error"><p>⚠️ Veuillez saisir au moins 9 chiffres (SIREN) ou 14 chiffres (SIRET)</p></div>');
            return;
        }
        
        $('#siret-spinner').addClass('active');
        $('#btn-search-siret').prop('disabled', true);
        
        // Récupérer d'abord les valeurs actuelles
        $.post(ccrgpd.ajax_url, {
            action: 'ccrgpd_get_current',
            nonce: ccrgpd.nonce
        }, function(response) {
            if (response.success) {
                currentValues = response.data;
            }
            
            // Puis rechercher l'entreprise
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
                        html += '<tr>';
                        html += '<td>' + f.label + '</td>';
                        html += '<td>' + (f.val || '-') + '<span class="' + st.cls + '">' + st.txt + '</span></td>';
                        html += '</tr>';
                    }
                    html += '</table></div>';
                    
                    // Dirigeants
                    if (d.dirigeants && d.dirigeants.length > 0) {
                        html += '<div class="dirigeants-choice">';
                        html += '<strong>👤 Choisir le responsable de publication :</strong>';
                        for (var j = 0; j < d.dirigeants.length; j++) {
                            var checked = j === 0 ? ' checked' : '';
                            html += '<label>';
                            html += '<input type="radio" name="sel_dirigeant" value="' + j + '"' + checked + '> ';
                            html += d.dirigeants[j].full_name + ' — <em>' + d.dirigeants[j].qualite + '</em>';
                            html += '</label>';
                        }
                        html += '</div>';
                    }
                    
                    // Capital (non dispo via API)
                    html += '<div class="capital-input">';
                    html += '<strong>💰 Capital social</strong> <small>(non disponible via l\'API)</small><br>';
                    html += '<a href="' + d.annuaire_url + '" target="_blank" rel="noopener">👉 Consulter sur l\'Annuaire Entreprises</a><br><br>';
                    html += '<label>Capital : <input type="text" id="import_capital" value="' + (currentValues.client_capital || '') + '" placeholder="Ex: 10 000 €" style="width:180px"></label>';
                    html += '</div>';
                    
                    // Options d'import
                    html += '<div class="import-options">';
                    html += '<label><input type="checkbox" id="import_overwrite"> Écraser les données existantes</label>';
                    html += '<p class="description">Si décoché, seuls les champs vides seront remplis automatiquement.</p>';
                    html += '</div>';
                    
                    // Bouton import
                    html += '<div class="siret-actions">';
                    html += '<button type="button" id="btn-import" class="button button-primary button-large">📥 Importer les données</button>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    $('#siret-result').html(html).data('entreprise', d);
                    
                } else {
                    $('#siret-result').html('<div class="siret-result error"><p>❌ ' + r.data + '</p></div>');
                }
            });
        });
    });
    
    // Touche Entrée dans le champ SIRET
    $('#siret_search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#btn-search-siret').click();
        }
    });
    
    // ==================== IMPORT DES DONNÉES ====================
    
    $(document).on('click', '#btn-import', function() {
        var d = $('#siret-result').data('entreprise');
        if (!d) return;
        
        var overwrite = $('#import_overwrite').is(':checked');
        var dirIdx = parseInt($('input[name=sel_dirigeant]:checked').val()) || 0;
        var dirigeant = (d.dirigeants && d.dirigeants[dirIdx]) ? d.dirigeants[dirIdx].full_name : '';
        var capital = $('#import_capital').val();
        
        // Fonction pour remplir un champ
        function setField(name, value) {
            var $field = $('[name="' + name + '"]');
            if (!$field.length) return;
            
            if (overwrite || !$field.val()) {
                $field.val(value);
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
        
        // Forme juridique (select)
        var $formeSelect = $('#client_forme_juridique');
        if (overwrite || !$formeSelect.val()) {
            // Vérifier si la forme existe dans le select
            if ($formeSelect.find('option[value="' + d.forme_juridique + '"]').length) {
                $formeSelect.val(d.forme_juridique).trigger('change');
            } else {
                // Sinon, mettre "Autre" et remplir le champ texte
                $formeSelect.val('Autre').trigger('change');
                $('[name="client_forme_juridique_autre"]').val(d.forme_juridique);
            }
        }
        
        // Message de confirmation
        var mode = overwrite ? 'remplacées' : 'complétées';
        $('#siret-result').append(
            '<div style="margin-top:15px;padding:12px 15px;background:#d1e7dd;border:1px solid #badbcc;border-radius:4px;color:#0f5132">' +
            '✅ Données ' + mode + ' avec succès !<br><strong>N\'oubliez pas d\'enregistrer pour sauvegarder.</strong>' +
            '</div>'
        );
        
        // Scroll vers les champs
        $('html, body').animate({
            scrollTop: $('[name="client_raison_sociale"]').offset().top - 100
        }, 500);
    });
    
});
