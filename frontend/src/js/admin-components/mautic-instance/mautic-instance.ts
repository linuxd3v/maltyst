import { LitElement, html } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import {SettingsManager} from '../../lib/settings-manager';
import { state } from 'lit/decorators.js';
import { handleInput } from '../../lib/utils';


@customElement('mautic-instance')
export class MauticInstance extends LitElement {


  // State /variables
  //===========================================================================
  static readonly MALTYST_COMP_NAME = 'mautic-instance';

  @property({ type: Boolean }) 
  maltystMauticIsApiValid: boolean = false;
  
  @state()
  private compStatus: string = '';

  private settingsManager: SettingsManager;

  // Actual component settings:  
  @property({ type: String })
  maltystMauticApiUrl: string = '';

  @property({ type: String })
  maltystMauticBasicUsername: string = '';

  @property({ type: String })
  maltystMauticBasicPassword: string = '';







  // Constructor && methods
  //===========================================================================
  constructor() {
    super();
    this.settingsManager = new SettingsManager();
  }




  // A helper to batch and dispatch changes
  private dispatchMauticDataLoaded() {
    const data: Record<string, string> = {
      maltystMauticApiUrl: this.maltystMauticApiUrl,
      maltystMauticBasicUsername: this.maltystMauticBasicUsername,
      maltystMauticBasicPassword: this.maltystMauticBasicPassword,
    };

    this.dispatchEvent(new CustomEvent('mautic-data-loaded', { 
      detail: { data }, 
      bubbles: true, // Allows the event to bubble up through the DOM
      composed: true // Allows the event to cross the shadow DOM boundary
    }));
  }




  get data(): Record<string, any> {
    return {
      maltystMauticApiUrl: this.maltystMauticApiUrl,
      maltystMauticBasicUsername: this.maltystMauticBasicUsername,
      maltystMauticBasicPassword: this.maltystMauticBasicPassword,
    };
  }


  // Lifecycle method called when the component is added to the DOM
  async connectedCallback() {
    super.connectedCallback(); // Ensure parent class methods are called

    try {
      this.compStatus = 'loading';

      // Fetch settings for this component: 
      let settings: Record<string, any> | null = await this.settingsManager.loadSettings(MauticInstance.MALTYST_COMP_NAME);


      if (settings) {
        this.maltystMauticApiUrl = this.maltystMauticApiUrl;
        this.maltystMauticBasicUsername = this.maltystMauticBasicUsername;
        this.maltystMauticBasicPassword = this.maltystMauticBasicPassword;

        // Inform the parent component
        this.dispatchMauticDataLoaded();
      }
    } catch (error) {
      this.compStatus = 'error';
      console.error('Error loading settings or retrieving Mautic status:', error);
    }
  }






  private saveSettings() {
    const toSave: Record<string, string> = {
      'maltystMauticApiUrl': this.maltystMauticApiUrl,
      'maltystMauticBasicUsername': this.maltystMauticBasicUsername,
      'maltystMauticBasicPassword': this.maltystMauticBasicPassword,
    };
    
    this.settingsManager.saveSettings(MauticInstance.MALTYST_COMP_NAME, toSave);
  }



  render() {
    return html`
      <div class="maltyst-settings-area mautic-instance">
        <h3 class="title">1. Mautic instance:</h3>
        
        ${this.compStatus === 'loading'
          ? this.renderLoading() // Show loading state
          : this.compStatus === 'error'
          ? this.renderError() // Show error state
          : this.renderForm()} <!-- Show form on success -->
      </div>
    `;
  }

  private renderLoading() {
    return html`
      <div class="maltyst-form-loading">
        <p>Loading...</p>
        <!-- Optionally, a spinner -->
        <div class="spinner"></div>
      </div>
    `;
  }

  private renderError() {
    return html`
      <div class="maltyst-form-error">
        <p>Something went wrong. Please try again.</p>
        <button @click=${() => this.settingsManager.loadSettings(MauticInstance.MALTYST_COMP_NAME)}>Retry</button>
      </div>
    `;
  }

  private renderForm() {

    return html`
      <form class="maltyst-form-content">
        

        <!--                               COMPONENT VARS                                 -->
        <!--===============================================================================-->
        <div class="form-group">
          <label for="maltystMauticApiUrl">Mautic instance full URL:</label>
          <input
            id="maltystMauticApiUrl"
            type="text"
            .value="${this.maltystMauticApiUrl}"
            @input="${(e: Event) => handleInput(this, e)}"
          />
          <p class="description">
            Full Mautic URL, including protocol.<br />
            ex: https://mautic.healingslice.com
          </p>
        </div>

        <div class="form-group">
          <label for="maltystMauticBasicUsername">Basic Auth Username:</label>
          <input
            id="maltystMauticBasicUsername"
            type="text"
            .value="${this.maltystMauticBasicUsername}"
            @input="${(e: Event) => handleInput(this, e)}"
          />
        </div>

        <div class="form-group">
          <label for="maltystMauticBasicPassword">Basic Auth Password:</label>
          <input
            id="maltystMauticBasicPassword"
            type="password"
            .value="${this.maltystMauticBasicPassword}"
            @input="${(e: Event) => handleInput(this, e)}"
          />
        </div>

        <div>
          <button @click="${() => this.saveSettings()}" class="save-button">Save Settings</button>
        </div>
      </form>
    `;
  }
}



// Ensure to register the component globally
//======================================================================================  
declare global {
  interface HTMLElementTagNameMap {
    'mautic-instance': MauticInstance;
  }
}