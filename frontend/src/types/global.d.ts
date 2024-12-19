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
            fetch_url: string;
            prefix: string;
            nonce: string;
        };
    }
}


export interface ApiResponse {
    message?: string;
    error?: string;
}