document.addEventListener('DOMContentLoaded', () => {

    // Ensure global plugin object exists.
    window.maltyst = window.maltyst ?? {};

    // Utility to get query parameter value by name.
    const getQueryParameter = (name) => {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    };

    // Check if a specific query parameter exists.
    const isQueryParameterPresent = (name) => {
        const params = new URLSearchParams(window.location.search);
        return params.has(name);
    };

    // Retrieve or cache the maltyst contact unique identifier.
    const getMaltystContactUqid = () => {
        if (!window.maltyst.maltystContactUqid) {
            window.maltyst.maltystContactUqid = getQueryParameter('maltyst_contact_uqid');
        }
        return window.maltyst.maltystContactUqid;
    };









    //=========================================================================
    // 1. Preference center
    //=========================================================================
    var pcContainer         = $('.maltyst-preference-center');
    var pcContainerForm     = pcContainer.find('form');
    var pcContainerSpinner  = pcContainerForm.find('.maltystloader');
    var pcContainerResponse = pcContainerForm.find('.maltyst_result_msg');


    var pullAccountInfo = function() {

        //Marking as in progress - so we dont double pull
        if (window.maltyst.inProgressPcPulling) {
            return;
        }
        window.maltyst.inProgressPcPulling = true;

        //Reset state
        pcContainerSpinner.removeClass('maltysthide');
        pcContainerResponse.removeClass('success');
        pcContainerResponse.removeClass('error');
        pcContainerResponse.addClass('maltysthide').text('');

        $.ajax({
            method: 'GET',
            url: maltyst_data.ajax_url,
            data: {
                'action': 'maltystFetchGetSubscriptions',
                'maltystContactUqid': getMaltystContactUqid(),
                'security': maltyst_data.nonce
            }
        }).done (function(ajaxResponse, status, xhr) {

            //Mark request as done
            pcContainerSpinner.addClass('maltysthide');
            window.maltyst.inProgressPcPulling = false;

            //Render segment names
            for (var i = 0; i < ajaxResponse.pcSegments.length; i++) {
                var pcSegment = ajaxResponse.pcSegments[i];
                var segmentHtml = pcContainer.find('.maltysttemplates').find('.maltyst-segment-li').clone();

                //Populating name, description and value
                segmentHtml.find('.sname').html(pcSegment.name);
                segmentHtml.find('.sdescription').html(pcSegment.description);
                segmentHtml.find('input:checkbox').val(pcSegment.alias);

                //Only checking the checkbox if it's in a list of segments user is in
                if ($.inArray(pcSegment.alias, ajaxResponse.userAliases) !== -1) {
                    segmentHtml.find('input:checkbox').prop('checked', true);
                }

                pcContainer.find('.maltyst-segments').append(segmentHtml);
            }

            //All rendered - show 'Save' button
            pcContainerForm.find('[name="maltyst_submit_btn"]').removeClass('maltysthide');
            pcContainerForm.find('[name="maltyst_refresh_btn"]').addClass('maltysthide');
            pcContainerForm.find('.maltyst-segments-all').removeClass('maltysthide');

            //If unsubscribe varaible is present - let's automatically trigger unsubscribe
            if (isQueryParameterPresent('unsubscribe-from-all')) {
                console.log('present');
                $('.maltyst-unsubscribe-all').trigger('click');
                updateAccountInfo();
            }

        } ).fail( function( response, status, error ) {

            pcContainerResponse.addClass('error').removeClass('maltysthide');
                
            var ajaxResponse = $.parseJSON(response.responseText);
            if (ajaxResponse.data.error) {
                pcContainerResponse.text(ajaxResponse.data.error);
            }


            //Something went wrong - only show 'Refresh' button
            pcContainerForm.find('[name="maltyst_refresh_btn"]').removeClass('maltysthide');

            //Mark request as done
            pcContainerSpinner.addClass('maltysthide');
            window.maltyst.inProgressPcPulling = false;
        });
    };



    var updateAccountInfo = function() {
        //Marking as in progress - so we dont double submit
        if (window.maltyst.inProgressPcSubmission) {
            return;
        }
        window.maltyst.inProgressPcSubmission = true;

        console.log('pcContainerSpinner', pcContainerSpinner);
        //Reset an error
        pcContainerSpinner.removeClass('maltysthide');
        pcContainerResponse.removeClass('success');
        pcContainerResponse.removeClass('error');
        pcContainerResponse.addClass('maltysthide').text('');

        //Getting values of all checked checkboxes
        var checkedSnames = pcContainerForm.find('input[type=checkbox]:checked').map(function(){
            return this.value;
        }).get();

        $.ajax({
            method: 'POST',
            url: maltyst_data.ajax_url,
            data: {
                'action':   'maltystUpdateSubscriptions',
                'snames':    checkedSnames,
                'maltystContactUqid': getMaltystContactUqid(),
                'security':  maltyst_data.nonce
            }
        }).done (function(ajaxResponse, status, xhr) {

            pcContainerResponse.addClass('success').removeClass('maltysthide');
            pcContainerResponse.text(ajaxResponse.message);

        } ).fail( function( response, status, error ) {
            pcContainerResponse.addClass('error').removeClass('maltysthide');
            
            var ajaxResponse = $.parseJSON(response.responseText);
            if (ajaxResponse.data.error) {
                pcContainerResponse.text(ajaxResponse.data.error);
            }
            
        }).always(function(){
            pcContainerSpinner.addClass('maltysthide');
            window.maltyst.inProgressPcSubmission = false;
        });
    };




    //Preference center initialization
    if (pcContainer.length) {
        
        //Pull account information to construct unsubscribe form
        pullAccountInfo();

        pcContainer.find('[name="maltyst_refresh_btn"]').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            pullAccountInfo();
        });

        pcContainer.find('form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();

            updateAccountInfo()
        });


        //Unsubscribe all click
        $('.maltyst-unsubscribe-all').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            pcContainer.find('form').find('input:checkbox').prop('checked', false);
        });
    };










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


    if (maltystConfirmation.length) {
        submitOptinConfirmation();
    }



});