export type UserSummary = {
    id: string;
    name: string;
    first_name?: string | null;
    middle_name?: string | null;
    last_name?: string | null;
    email: string;
    role?: number | string;
    is_admin?: boolean;
};
