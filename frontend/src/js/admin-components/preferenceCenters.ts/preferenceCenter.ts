import { LitElement, html } from 'lit';
import { customElement, property } from 'lit/decorators.js';

@customElement('preference-center')
export class PreferenceCenter extends LitElement {
  @property({ type: String }) name = ''; // Name of the preference center
  @property({ type: Array }) segments: string[] = []; // Array of segment IDs

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
            (segment) => html`<li>${segment}</li>`
          )}
        </ul>
        <div>
          <button @click="${this.addSegment}">Add Segment</button>
        </div>
      </div>
    `;
  }

  private handleNameChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const newName = input.value;

    // Emit a custom event to notify the parent about the name change
    this.dispatchEvent(
      new CustomEvent('name-changed', {
        detail: {
          oldName: this.name,
          newName,
        },
        bubbles: true,
        composed: true,
      })
    );

    // Update the local name property
    this.name = newName;
  }

  private addSegment() {
    const newSegment = prompt('Enter new segment ID:');
    if (newSegment) {
      this.segments = [...this.segments, newSegment];

      // Emit a custom event to notify the parent
      this.dispatchEvent(
        new CustomEvent('segment-added', {
          detail: {
            name: this.name,
            segments: this.segments,
          },
          bubbles: true,
          composed: true,
        })
      );
    }
  }
}

// Ensure to register the component globally
declare global {
  interface HTMLElementTagNameMap {
    'preference-center': PreferenceCenter;
  }
}
