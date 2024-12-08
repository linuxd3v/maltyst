//=========================================================================
// 2. Email opt-in form submission handling
//=========================================================================

// Elements
const optinForm = document.querySelector('.maltyst-optin-frm');
const optinFormSpinner = optinForm?.querySelector('.maltystloader');
const optinFormResponseArea = optinForm?.querySelector('.maltyst_result_msg');

const submitOptin = async () => {
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
        const emailField = optinForm.querySelector(`[name=${maltyst_data.prefix}_email]`);
        const email = emailField?.value;
        const response = await fetch(maltyst_data.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'maltystAjaxAcceptOptin',
                email: email,
                security: maltyst_data.nonce,
            }),
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result?.data?.error || 'An error occurred.');
        }

        // Success
        optinFormResponseArea?.classList.add('success');
        optinFormResponseArea?.classList.remove('maltysthide');
        if (optinFormResponseArea) optinFormResponseArea.textContent = result.message;

    } catch (error) {
        // Error handling
        optinFormResponseArea?.classList.add('error');
        optinFormResponseArea?.classList.remove('maltysthide');
        if (optinFormResponseArea) optinFormResponseArea.textContent = error.message;
    } finally {
        // Reset spinner and allow submissions again
        optinFormSpinner?.classList.add('maltysthide');
        window.maltyst.inProgressOptin = false;
    }
};

export default function initDoubleOptinStart() {
    if (optinForm) {
        optinForm.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();

            submitOptin();
        });
    }
}
