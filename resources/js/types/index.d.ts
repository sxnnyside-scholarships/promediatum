export interface User {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    pronoun?: string | null;
    institution?: string | null;
    educational_area?: string | null;
    educational_level?: string | null;
    greeting?: string;
    localized_greeting?: string;
    is_locked: boolean;
    locale: string;
    two_factor_confirmed_at?: string | null;
    email_verified_at?: string | null;
    settings?: Record<string, unknown>;
    created_at?: string;
    updated_at?: string;
}

export interface Period {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Group {
    id: number;
    period_id: number;
    name: string;
    slug?: string;
    subject?: string | null;
    educational_level?: string | null;
    grade_level?: string | null;
    color?: string | null;
    is_archived?: boolean;
    period?: Period;
    students?: Student[];
    students_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface Student {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    initials?: string;
    slug?: string;
    email?: string | null;
    phone?: string | null;
    guardian_name?: string | null;
    enrollment_number?: string | null;
    notes?: string | null;
    groups?: Group[];
    observations?: Observation[];
    created_at?: string;
    updated_at?: string;
}

export interface Observation {
    id: number;
    student_id: number;
    group_id?: number | null;
    period_id?: number | null;
    type: 'performance' | 'behavior' | 'achievement' | 'followup';
    content: string;
    status: 'pending' | 'resolved';
    resolved_at?: string | null;
    student?: Student;
    group?: Group;
    period?: Period;
    created_at?: string;
    updated_at?: string;
}

export interface ExportTemplate {
    id: number;
    user_id: number;
    name: string;
    type: 'group' | 'student' | 'period';
    format: 'pdf' | 'xlsx' | 'csv';
    config: Record<string, unknown>;
    is_default: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface ExportHistory {
    id: number;
    user_id?: number;
    template_id?: number | null;
    type: 'group' | 'student' | 'period' | string;
    format: 'pdf' | 'xlsx' | 'csv' | 'json' | string;
    filename?: string;
    file_name?: string;
    path?: string;
    file_path?: string;
    context_label?: string | null;
    template_name?: string | null;
    sent_via_email?: boolean;
    recipient_email?: string | null;
    can_download?: boolean;
    status?: 'completed' | 'failed' | 'processing' | string;
    metadata?: Record<string, unknown> | null;
    download_url?: string;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface FabAction {
    icon: string;
    label: string;
    route?: string;
    url?: string;
    method?: string;
    action?: string;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    locale: string;
    flash: {
        status?: string | null;
        smtp_status?: string | null;
    };
    fab?: FabAction[];
    smtp_configured?: boolean;
};
