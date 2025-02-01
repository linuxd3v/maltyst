import { LitElement, html } from 'lit';
import { customElement, state, property } from 'lit/decorators.js';
import {FetchManager} from '../../lib/fetch-manager';

@customElement('preference-centers')
export class PreferenceCenters extends LitElement {

  // State /variables
  //===========================================================================
  static readonly MALTYST_COMP_NAME = 'preference-centers';

  @property({ type: Boolean }) 
  maltystMauticIsApiValid: boolean = false;

  private fetchManager: FetchManager;

  @state()
  private compStatus: string = '';

  // Array of objects
  @state() 
  private centers:  Record<string, any> | null  = {}

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
      this.centers = await this.fetchManager.loadSettings(PreferenceCenters.MALTYST_COMP_NAME);

      // Fetch settings for this component: 
      this.validSegments = await this.fetchManager.getAllSegments();

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
  
    this.fetchManager.saveSettings(PreferenceCenters.MALTYST_COMP_NAME, data);
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

  private handleNameChanged(event: CustomEvent) {
    if (!this.centers) return;
  
    const { oldName, newName } = event.detail;
  
    if (oldName !== newName) {
      if (this.centers[newName]) {
        alert(`A preference center with the name "${newName}" already exists.`);
        return;
      }
  
      // Update the key in the `centers` object
      const { [oldName]: oldData, ...remainingCenters } = this.centers;
      this.centers = {
        ...remainingCenters,
        [newName]: oldData,
      };
    }
  }

  private handleSegmentAdded(event: CustomEvent) {
    if (!this.centers) return;
  
    const { name, segments } = event.detail;
  
    if (this.centers[name]) {
      // Update the segments for the specific center
      this.centers = {
        ...this.centers,
        [name]: { ...this.centers[name], segments },
      };
  
      console.log(`Segments updated for center "${name}":`, segments);
    }
  }
  

  private handleSegmentRemoved(event: CustomEvent) {
    if (!this.centers) return;
  
    const { name, removedSegment, segments } = event.detail;
  
    if (this.centers[name]) {
      // Update the segments for the specific center
      this.centers = {
        ...this.centers,
        [name]: { ...this.centers[name], segments },
      };
  
      console.log(`Segment "${removedSegment}" was removed from center "${name}".`);
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
                  .validSegments="${this.validSegments}"
                  @segment-added="${this.handleSegmentAdded}"
                  @segment-removed="${this.handleSegmentRemoved}"
                  @name-changed="${this.handleNameChanged}"
                ></preference-center>
              `
            )
          : html`<p>No preference centers available.</p>`}
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
    'preference-centers': PreferenceCenters;
  }
}