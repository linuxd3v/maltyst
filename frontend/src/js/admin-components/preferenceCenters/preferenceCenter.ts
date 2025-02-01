import { LitElement, html } from 'lit';
import { customElement, property } from 'lit/decorators.js';

@customElement('preference-center')
export class PreferenceCenter extends LitElement {
  // State /variables
  //===========================================================================
  @property({ type: String }) name = ''; // Name of the preference center
  @property({ type: Array }) segments: string[] = []; // Array of segment IDs

  @property({ type: Object }) validSegments: Record<string, any> | null = null; // Valid segments



  // Constructor && methods
  //===========================================================================

  private handleNameChange(event: Event) {
    const input = event.target as HTMLInputElement;
    let newName = input.value
      .toLowerCase() // Convert to lowercase
      .replace(/\s+/g, '-') // Replace spaces with dashes
      .replace(/[^a-z0-9_-]/g, ''); // Remove invalid characters
  
    this.dispatchEvent(
      new CustomEvent('name-changed', {
        detail: { oldName: this.name, newName },
        bubbles: true,
        composed: true,
      })
    );
  
    this.name = newName;
  
    // Update input value to reflect the sanitized version
    input.value = newName;
  }  

  private addSegment() {
    const newSegment = prompt('Enter new segment ID:');
    if (newSegment) {
      this.segments = [...this.segments, newSegment];

      this.dispatchEvent(
        new CustomEvent('segment-added', {
          detail: { name: this.name, segments: this.segments },
          bubbles: true,
          composed: true,
        })
      );
    }
  }

  private removeSegment(index: number) {
    const removedSegment = this.segments[index];
    this.segments = this.segments.filter((_, i) => i !== index);

    this.dispatchEvent(
      new CustomEvent('segment-removed', {
        detail: { name: this.name, removedSegment, segments: this.segments },
        bubbles: true,
        composed: true,
      })
    );
  }


  render() {
    return html`
      <div class="preference-center">
        <h3>
          <input
            type="text"
            .value="${this.name}"
            @input="${this.handleNameChange}"
            placeholder="Enter preference center name"
          />
        </h3>

        <ul>
          ${this.segments.map(
            (segment, index) => {
              const isValid = this.validSegments && segment in this.validSegments;
              return html`
                <li class="${isValid ? '' : 'unknownsegment'}">
                  ${segment}
                  <button @click="${() => this.removeSegment(index)}">Remove</button>
                </li>
              `;
            }
          )}
        </ul>

        <div>
          <button @click="${this.addSegment}">Add Segment</button>
        </div>
      </div>
    `;
  }
}




// Ensure to register the component globally
//======================================================================================  
declare global {
  interface HTMLElementTagNameMap {
    'preference-center': PreferenceCenter;
  }
}