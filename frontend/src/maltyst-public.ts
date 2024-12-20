import initPreferenceCenter from './public-components/preference-center/preference-center';
import initDoubleOptinStart from './public-components/double-optin-start/double-optin-start';
import initDoubleOptinFinish from './public-components/double-optin-finish/double-optin-finish';

// Global object for this plugin, so we don't pollute a global namespace
window.maltyst = window.maltyst ?? {};

document.addEventListener('DOMContentLoaded', () => {

    // Global object for this plugin, so we don't pollute a global namespace
    window.maltyst = window.maltyst ?? {};

    // Register functionalities (only if that HTML is found on the page)
    initPreferenceCenter();
    initDoubleOptinStart();
    initDoubleOptinFinish();
});
