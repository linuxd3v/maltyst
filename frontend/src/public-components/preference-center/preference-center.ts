import {isQueryParameterPresent, getMaltystContactUqid} from '../utils';

//==========================================================================
// 3. Preference Center
//==========================================================================

// Elements
const pcContainer = document.querySelector<HTMLElement>('.maltyst-preference-center');
const pcContainerForm = pcContainer?.querySelector<HTMLFormElement>('form');
const pcContainerSpinner = pcContainerForm?.querySelector<HTMLElement>('.maltystloader');
const pcContainerResponse = pcContainerForm?.querySelector<HTMLElement>('.maltyst_result_msg');

// Utility to update DOM classes
const toggleClass = (element: HTMLElement | null, className: string, add: boolean): void => {
    if (element) {
        element.classList[add ? 'add' : 'remove'](className);
    }
};

// Utility for resetting state
const resetState = (): void => {
    toggleClass(pcContainerSpinner, 'maltysthide', false);
    toggleClass(pcContainerResponse, 'success', false);
    toggleClass(pcContainerResponse, 'error', false);
    toggleClass(pcContainerResponse, 'maltysthide', true);
    if (pcContainerResponse) pcContainerResponse.textContent = '';
};

// Pull account information
const pullAccountInfo = async (): Promise<void> => {
    if (window.maltyst?.inProgressPcPulling) return;
    window.maltyst = window.maltyst || {};
    window.maltyst.inProgressPcPulling = true;

    resetState();

    try {
        const response = await fetch(
            `${window.maltystData?.fetch_url}?action=maltystFetchGetSubscriptions&maltystContactUqid=${getMaltystContactUqid()}&security=${window.maltystData?.nonce}`
        );
        if (!response.ok) throw new Error('Failed to fetch account info');

        const data = await response.json();

        // Render segment names
        const segmentsContainer = pcContainer?.querySelector<HTMLElement>('.maltyst-segments');
        const template = pcContainer?.querySelector<HTMLLIElement>('.maltysttemplates .maltyst-segment-li');
        if (segmentsContainer && template) {
            segmentsContainer.innerHTML = '';

            data.pcSegments.forEach((segment: { name: string; description: string; alias: string }) => {
                const segmentHtml = template.cloneNode(true) as HTMLElement;
                segmentHtml.querySelector<HTMLElement>('.sname')!.textContent = segment.name;
                segmentHtml.querySelector<HTMLElement>('.sdescription')!.textContent = segment.description;
                const checkbox = segmentHtml.querySelector<HTMLInputElement>('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.value = segment.alias;
                    checkbox.checked = data.userAliases.includes(segment.alias);
                }
                segmentsContainer.appendChild(segmentHtml);
            });

            // Show "Save" button and segments
            toggleClass(pcContainerForm?.querySelector<HTMLElement>('[name="maltyst_submit_btn"]'), 'maltysthide', false);
            toggleClass(pcContainerForm?.querySelector<HTMLElement>('[name="maltyst_refresh_btn"]'), 'maltysthide', true);
            toggleClass(pcContainer?.querySelector<HTMLElement>('.maltyst-segments-all'), 'maltysthide', false);

            // Automatically trigger unsubscribe if needed
            if (isQueryParameterPresent('unsubscribe-from-all')) {
                pcContainer?.querySelector<HTMLElement>('.maltyst-unsubscribe-all')?.click();
                updateAccountInfo();
            }
        }
    } catch (error) {
        console.error(error);
        toggleClass(pcContainerResponse, 'error', true);
        if (pcContainerResponse) pcContainerResponse.textContent = 'Error fetching account info.';
        toggleClass(pcContainerForm?.querySelector<HTMLElement>('[name="maltyst_refresh_btn"]'), 'maltysthide', false);
    } finally {
        toggleClass(pcContainerSpinner, 'maltysthide', true);
        window.maltyst.inProgressPcPulling = false;
    }
};

// Update account info
const updateAccountInfo = async (): Promise<void> => {
    if (window.maltyst?.inProgressPcSubmission) return;
    window.maltyst = window.maltyst || {};
    window.maltyst.inProgressPcSubmission = true;

    resetState();

    const checkedSnames = Array.from(pcContainerForm?.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:checked') || []).map(
        (input) => input.value
    );

    try {
        const response = await fetch(window.maltystData?.fetch_url || '', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'maltystUpdateSubscriptions',
                snames: checkedSnames,
                maltystContactUqid: getMaltystContactUqid(),
                security: window.maltystData?.nonce,
            }),
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data?.message || 'Failed to update account info');

        toggleClass(pcContainerResponse, 'success', true);
        if (pcContainerResponse) pcContainerResponse.textContent = data.message;
    } catch (error) {
        console.error(error);
        toggleClass(pcContainerResponse, 'error', true);
        if (pcContainerResponse) pcContainerResponse.textContent = error.message || 'Error updating account info.';
    } finally {
        toggleClass(pcContainerSpinner, 'maltysthide', true);
        window.maltyst.inProgressPcSubmission = false;
    }
};

// Initialize preference center
export default function initPreferenceCenter(): void {
    if (!pcContainer) return;

    pullAccountInfo();

    pcContainer?.querySelector<HTMLElement>('[name="maltyst_refresh_btn"]')?.addEventListener('click', (e) => {
        e.preventDefault();
        pullAccountInfo();
    });

    pcContainerForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        updateAccountInfo();
    });

    pcContainer?.querySelector<HTMLElement>('.maltyst-unsubscribe-all')?.addEventListener('click', (e) => {
        e.preventDefault();
        pcContainerForm?.querySelectorAll<HTMLInputElement>('input[type="checkbox"]').forEach((input) => {
            input.checked = false;
        });
    });
}
