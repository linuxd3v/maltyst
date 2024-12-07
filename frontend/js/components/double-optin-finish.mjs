import { getQueryParameter } from './utils.mjs';

//=========================================================================
// 3. Opt-in Confirmation Form
//=========================================================================

// Cache elements
const maltystConfirmation = document.querySelector('.maltyst-confirmation-cnt');
const maltystConfirmationSpinner = maltystConfirmation?.querySelector('.maltystloader');
const maltystConfirmationResponse = maltystConfirmation?.querySelector('.maltyst_result_msg');

// Submit opt-in confirmation using modern fetch API
const submitOptinConfirmation = async () => {
    if (!maltystConfirmationSpinner || !maltystConfirmationResponse) return;

    // Show the spinner
    maltystConfirmationSpinner.classList.remove('maltysthide');

    try {
        // Send POST request
        const response = await fetch(maltyst_data.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'maltystFetchPostOptinConfirmation',
                maltyst_optin_confirmation_token: getQueryParameter('maltyst_optin_confirmation_token'),
                security: maltyst_data.nonce,
            }),
        });

        // Parse the JSON response
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData?.data?.error || 'An error occurred');
        }

        const responseData = await response.json();

        // Show success message
        maltystConfirmationResponse.classList.add('success');
        maltystConfirmationResponse.classList.remove('maltysthide');
        maltystConfirmationResponse.textContent = responseData.message;
    } catch (error) {
        // Show error message
        maltystConfirmationResponse.classList.add('error');
        maltystConfirmationResponse.classList.remove('maltysthide');
        maltystConfirmationResponse.textContent = error.message;
    } finally {
        // Hide the spinner
        maltystConfirmationSpinner.classList.add('maltysthide');
    }
};

// Initialize the double opt-in finish process
export function initDoubleOptinFinish() {
    if (maltystConfirmation) {
        submitOptinConfirmation();
    }
}
