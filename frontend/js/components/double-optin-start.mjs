//=========================================================================
// 2. Email optin form submission handling
//=========================================================================
//Elements
var optinForm             = $('.maltyst-optin-frm');
var optinFormSpinner      = optinForm.find('.maltystloader');
var optinFormResponseArea = optinForm.find('.maltyst_result_msg');

var submitOptin = function() {
    //Marking as in progress - so we dont double submit
    if (window.maltyst.inProgressOptin) {
        return;
    }
    window.maltyst.inProgressOptin = true;

    //Reset an error
    optinFormSpinner.removeClass('maltysthide');
    optinFormResponseArea.removeClass('success');
    optinFormResponseArea.removeClass('error');
    optinFormResponseArea.addClass('maltysthide').text('');

    // Submit an optin
    $.post(
        maltyst_data.ajax_url,
        {
            'action':  'maltystAjaxAcceptOptin',
            'email':    optinForm.find('[name=' + maltyst_data.prefix + '_email]').val(),
            'security': maltyst_data.nonce
        }
    ).done (function(ajaxResponse, status, xhr) {
        optinFormResponseArea.addClass('success');
        optinFormResponseArea.removeClass('maltysthide');
        optinFormResponseArea.text(ajaxResponse.message);

    } ).fail( function( response, status, error ) {

        optinFormResponseArea.addClass('error');
        optinFormResponseArea.removeClass('maltysthide');

        var ajaxResponse = $.parseJSON(response.responseText);
        if (ajaxResponse.data.error) {
            optinFormResponseArea.text(ajaxResponse.data.error);
        }
        
    }).always(function(){
        optinFormSpinner.addClass('maltysthide');
        window.maltyst.inProgressOptin = false;
    });
};

optinForm.on('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    submitOptin();
});