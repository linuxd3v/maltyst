// src/types/global.d.ts
export {};

declare global {
    interface Window {
        maltyst?: {
            maltystContactUqid?: string | null;
            inProgressOptin?: boolean;
            inProgressPcPulling?: boolean;
            inProgressPcSubmission?: boolean;
        };
        maltystData: {
            nonce: string;
            MALTYST_PREFIX: string;
            MALTYST_ROUTE: string;
        };
    }
}


export interface ApiResponse {
    message?: string;
    error?: string;
}