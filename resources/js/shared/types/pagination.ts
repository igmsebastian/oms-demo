export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
};

export type PaginatedResource<T> = {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
};
