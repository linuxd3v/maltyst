import { LitElement, html } from 'lit';
import { customElement, state, property } from 'lit/decorators.js';
import {SettingsManager} from '../../lib/settings-manager';

@customElement('preference-centers')
export class PreferenceCenters extends LitElement {

  // State /variables
  //===========================================================================
  static readonly MALTYST_COMP_NAME = 'preference-centers';

  @property({ type: Boolean }) 
  maltystMauticIsApiValid: boolean = false;

  private settingsManager: SettingsManager;

  @state()
  private compStatus: string = '';

  // Array of objects
  @state() 
  private centers:  Record<string, any> | null  = {}


  
  // Constructor && methods
  //===========================================================================
  constructor() {
    super();
    this.settingsManager = new SettingsManager();
  }


  // Lifecycle method called when the component is added to the DOM
  async connectedCallback() {
    super.connectedCallback();

    try {
      this.compStatus = 'loading';

      // Fetch settings for this component: 
      this.centers = await this.settingsManager.loadSettings(PreferenceCenters.MALTYST_COMP_NAME);

      this.compStatus = 'complete';

    } catch (error) {
      this.compStatus = 'error';
      console.error('Error loading settings or retrieving Mautic status:', error);
    }
  }



  private saveSettings() {
    if (!this.centers) {
      console.warn('No preference centers to save.');
      return;
    }
  
    const data: Record<string, { segments: string[] }> = Object.entries(this.centers).reduce(
      (acc, [name, data]) => {
        acc[name] = { segments: data.segments || [] };
        return acc;
      },
      {} as Record<string, { segments: string[] }>
    );
  
    this.settingsManager.saveSettings(PreferenceCenters.MALTYST_COMP_NAME, data);
  }
  

  private addPreferenceCenter() {
    const name = prompt('Enter preference center name:');
    if (name) {
      if (!this.centers) {
        this.centers = {};
      }
  
      if (this.centers[name]) {
        alert(`A preference center with the name "${name}" already exists.`);
        return;
      }
  
      this.centers = {
        ...this.centers,
        [name]: { segments: [] },
      };
    }
  }
  

  private removePreferenceCenter(name: string) {
    if (!this.centers || !this.centers[name]) {
      console.warn(`Preference center "${name}" does not exist.`);
      return;
    }

    const confirmRemoval = confirm(`Are you sure you want to remove the preference center "${name}"?`);
    if (confirmRemoval) {
      const { [name]: _, ...remainingCenters } = this.centers;
      this.centers = remainingCenters;
    }
  }

  render() {
    return html`
      <div class="maltyst-settings-area preference-centers">
        <h3 class="title">2. Maltyst Preference Centers:</h3>
    
        <div>
          <button @click="${this.addPreferenceCenter}">Add New Preference Center</button>
        </div>
        
        ${this.centers
          ? Object.entries(this.centers).map(
              ([name, data]) => html`
                <preference-center
                  .name="${name}"
                  .segments="${data.segments}"
                  @remove="${() => this.removePreferenceCenter(name)}"
                ></preference-center>
              `
            )
          : html`<p>No preference centers available.</p>`}
        <button @click="${this.saveSettings}">Save</button>
      </div>
    `;
  }  
}



// Ensure to register the component globally
//======================================================================================  
declare global {
  interface HTMLElementTagNameMap {
    'preference-centers': PreferenceCenters;
  }
}