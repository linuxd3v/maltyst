import { getQueryParameter } from './utils';
import { ApiResponse } from '../types/global'

//=========================================================================
// 3. Opt-in Confirmation Form
//=========================================================================

// Cache elements
const maltystConfirmation = document.querySelector<HTMLElement>('.maltyst-confirmation-cnt');
const maltystConfirmationSpinner = maltystConfirmation?.querySelector<HTMLElement>('.maltystloader');
const maltystConfirmationResponse = maltystConfirmation?.querySelector<HTMLElement>('.maltyst_result_msg');

// Submit opt-in confirmation using modern fetch API
const submitOptinConfirmation = async (): Promise<void> => {
    if (!maltystConfirmationSpinner || !maltystConfirmationResponse) return;

    // Show the spinner
    maltystConfirmationSpinner.classList.remove('maltysthide');

    try {
        // Send POST request
        const response = await fetch(window.maltystData.fetch_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'maltystFetchPostOptinConfirmation',
                maltyst_optin_confirmation_token: getQueryParameter('maltyst_optin_confirmation_token'),
                security: window.maltystData.nonce,
            }),
        });

        const responseData: ApiResponse = await response.json();

        // When status code is outside the range of 200–299
        if (!response.ok) {
            throw new Error(responseData?.error || 'An error occurred');
        }


        // Show success message
        maltystConfirmationResponse.classList.add('success');
        maltystConfirmationResponse.classList.remove('maltysthide');
        maltystConfirmationResponse.textContent = responseData.message;
    } catch (error) {
        // Show error message
        const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
        maltystConfirmationResponse.classList.add('error');
        maltystConfirmationResponse.classList.remove('maltysthide');
        maltystConfirmationResponse.textContent = errorMessage;
    } finally {
        // Hide the spinner
        maltystConfirmationSpinner.classList.add('maltysthide');
    }
};

// Initialize the double opt-in finish process
export default function initDoubleOptinFinish(): void {
    if (maltystConfirmation) {
        submitOptinConfirmation();
    }
}
