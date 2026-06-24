export interface User {
    id: number;
    name: string;
    email: string;
    department_id?: number | null;
    roles?: string[];
    email_verified_at?: string | null;
}

export interface InAppNotification {
    id: number;
    type: string;
    title: string;
    body: string;
    data?: Record<string, number | string | null>;
    read_at?: string | null;
    created_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        candidate?: {
            id: number;
            name: string;
            email: string;
            phone?: string | null;
            has_cv?: boolean;
        } | null;
    };
    unread_notifications_count?: number;
    latest_notifications?: InAppNotification[];
};
