import { getQueryParameter } from './utils.mjs';

//=========================================================================
// 3. Optin confirmation form
//=========================================================================
var maltystConfirmation         = $('.maltyst-confirmation-cnt');
var maltystConfirmationSpinner  = maltystConfirmation.find('.maltystloader');
var maltystConfirmationResponse = maltystConfirmation.find('.maltyst_result_msg');

var submitOptinConfirmation = function() {
    maltystConfirmationSpinner.removeClass('maltysthide');

    $.ajax({
        method: 'POST',
        url: maltyst_data.ajax_url,
        data: {
            'action':                       'maltystFetchPostOptinConfirmation',
            'maltyst_optin_confirmation_token': getQueryParameter('maltyst_optin_confirmation_token'),
            'security':                      maltyst_data.nonce
        }
    }).done (function(ajaxResponse, status, xhr) {

        maltystConfirmationResponse.addClass('success').removeClass('maltysthide');
        maltystConfirmationResponse.text(ajaxResponse.message);

    } ).fail( function( response, status, error ) {
        maltystConfirmationResponse.addClass('error').removeClass('maltysthide');
        
        var ajaxResponse = $.parseJSON(response.responseText);
        if (ajaxResponse.data.error) {
            maltystConfirmationResponse.text(ajaxResponse.data.error);
        }
        
    }).always(function(){
        maltystConfirmationSpinner.addClass('maltysthide');
    });
};


export function initDoubleOptinFinish() {
    if (maltystConfirmation.length) {
        submitOptinConfirmation();
    }
}