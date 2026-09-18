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
    settings?: Record<string, unknown>;
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
    grade_level?: string | null;
    color?: string | null;
    period?: Period;
    students_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface Student {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    email?: string | null;
    phone?: string | null;
    enrollment_number?: string | null;
    notes?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface Observation {
    id: number;
    student_id: number;
    group_id?: number | null;
    type: 'performance' | 'behavior' | 'achievement' | 'follow_up';
    content: string;
    is_resolved: boolean;
    resolved_at?: string | null;
    student?: Student;
    group?: Group;
    created_at?: string;
    updated_at?: string;
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
