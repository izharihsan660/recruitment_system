export interface Paginated<T> {
    data: T[];
    links?: unknown[];
    meta?: Record<string, unknown>;
}

export interface Entity {
    id: number;
    name: string;
    short_name?: string;
    is_active?: boolean;
}

export interface Department {
    id: number;
    name: string;
    entity_id?: number;
    is_active?: boolean;
    entity?: Entity;
}

export interface BasicUser {
    id: number;
    name: string;
    email?: string;
    roles?: string[];
}

export interface RecruitmentRequest {
    id: number;
    entity_id?: number;
    department_id?: number;
    requester_id?: number;
    requester_position?: string;
    requested_at?: string;
    position_name?: string;
    headcount?: number;
    employment_status?: string;
    job_title?: string;
    work_location?: string;
    required_at?: string;
    reason_type?: string;
    reason_notes?: string;
    min_education?: string;
    min_experience?: string;
    required_skills?: string;
    age_min?: number | null;
    age_max?: number | null;
    gender?: string | null;
    job_description?: string;
    facilities?: Record<string, boolean>;
    status?: string;
    current_approval_level?: number;
    entity?: Entity;
    department?: Department;
    requester?: BasicUser;
    approval_records?: ApprovalRecord[];
    created_at?: string;
}

export interface ApprovalRecord {
    id: number;
    level: number;
    approver?: BasicUser;
    action?: string | null;
    comment?: string | null;
    acted_at?: string | null;
}

export interface JobPosting {
    id: number;
    recruitment_request_id?: number;
    position_name?: string;
    department?: Department;
    entity?: Entity;
    status?: string;
    work_location?: string;
    job_description?: string;
    requirements?: string;
    test_required?: boolean;
    mcu_required?: boolean;
    simper_required?: boolean;
    opened_at?: string | null;
    created_at?: string;
}

export interface Candidate {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    cv_path?: string | null;
    cv_original_name?: string | null;
    has_cv?: boolean;
    updated_at?: string;
    address?: string | null;
    birth_date?: string | null;
    gender?: string | null;
    education?: EducationItem[] | null;
    experience?: ExperienceItem[] | null;
}

export interface EducationItem {
    degree: string;
    major: string;
    institution: string;
    year: number | string;
}

export interface ExperienceItem {
    company: string;
    position: string;
    start_year: number | string;
    end_year?: number | string | null;
    description?: string | null;
}

export interface ApplicationItem {
    id: number;
    job_posting_id?: number;
    status?: string;
    status_label?: string;
    rejection_reason?: string | null;
    rejection_stage?: string | null;
    withdrawn_at?: string | null;
    source?: string | null;
    source_id?: number | null;
    created_at?: string;
    candidate?: Candidate;
    job_posting?: JobPosting;
    jobPosting?: JobPosting;
    pipeline_logs?: PipelineLog[];
}

export interface PipelineLog {
    id: number;
    from_stage?: string | null;
    to_stage?: string | null;
    notes?: string | null;
    created_at?: string;
}

export interface CandidateSource {
    id: number;
    name: string;
    is_active?: boolean;
}

export interface EmailIntake {
    id: number;
    sender_name?: string | null;
    sender_email?: string | null;
    subject?: string | null;
    body?: string | null;
    received_at?: string | null;
    attachment_path?: string | null;
    status?: string;
    is_duplicate?: boolean;
    suggested_job_id?: number | null;
}

export interface TalentPoolItem {
    id: number;
    status?: string;
    tags?: string[] | null;
    notes?: string | null;
    added_at?: string;
    candidate?: Candidate;
    source_application?: ApplicationItem;
}

export function rows<T>(value: T[] | Paginated<T> | undefined | null): T[] {
    if (!value) {
        return [];
    }

    return Array.isArray(value) ? value : value.data;
}

export function formatDate(value?: string | null): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value));
}

export function humanize(value?: string | null): string {
    if (!value) {
        return '-';
    }

    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function fpkStatusTone(status?: string): 'slate' | 'green' | 'yellow' | 'red' | 'orange' | 'blue' {
    if (status === 'approved') {
        return 'green';
    }

    if (status === 'rejected') {
        return 'red';
    }

    if (status === 'need_revision') {
        return 'orange';
    }

    if (status === 'in_approval' || status === 'requested') {
        return 'yellow';
    }

    return 'slate';
}

export function jobStatusTone(status?: string): 'slate' | 'green' | 'yellow' | 'red' | 'orange' | 'blue' {
    if (status === 'open') {
        return 'green';
    }

    if (status === 'cancelled') {
        return 'red';
    }

    return 'slate';
}
