import { isQueryParameterPresent, getMaltystContactUqid } from './utils.mjs';

const pcContainer = document.querySelector('.maltyst-preference-center');
const pcContainerForm = pcContainer?.querySelector('form');
const pcContainerSpinner = pcContainerForm?.querySelector('.maltystloader');
const pcContainerResponse = pcContainerForm?.querySelector('.maltyst_result_msg');

// Utility to update DOM classes
const toggleClass = (element, className, add) => {
    if (element) {
        element.classList[add ? 'add' : 'remove'](className);
    }
};

// Utility for resetting state
const resetState = () => {
    toggleClass(pcContainerSpinner, 'maltysthide', false);
    toggleClass(pcContainerResponse, 'success', false);
    toggleClass(pcContainerResponse, 'error', false);
    toggleClass(pcContainerResponse, 'maltysthide', true);
    pcContainerResponse.textContent = '';
};

// Pull account information
const pullAccountInfo = async () => {
    if (window.maltyst.inProgressPcPulling) return;
    window.maltyst.inProgressPcPulling = true;

    resetState();

    try {
        const response = await fetch(`${maltyst_data.ajax_url}?action=maltystFetchGetSubscriptions&maltystContactUqid=${getMaltystContactUqid()}&security=${maltyst_data.nonce}`);
        if (!response.ok) throw new Error('Failed to fetch account info');

        const data = await response.json();

        // Render segment names
        const segmentsContainer = pcContainer.querySelector('.maltyst-segments');
        const template = pcContainer.querySelector('.maltysttemplates .maltyst-segment-li');
        segmentsContainer.innerHTML = '';

        data.pcSegments.forEach(segment => {
            const segmentHtml = template.cloneNode(true);
            segmentHtml.querySelector('.sname').textContent = segment.name;
            segmentHtml.querySelector('.sdescription').textContent = segment.description;
            const checkbox = segmentHtml.querySelector('input[type="checkbox"]');
            checkbox.value = segment.alias;
            checkbox.checked = data.userAliases.includes(segment.alias);
            segmentsContainer.appendChild(segmentHtml);
        });

        // Show "Save" button and segments
        toggleClass(pcContainerForm.querySelector('[name="maltyst_submit_btn"]'), 'maltysthide', false);
        toggleClass(pcContainerForm.querySelector('[name="maltyst_refresh_btn"]'), 'maltysthide', true);
        toggleClass(pcContainer.querySelector('.maltyst-segments-all'), 'maltysthide', false);

        // Automatically trigger unsubscribe if needed
        if (isQueryParameterPresent('unsubscribe-from-all')) {
            pcContainer.querySelector('.maltyst-unsubscribe-all').click();
            updateAccountInfo();
        }
    } catch (error) {
        console.error(error);
        toggleClass(pcContainerResponse, 'error', true);
        pcContainerResponse.textContent = 'Error fetching account info.';
        toggleClass(pcContainerForm.querySelector('[name="maltyst_refresh_btn"]'), 'maltysthide', false);
    } finally {
        toggleClass(pcContainerSpinner, 'maltysthide', true);
        window.maltyst.inProgressPcPulling = false;
    }
};

// Update account info
const updateAccountInfo = async () => {
    if (window.maltyst.inProgressPcSubmission) return;
    window.maltyst.inProgressPcSubmission = true;

    resetState();

    const checkedSnames = Array.from(pcContainerForm.querySelectorAll('input[type="checkbox"]:checked')).map(input => input.value);

    try {
        const response = await fetch(maltyst_data.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'maltystUpdateSubscriptions',
                snames: checkedSnames,
                maltystContactUqid: getMaltystContactUqid(),
                security: maltyst_data.nonce,
            }),
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data?.message || 'Failed to update account info');

        toggleClass(pcContainerResponse, 'success', true);
        pcContainerResponse.textContent = data.message;
    } catch (error) {
        console.error(error);
        toggleClass(pcContainerResponse, 'error', true);
        pcContainerResponse.textContent = error.message || 'Error updating account info.';
    } finally {
        toggleClass(pcContainerSpinner, 'maltysthide', true);
        window.maltyst.inProgressPcSubmission = false;
    }
};

// Initialize preference center
export function initPreferenceCenter() {
    if (!pcContainer) return;

    pullAccountInfo();

    pcContainer.querySelector('[name="maltyst_refresh_btn"]').addEventListener('click', (e) => {
        e.preventDefault();
        pullAccountInfo();
    });

    pcContainerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        updateAccountInfo();
    });

    pcContainer.querySelector('.maltyst-unsubscribe-all').addEventListener('click', (e) => {
        e.preventDefault();
        pcContainerForm.querySelectorAll('input[type="checkbox"]').forEach(input => {
            input.checked = false;
        });
    });
}
