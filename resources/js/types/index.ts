export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
};

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
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
