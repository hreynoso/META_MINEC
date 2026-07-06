export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface PageProps {
    auth: {
        user: AuthUser | null;
        roles: string[];
        permissions: string[];
    };
    branding: {
        app_name: string;
        institution: string;
        institution_short: string;
    };
    flash: {
        success?: string;
        error?: string;
    };
}
