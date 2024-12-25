import { ApiResponse } from '../../types/global'

//==========================================================================
// 2. Opt-in form submission handling
//==========================================================================

// Note - that depending on plugin configuration - this could be:
// a) direct optin (immediately add to the list)
// b) double optin (email confirmation needed)

// Elements
const optinForm = document.querySelector<HTMLFormElement>('.maltyst-optin-frm');
const optinFormSpinner = optinForm?.querySelector<HTMLElement>('.maltystloader');
const optinFormResponseArea = optinForm?.querySelector<HTMLElement>('.maltyst_result_msg');

const submitOptin = async (): Promise<void> => {
    // Prevent double submission
    if (window.maltyst?.inProgressOptin) {
        return;
    }
    window.maltyst = window.maltyst || {};
    window.maltyst.inProgressOptin = true;

    // Reset the form UI
    optinFormSpinner?.classList.remove('maltysthide');
    optinFormResponseArea?.classList.remove('success', 'error', 'maltysthide');
    if (optinFormResponseArea) optinFormResponseArea.textContent = '';

    try {
        // Collect form data
        const emailField = optinForm?.querySelector<HTMLInputElement>(`[name=${window.maltystData.MALTYST_PREFIX}_email]`);
        const email = emailField?.value;

        // Start the optin (double optin could be optional depending on maltyst settings)
        const response = await fetch(`${window.maltystData.MALTYST_ROUTE}/start-optin`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'maltystAjaxAcceptOptin',
                email: email,
                security: window.maltystData.nonce,
            }),
        });

        // Decode the json
        const result: ApiResponse = await response.json();

        // When status code is outside the range of 200–299
        if (!response.ok) {
            throw new Error(result?.error || 'An error occurred.');
        }

        // Success
        optinFormResponseArea?.classList.add('success');
        optinFormResponseArea?.classList.remove('maltysthide');
        if (optinFormResponseArea) optinFormResponseArea.textContent = result.message;

    } catch (error) {
        // Error handling
        const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
        optinFormResponseArea?.classList.add('error');
        optinFormResponseArea?.classList.remove('maltysthide');
        if (optinFormResponseArea) optinFormResponseArea.textContent = errorMessage;
    } finally {
        // Reset spinner and allow submissions again
        optinFormSpinner?.classList.add('maltysthide');
        window.maltyst.inProgressOptin = false;
    }
};

export default function initOptinStart(): void {
    if (optinForm) {
        optinForm.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();

            submitOptin();
        });
    }
}
