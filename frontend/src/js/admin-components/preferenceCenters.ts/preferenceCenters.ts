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
  private centers: { name: string; segments: string[] }[] = [
    // example:
    // { name: 'pc-default', segments: [] },
  ];



  
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
      let settings: Record<string, any> | null = await this.settingsManager.loadSettings(PreferenceCenters.MALTYST_COMP_NAME);


    } catch (error) {
      this.compStatus = 'error';
      console.error('Error loading settings or retrieving Mautic status:', error);
    }
  }



  private saveSettings() {
    // Format data as Record<string, any>
    const data: Record<string, { segments: string[] }> = this.centers.reduce((acc, center) => {
      acc[center.name] = { segments: center.segments };
      return acc;
    }, {} as Record<string, { segments: string[] }>);
    
    this.settingsManager.saveSettings(PreferenceCenters.MALTYST_COMP_NAME, data);
  }


  private addPreferenceCenter() {
    const name = prompt('Enter preference center name:');
    if (name) {
      this.centers = [...this.centers, { name, segments: [] }];
    }
  }

  private removePreferenceCenter(index: number) {
    const confirmRemoval = confirm(`Are you sure you want to remove the preference center "${this.centers[index].name}"?`);
    if (confirmRemoval) {
      this.centers = this.centers.filter((_, i) => i !== index);
    }
  }

  render() {
    return html`
      <div class="maltyst-settings-area preference-centers">
        <h3 class="title">2. Maltyst Preference Centers:</h3>

        <div>
          <button @click="${this.addPreferenceCenter}">Add New Preference Center</button>
        </div>
        
        ${this.centers.map(
          (center, index) => html`
            <preference-center
              .name="${center.name}"
              .segments="${center.segments}"
              @remove="${() => this.removePreferenceCenter(index)}"
            ></preference-center>
          `
        )}
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