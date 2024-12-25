// maltyst-settings-page.ts
import { LitElement, html } from 'lit';
import { customElement, state } from 'lit/decorators.js';
import { provide } from '@lit/context';

import { settingsContext, SettingsContextValue } from './settings-context';

// Global object for this plugin, so we don't pollute a global namespace
window.maltyst = window.maltyst ?? {};



@customElement('maltyst-settings-page')
export class MaltystSettingsPage extends LitElement {
  // This holds the current context data (state, data, etc.)
  @state()
  private _contextValue: SettingsContextValue = {
    state: 'idle',
    data: null,
  };

  // Provide the context to child components
  @provide({ context: settingsContext })
  get contextValue() {
    return this._contextValue;
  }

  connectedCallback() {
    super.connectedCallback();
    this.fetchSettings();
  }

  async fetchSettings() {
    try {
      this._contextValue = { state: 'loading', data: null };
      const response = await fetch('/api/maltyst-settings');
      if (!response.ok) throw new Error(`HTTP error ${response.status}`);
      const data = await response.json();

      // On success
      this._contextValue = {
        state: 'success',
        data,
      };
    } catch (err: any) {
      // On error
      this._contextValue = {
        state: 'error',
        data: null,
        error: err.message ?? 'Fetch failed',
      };
    }
  }

  render() {
    // Optionally, you can do some top-level rendering
    // But primarily, we just slot child components
    return html`
      <div class="settings-page">
        <!-- 
          Child components will be placed here. 
          They can consume the settingsContext without prop drilling.
        -->
        <slot></slot>
      </div>
    `;
  }
}

