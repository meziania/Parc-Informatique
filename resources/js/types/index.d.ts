export type UserRole = 'user' | 'technician' | 'admin';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role: UserRole;
    role_label?: string;
    is_active?: boolean;
}

export interface RoleOption {
    value: UserRole;
    label: string;
}

export interface ManagedUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
    open_tickets_count?: number;
    entity: { id: number; name: string } | null;
}

export interface ManagedUserForm {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    entity_id: number | null;
    is_active: boolean;
}

export interface Option {
    value: string;
    label: string;
}

export interface Location {
    id: number;
    name: string;
    building: string | null;
    room: string | null;
    assets_count?: number;
}

export interface Supplier {
    id: number;
    name: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    website: string | null;
    notes: string | null;
    is_active: boolean;
    contracts_count?: number;
    contracts?: ContractSummary[];
}

export interface ContractSummary {
    id: number;
    title: string;
    reference: string | null;
    ends_on: string | null;
    expiry_status?: string;
    expiry_label?: string;
    assets_count?: number;
    supplier?: { id: number; name: string; email?: string | null; phone?: string | null } | null;
}

export interface Contract extends ContractSummary {
    supplier_id: number | null;
    starts_on: string | null;
    amount: string | number | null;
    currency: string | null;
    notes: string | null;
    is_active: boolean;
    assets?: { id: number; name: string; inventory_number: string }[];
}

export interface Consumable {
    id: number;
    name: string;
    sku: string | null;
    category: string;
    category_label: string;
    quantity: number;
    min_quantity: number;
    unit: string;
    location_id: number | null;
    supplier_id: number | null;
    notes: string | null;
    stock_status: string;
    stock_label: string;
    location?: { id: number; name: string } | null;
    supplier?: { id: number; name: string } | null;
}

export interface InstalledSoftware {
    id: number;
    name: string;
    vendor: string | null;
    version: string | null;
    software_license_id: number | null;
    installed_at: string | null;
    notes: string | null;
    license?: { id: number; name: string } | null;
}

export interface Asset {
    id: number;
    name: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    status_color: string;
    inventory_number: string;
    serial_number: string | null;
    manufacturer: string | null;
    model: string | null;
    purchase_date: string | null;
    warranty_end: string | null;
    next_maintenance_at?: string | null;
    maintenance_label?: string;
    notes: string | null;
    location_id: number | null;
    user_id: number | null;
    location?: Location | null;
    user?: { id: number; name: string; email?: string } | null;
    documents?: DocumentFile[];
    licenses?: SoftwareLicenseSummary[];
    contracts?: ContractSummary[];
    installed_softwares?: InstalledSoftware[];
}

export interface SoftwareLicenseSummary {
    id: number;
    name: string;
    vendor: string | null;
    expiry_date: string | null;
    seats: number;
    seats_used: number;
    seats_available: number;
    expiry_status: string;
    expiry_label: string;
}

export interface SoftwareLicense extends SoftwareLicenseSummary {
    product_key: string | null;
    purchase_date: string | null;
    notes: string | null;
    is_active: boolean;
    assets?: { id: number; name: string; inventory_number: string }[];
}

export interface Reservation {
    id: number;
    title: string;
    purpose: string | null;
    starts_at: string;
    ends_at: string;
    status: string;
    status_label: string;
    status_color: string;
    resource_label: string;
    review_note: string | null;
    user?: { id: number; name: string; email?: string } | null;
    asset?: { id: number; name: string; inventory_number: string } | null;
    location?: Location | null;
    reviewer?: { id: number; name: string } | null;
}

export interface TicketTask {
    id: number;
    title: string;
    is_done: boolean;
    due_at: string | null;
    completed_at: string | null;
    sort_order: number;
    assignee?: { id: number; name: string } | null;
}

export interface AssetEvent {
    id: number;
    event_type: string;
    title: string;
    body: string | null;
    meta: Record<string, unknown> | null;
    created_at: string;
    actor: { id: number; name: string } | null;
    ticket_id: number | null;
}

export interface DocumentFile {
    id: number;
    original_name: string;
    mime_type: string | null;
    size: number;
    human_size: string;
    created_at: string;
    uploader: { id: number; name: string } | null;
}

export interface TicketUser {
    id: number;
    name: string;
    email?: string;
    open_tickets_count?: number;
}

export interface TicketComment {
    id: number;
    body: string;
    created_at: string;
    user: TicketUser | null;
}

export type SlaStatus = 'ok' | 'at_risk' | 'breached' | 'met' | 'none';

export interface Ticket {
    id: number;
    number: string;
    title: string;
    description: string;
    type: string;
    type_label: string;
    priority: string;
    priority_label: string;
    priority_color: string;
    status: string;
    status_label: string;
    status_color: string;
    due_at: string | null;
    sla_status: SlaStatus;
    sla_label: string;
    sla_color: string;
    solution: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    satisfaction_rating: number | null;
    satisfaction_comment: string | null;
    satisfaction_rated_at: string | null;
    satisfaction_label: string | null;
    created_at: string;
    requester: TicketUser;
    assignee: TicketUser | null;
    asset: { id: number; name: string; inventory_number: string } | null;
    comments?: TicketComment[];
    documents?: DocumentFile[];
    tasks?: TicketTask[];
    approval_status?: string | null;
    approval_label?: string | null;
    approval_color?: string | null;
    approval_note?: string | null;
    approved_at?: string | null;
    approver?: { id: number; name: string } | null;
}

export interface PriorityOption extends Option {
    sla_hours?: number;
    sla_label?: string;
}

export interface TicketTypeOption {
    value: string;
    label: string;
    description: string;
}

export interface FaqArticle {
    id: number;
    title: string;
    body: string;
    category: string;
    category_label: string;
    is_published: boolean;
    views_count: number;
    created_at: string;
    updated_at: string;
    author: { id: number; name: string } | null;
}

export interface DashboardTicket {
    id: number;
    number: string;
    title: string;
    type_label: string;
    priority_label: string;
    priority_color: string;
    status_label: string;
    status_color: string;
    due_at: string | null;
    sla_status: SlaStatus;
    sla_label: string;
    sla_color: string;
    created_at: string | null;
    requester: { id: number; name: string } | null;
    assignee: { id: number; name: string } | null;
    asset: { id: number; name: string; inventory_number: string } | null;
}

export interface DashboardAsset {
    id: number;
    name: string;
    inventory_number: string;
    type?: string;
    type_label?: string;
    status: string;
    status_label?: string;
    status_color?: string;
    warranty_end?: string | null;
    next_maintenance_at?: string | null;
    maintenance_label?: string;
    user?: { id: number; name: string } | null;
    location?: { id: number; name: string } | null;
}

export interface TechnicianStats {
    open_tickets: number;
    unassigned_tickets: number;
    urgent_tickets: number;
    overdue_tickets: number;
    at_risk_tickets: number;
    my_tickets: number;
    broken_assets: number;
    assets_total: number;
    assets_in_stock: number;
    avg_satisfaction: number;
    satisfaction_count: number;
    awaiting_satisfaction: number;
    sla_met_rate_30d: number | null;
    avg_satisfaction_30d: number | null;
    satisfaction_count_30d: number;
    warranty_expired: number;
    warranty_expiring_30d: number;
    maintenance_due: number;
    maintenance_soon: number;
    licenses_expired: number;
    licenses_expiring_30d: number;
    pending_reservations: number;
}

export interface ServiceCatalogOption {
    id: number;
    code: string;
    name: string;
    description: string | null;
    type: string;
    default_priority: string;
    sla_hours: number;
    type_label: string;
    priority_label: string;
    sla_label: string;
    requires_approval?: boolean;
}

export interface UserStats {
    open_tickets: number;
    resolved_tickets: number;
    overdue_tickets: number;
    my_assets: number;
    pending_satisfaction: number;
    my_reservations: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface AppNotification {
    id: string;
    type: string;
    data: {
        ticket_id?: number;
        ticket_number?: string;
        title: string;
        body: string;
        url: string;
    };
    read_at: string | null;
    created_at: string | null;
}

export interface SharedNotifications {
    unread_count: number;
    recent: AppNotification[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    csrf_token?: string;
    flash?: {
        status?: string | null;
    };
    ai?: {
        enabled: boolean;
        provider: string;
        reachable: boolean;
        mode: string;
        chat_model: string;
        embedding_model: string;
        chat_model_ready: boolean | null;
        embedding_model_ready: boolean | null;
        message: string;
        hint: string | null;
        latency_ms: number | null;
    } | null;
    notifications: SharedNotifications;
};
