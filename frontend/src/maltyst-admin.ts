import initPreferenceCenter from './public-components/preference-center/preference-center';
import initOptinStart from './public-components/optin-start/optin-start';
import initDoubleOptinFinish from './public-components/process-doubleoptin-confirmation/process-doubleoptin-confirmation';

// Global object for this plugin, so we don't pollute a global namespace
window.maltyst = window.maltyst ?? {};

document.addEventListener('DOMContentLoaded', () => {

    // Global object for this plugin, so we don't pollute a global namespace
    window.maltyst = window.maltyst ?? {};

    // Register functionalities (only if that HTML is found on the page)
    initPreferenceCenter();
    initOptinStart();
    initDoubleOptinFinish();
});
