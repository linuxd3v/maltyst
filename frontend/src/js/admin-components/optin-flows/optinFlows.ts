import { LitElement, html } from 'lit';
import { customElement, state, property } from 'lit/decorators.js';
import {FetchManager} from '../../lib/fetch-manager';

@customElement('optin-flows')
export class OptinFlows extends LitElement {

  // State /variables
  //===========================================================================
  static readonly MALTYST_COMP_NAME = 'optin-flows';

  @property({ type: Boolean }) 
  maltystMauticIsApiValid: boolean = false;

  private fetchManager: FetchManager;

  @state()
  private compStatus: 'loading' | 'complete' | 'error' = 'loading';

  // Array of objects
  @state() 
  private optins:  Record<string, any> | null  = {}

  // Array of objects
  @state() 
  private validSegments:  Record<string, any> | null  = {}
  
  
  // Constructor && methods
  //===========================================================================
  constructor() {
    super();
    this.fetchManager = new FetchManager();
  }


  // Lifecycle method called when the component is added to the DOM
  async connectedCallback() {
    super.connectedCallback();

    try {
      this.compStatus = 'loading';

      // Fetch settings for this component: 
      this.optins = await this.fetchManager.loadSettings(OptinFlows.MALTYST_COMP_NAME);

      // Fetch settings for this component: 
      this.validSegments = await this.fetchManager.getAllSegments();

      this.compStatus = 'complete';

    } catch (error) {
      this.compStatus = 'error';
      console.error('Error loading settings or retrieving Mautic status:', error);
    }
  }



  private saveSettings() {
    if (!this.optins) {
      console.warn('No preference optins to save.');
      return;
    }
  
    const data: Record<string, { segments: string[] }> = Object.entries(this.optins).reduce(
      (acc, [name, data]) => {
        acc[name] = { segments: data.segments || [] };
        return acc;
      },
      {} as Record<string, { segments: string[] }>
    );
  
    this.fetchManager.saveSettings(OptinFlows.MALTYST_COMP_NAME, data);
  }
  

  private addOptin() {
    const name = prompt('Enter optin flow name:');
    if (name) {
      if (!this.optins) {
        this.optins = {};
      }
  
      if (this.optins[name]) {
        alert(`An optin flow with the name "${name}" already exists.`);
        return;
      }
  
      this.optins = {
        ...this.optins,
        [name]: { segments: [] },
      };
    }
  }
  

  private removeOptin(name: string) {
    if (!this.optins || !this.optins[name]) {
      console.warn(`Preference center "${name}" does not exist.`);
      return;
    }

    const confirmRemoval = confirm(`Are you sure you want to remove the preference center "${name}"?`);
    if (confirmRemoval) {
      const { [name]: _, ...remainingoptins } = this.optins;
      this.optins = remainingoptins;
    }
  }

  private handleNameChanged(event: CustomEvent) {
    if (!this.optins) return;
  
    const { oldName, newName } = event.detail;
  
    if (oldName !== newName) {
      if (this.optins[newName]) {
        alert(`An optin flow with the name "${newName}" already exists.`);
        return;
      }
  
      // Update the key in the `optins` object
      const { [oldName]: oldData, ...remainingoptins } = this.optins;
      this.optins = {
        ...remainingoptins,
        [newName]: oldData,
      };
    }
  }

  private handleSegmentAdded(event: CustomEvent) {
    if (!this.optins) return;
  
    const { name, segments } = event.detail;
  
    if (this.optins[name]) {
      // Update the segments for the specific center
      this.optins = {
        ...this.optins,
        [name]: { ...this.optins[name], segments },
      };
  
      console.log(`Segments updated for center "${name}":`, segments);
    }
  }
  

  private handleSegmentRemoved(event: CustomEvent) {
    if (!this.optins) return;
  
    const { name, removedSegment, segments } = event.detail;
  
    if (this.optins[name]) {
      // Update the segments for the specific center
      this.optins = {
        ...this.optins,
        [name]: { ...this.optins[name], segments },
      };
  
      console.log(`Segment "${removedSegment}" was removed from center "${name}".`);
    }
  }  

  render() {
    return html`
      <div class="maltyst-settings-area optin-usecases ${this.compStatus}">
        <h3 class="title">3. Optins flows:</h3>
    
        <div>
          <button @click="${this.addOptin}">Add New Optin Flow</button>
        </div>
        
        ${this.optins
          ? Object.entries(this.optins).map(
              ([name, data]) => html`
                <div class="optin-flow-container">
                  <optin-flow
                    .name="${name}"
                    .segments="${data.segments}"
                    .validSegments="${this.validSegments}"
                    @segment-added="${this.handleSegmentAdded}"
                    @segment-removed="${this.handleSegmentRemoved}"
                    @name-changed="${this.handleNameChanged}"
                  ></optin-flow>
                  <button @click="${() => this.removeOptin(name)}">Remove</button>
                </div>
              `
            )
          : html`<p>No optin flows available.</p>`}
  
        <div>
          <button @click="${this.saveSettings}">Save</button>
        </div>
      </div>
    `;
  }
  
}



// Ensure to register the component globally
//======================================================================================  
declare global {
  interface HTMLElementTagNameMap {
    'optin-flows': OptinFlows;
  }
}