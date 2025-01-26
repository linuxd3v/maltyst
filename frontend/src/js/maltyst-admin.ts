// maltyst-settings-page.ts
import { LitElement, html } from 'lit';
import { customElement, state } from 'lit/decorators.js';

import {SettingsManager} from "./lib/settings-manager";
import {MauticInstanceChecker} from "./lib/mautic-instance-checker";



// Global object for this plugin, so we don't pollute a global namespace
window.maltyst = window.maltyst ?? {};


@customElement('maltyst-settings-page')
export class MaltystSettingsPage extends LitElement {



  // State /variables
  //===========================================================================
  static readonly MALTYST_COMP_NAME = 'maltyst-settings-page';


  // Mautic server state: 
  @state()
  private maltystMauticIsApiValid: boolean = false;
  @state()
  private maltystMauticApiLastChecked: string = '';


  private mauticInstanceChecker: MauticInstanceChecker;





  // Constructor && methods
  //===========================================================================
  // Constructor
  constructor() {
    super();
    this.mauticInstanceChecker = new MauticInstanceChecker();
  }

  // On when this was added to the browser - load settings from wp backend
  async connectedCallback() {
    super.connectedCallback();
  }

  // Get component data
  private getMauticInstanceData(): Record<string, string> | undefined {
    const mauticInstance = this.shadowRoot?.querySelector('mautic-instance') as HTMLElement & {
      maltystMauticApiUrl: string;
      maltystMauticBasicUsername: string;
      maltystMauticBasicPassword: string;
    };
  
    if (mauticInstance) {
      return {
        maltystMauticApiUrl: mauticInstance.maltystMauticApiUrl,
        maltystMauticBasicUsername: mauticInstance.maltystMauticBasicUsername,
        maltystMauticBasicPassword: mauticInstance.maltystMauticBasicPassword,
      };
    } else {
      console.error('Mautic instance not found!');
      return undefined;
    }
  }
  
  private async testMautic(mauticInstance: Record<string, string> | undefined): Promise<void> {
    console.log('Loaded data:', mauticInstance);

    if (mauticInstance === undefined) {
      mauticInstance = this.getMauticInstanceData();
    }

    try {
      // Ensure settings are valid before proceeding
      if (mauticInstance) {
        // Execute getMauticStatus only after settings are loaded
        const mauticStatus: Record<string, any> | null = await this.mauticInstanceChecker.getMauticStatus(mauticInstance);
  
        if (mauticStatus) {
          // Update class properties with retrieved Mautic status
          this.maltystMauticIsApiValid = mauticStatus.maltystMauticIsApiValid;
          this.maltystMauticApiLastChecked = mauticStatus.maltystMauticApiLastChecked;
        }
      }
    } catch (error) {
      console.error('Error loading settings or retrieving Mautic status:', error);
    }
  }
   

  


  // Render the HTML template for the component
  //======================================================================================  
  render() {
    return html`
      <div class="maltyst-settings-page">
        <div>
            <h1>Maltyst</h1>
            <h2>Free your newsletters</h2>
        </div>

        <!-- Mautic status -->
        <div class="form-group">
        is mautic server connection valid: 
          ${this.maltystMauticIsApiValid ? 'Yes' : 'No'} <br>
          mautic connection last checked: ${this.maltystMauticApiLastChecked || 'Unknown'} <br> 
          (<a href="#" @click=${() => this.testMautic(undefined)}>recheck connection</a>)
        </div>
          
        <!-- component: Mautic instance connection details  -->
        <mautic-instance
          .maltystMauticIsApiValid=${this.maltystMauticIsApiValid}
          @mautic-data-loaded=${(event: CustomEvent) => {
            this.testMautic(event.detail.data);
          }}>
        </mautic-instance>


        <preference-centers 
          .maltystMauticIsApiValid=${this.maltystMauticIsApiValid}>
        </preference-centers>
        
        <optin-usecases 
          .maltystMauticIsApiValid=${this.maltystMauticIsApiValid}>
        </optin-usecases>
        
        <new-post-notifications 
          .maltystMauticIsApiValid=${this.maltystMauticIsApiValid}>
        </new-post-notifications>
        
        <other-settings 
        .maltystMauticIsApiValid=${this.maltystMauticIsApiValid}>

        </other-settings>
      </div>
    `;
  }
}



// Ensure to register the component globally
//======================================================================================  
declare global {
  interface HTMLElementTagNameMap {
    'mautic-settings-page': MaltystSettingsPage;
  }
}