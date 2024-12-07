import initPreferenceCenter from './components/preference-center.mjs';
import initDoubleOptinStart from './components/double-optin-start.mjs';
import initDoubleOptinFinish from './components/double-optin-finish.mjs';


// Global object for this plugin, so we don't pollute a global namespace
window.maltyst = window.maltyst ?? {};

document.addEventListener('DOMContentLoaded', () => {

    // Global object for this plugin, so we don't pollute a global namespace
    window.maltyst = window.maltyst ?? {};

    // Register functionalities  (only if that html is found on the page)
    initPreferenceCenter();
    initDoubleOptinStart();
    initDoubleOptinFinish();
});