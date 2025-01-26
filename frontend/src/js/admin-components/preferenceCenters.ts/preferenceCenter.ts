import { LitElement, html } from 'lit';
import { customElement, property } from 'lit/decorators.js';

@customElement('preference-center')
export class PreferenceCenter extends LitElement {
  @property({ type: String }) name = ''; // Name of the preference center
  @property({ type: Array }) segments: string[] = []; // Array of segment IDs

  render() {
    return html`
      <div class="preference-center">
        <h3>${this.name}</h3>
        <ul>
          ${this.segments.map(
            (segment) => html`<li>${segment}</li>`
          )}
        </ul>
        <div>
          <button @click="${this.addSegment}">Add Segment</button>
        </div>
      </div>
    `;
  }

  private addSegment() {
    const newSegment = prompt('Enter new segment ID:');
    if (newSegment) {
      this.segments = [...this.segments, newSegment];
    }
  }
}




// Ensure to register the component globally
//======================================================================================  
declare global {
  interface HTMLElementTagNameMap {
    'preference-center': PreferenceCenter;
  }
}