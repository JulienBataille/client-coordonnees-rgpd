jQuery(document).ready(function($){
    var currentValues={};
    $('.nav-tab').on('click',function(e){e.preventDefault();$('.nav-tab').removeClass('nav-tab-active');$(this).addClass('nav-tab-active');$('.tab-content').removeClass('active');$($(this).attr('href')).addClass('active');});
    $('.rgpd-form-header').on('click',function(e){if($(e.target).closest('.toggle').length)return;$(this).closest('.rgpd-form').toggleClass('open');});
    $('.rgpd-form .toggle input').on('change',function(){$(this).closest('.rgpd-form').toggleClass('enabled',this.checked);});
    function toggleFormeAutre(){$('#forme_autre_wrap').toggle($('#client_forme_juridique').val()==='Autre');}
    $('#client_forme_juridique').on('change',toggleFormeAutre);toggleFormeAutre();
    function getStatus(field,newVal){var current=currentValues[field]||'';if(!newVal)return{cls:'',txt:''};if(!current)return{cls:'status-new',txt:'← nouveau'};if(current===newVal)return{cls:'status-existing',txt:'✓ identique'};return{cls:'status-replace',txt:'⚠️ remplace: '+current.substring(0,25)+(current.length>25?'...':'')};}
    $('#btn-search-siret').on('click',function(e){
        e.preventDefault();
        var siret=$('#siret_search').val().replace(/\s/g,'');
        if(siret.length<9){$('#siret-result').html('<div class="siret-result error"><p>⚠️ SIRET/SIREN requis (min 9 chiffres)</p></div>');return;}
        $('#siret-spinner').addClass('active');$('#btn-search-siret').prop('disabled',true);
        $.post(ccrgpd.ajax_url,{action:'ccrgpd_get_current',nonce:ccrgpd.nonce},function(cv){
            if(cv.success)currentValues=cv.data;
            $.post(ccrgpd.ajax_url,{action:'ccrgpd_search_siret',nonce:ccrgpd.nonce,siret:siret},function(r){
                $('#siret-spinner').removeClass('active');$('#btn-search-siret').prop('disabled',false);
                if(r.success){
                    var d=r.data;
                    var html='<div class="siret-result success"><h4>✅ '+d.raison_sociale+'</h4><div class="import-preview"><table>';
                    var fields=[{key:'client_raison_sociale',label:'Raison sociale',val:d.raison_sociale},{key:'client_forme_juridique',label:'Forme juridique',val:d.forme_juridique},{key:'client_address_siege',label:'Adresse siège',val:d.adresse_siege},{key:'client_siret',label:'SIRET',val:d.siret},{key:'client_siren',label:'SIREN',val:d.siren},{key:'client_tva',label:'TVA Intracom.',val:d.tva},{key:'client_rcs',label:'RCS',val:d.rcs}];
                    for(var i=0;i<fields.length;i++){var f=fields[i];var st=getStatus(f.key,f.val);html+='<tr><td>'+f.label+'</td><td>'+f.val+' <span class="'+st.cls+'">'+st.txt+'</span></td></tr>';}
                    html+='</table></div>';
                    if(d.dirigeants&&d.dirigeants.length){html+='<div class="dirigeants-choice"><strong>👤 Responsable :</strong>';for(var j=0;j<d.dirigeants.length;j++){var chk=j===0?' checked':'';html+='<label><input type="radio" name="sel_dir" value="'+j+'"'+chk+'> '+d.dirigeants[j].full_name+' — '+d.dirigeants[j].qualite+'</label>';}html+='</div>';}
                    html+='<div class="capital-input"><strong>💰 Capital social</strong> (non dispo via API)<br><a href="'+d.annuaire_url+'" target="_blank">👉 Consulter Annuaire Entreprises</a><br><br><label>Capital : <input type="text" id="import_capital" value="'+(currentValues.client_capital||'')+'" placeholder="Ex: 10 000 €" style="width:150px"></label></div>';
                    html+='<div class="import-options"><label><input type="checkbox" id="overwrite"> Écraser les données existantes</label></div>';
                    html+='<div class="siret-actions"><button type="button" id="btn-import" class="button button-primary">📥 Importer</button></div></div>';
                    $('#siret-result').html(html).data('entreprise',d);
                }else{$('#siret-result').html('<div class="siret-result error"><p>❌ '+r.data+'</p></div>');}
            });
        });
    });
    $(document).on('click','#btn-import',function(){
        var d=$('#siret-result').data('entreprise');if(!d)return;
        var overwrite=$('#overwrite').is(':checked');
        var dirIdx=$('input[name=sel_dir]:checked').val()||0;
        var dirigeant=d.dirigeants&&d.dirigeants[dirIdx]?d.dirigeants[dirIdx].full_name:'';
        var capital=$('#import_capital').val();
        function setVal(name,value){var $el=$('[name="'+name+'"]');if(overwrite||!$el.val())$el.val(value);}
        setVal('client_raison_sociale',d.raison_sociale);setVal('client_address_siege',d.adresse_siege);setVal('client_siret',d.siret);setVal('client_siren',d.siren);setVal('client_tva',d.tva);setVal('client_rcs',d.rcs);setVal('client_responsable',dirigeant);if(capital)setVal('client_capital',capital);
        var $select=$('#client_forme_juridique');
        if(overwrite||!$select.val()){if($select.find('option[value="'+d.forme_juridique+'"]').length){$select.val(d.forme_juridique).trigger('change');}else{$select.val('Autre').trigger('change');$('[name="client_forme_juridique_autre"]').val(d.forme_juridique);}}
        var mode=overwrite?'remplacées':'complétées';
        $('#siret-result').append('<div style="margin-top:15px;padding:10px;background:#d4edda;border-radius:4px">✅ Données '+mode+' ! <strong>Pensez à enregistrer</strong></div>');
        $('html,body').animate({scrollTop:$('[name="client_raison_sociale"]').offset().top-50},500);
    });
});
