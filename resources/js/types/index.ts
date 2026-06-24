export type User = {
    id: number;
    name: string;
    email: string;
    department_id?: number | null;
    roles?: string[];
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
};

export type InAppNotification = {
    id: number;
    type: string;
    title: string;
    body: string;
    data?: Record<string, number | string | null>;
    read_at?: string | null;
    created_at?: string;
};

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

export type Candidate = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    current_position?: string | null;
    source?: string | null;
    created_at?: string;
    updated_at?: string;
};

export type JobPosting = {
    id: number;
    title: string;
    department?: string | null;
    location?: string | null;
    employment_type?: string | null;
    status: 'draft' | 'published' | 'closed';
    published_at?: string | null;
    created_at?: string;
    updated_at?: string;
};
