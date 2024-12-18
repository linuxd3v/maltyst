// Utility to get query parameter value by name.
export const getQueryParameter = (name) => {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
};

// Check if a specific query parameter exists.
export const isQueryParameterPresent = (name) => {
    const params = new URLSearchParams(window.location.search);
    return params.has(name);
};

// Retrieve or cache the maltyst contact unique identifier.
export const getMaltystContactUqid = () => {
    if (!window.maltyst.maltystContactUqid) {
        window.maltyst.maltystContactUqid = getQueryParameter('maltyst_contact_uqid');
    }
    return window.maltyst.maltystContactUqid;
};
