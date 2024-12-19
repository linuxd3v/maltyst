// Utility to get query parameter value by name.
export const getQueryParameter = (name: string): string | null => {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
};

// Check if a specific query parameter exists.
export const isQueryParameterPresent = (name: string): boolean => {
    const params = new URLSearchParams(window.location.search);
    return params.has(name);
};

// Retrieve or cache the maltyst contact unique identifier.
export const getMaltystContactUqid = (): string | null => {
    if (!window.maltyst?.maltystContactUqid) {
        window.maltyst = window.maltyst ?? {};
        window.maltyst.maltystContactUqid = getQueryParameter('maltyst_contact_uqid');
    }
    return window.maltyst.maltystContactUqid;
};