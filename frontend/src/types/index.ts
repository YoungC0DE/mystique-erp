export type PermissionSlug = 'create' | 'read' | 'update' | 'delete'



export interface Tokens {

  token_type?: string

  expires_in?: number

  access_token: string

  refresh_token: string

}



export interface Permission {

  id: string

  name: string

  slug: PermissionSlug | string

}



export interface Role {

  id: string

  name: string

  slug: string

  permissions?: Permission[]

  created_at?: string

}



export type AppLocale = 'pt-BR' | 'en'



export interface User {

  id: string

  name: string

  email: string

  is_admin: boolean

  locale: AppLocale

  roles?: Role[]

  permissions: string[]

  created_at?: string

}



export type FieldType =

  | 'texto'

  | 'textarea'

  | 'numero'

  | 'decimal'

  | 'boolean'

  | 'email'

  | 'telefone'

  | 'data'

  | 'datetime'

  | 'select'

  | 'multiselect'



export interface ModuleField {

  id: string

  label: string

  key: string

  type: FieldType

  required: boolean

  default_value: string | null

  options: string[] | null

  order: number

  show_in_card: boolean

  show_in_list: boolean

  show_in_detail: boolean

  highlighted: boolean

  visible: boolean

}



export interface DetailLayoutGroup {

  label?: string

  field_keys: string[]

}



export interface DetailLayoutRow {

  field_keys: string[]

}



export interface DetailLayout {

  rows?: DetailLayoutRow[]

  columns?: 1 | 2

  groups?: DetailLayoutGroup[]

}



export interface ModuleKanbanStatus {

  id: string

  slug: string

  label: string

  order: number

  external_value: string

}



export interface Module {

  id: string

  name: string

  slug: string

  icon: string | null

  status: string

  is_integrated?: boolean

  connection_id?: string | null

  connection?: DatabaseConnection

  callback_url?: string | null

  callback_method?: string

  status_column?: string | null

  statuses?: ModuleKanbanStatus[]

  fields_count?: number

  fields?: ModuleField[]

  detail_layout?: DetailLayout | null

  created_at?: string

}



export type RecordValue = string | number | boolean | string[] | null



export interface ModuleRecord {

  id: string

  status: string

  values: Record<string, RecordValue>

  created_at?: string

  updated_at?: string

}



export interface RecordAudit {

  id: string

  action: string

  changes: Record<string, { old: RecordValue; new: RecordValue }>

  user?: { id: string | null; name: string | null }

  created_at?: string

}



export interface PaginationMeta {

  current_page: number

  last_page: number

  per_page: number

  total: number

}



export interface Paginated<T> {

  data: T[]

  meta: PaginationMeta

  links?: unknown

}



export interface KanbanColumnData {

  key: string

  label: string

  color?: string

  records: ModuleRecord[]

  meta: PaginationMeta

}



export interface KanbanBoard {

  module: string

  columns: KanbanColumnData[]

}



export interface RecordNote {

  body: string | null

  updated_at?: string | null

  updated_by?: { id: string | null; name: string | null } | null

}



export interface DatabaseConnection {

  id: string

  name: string

  host: string

  port: number

  database: string

  username: string

  table_name: string

  has_password: boolean

  created_at?: string

  updated_at?: string

}



export interface DatabaseColumn {

  name: string

  type: string

}



export interface Report {

  id: string

  name: string

  module_id: string

  module?: Module

  field_keys: string[]

  filters: import('@/lib/filters').FieldFilter[]

  created_by?: { id: string | null; name: string | null }

  created_at?: string

  updated_at?: string

}



export interface ReportRunResult {

  data: Record<string, unknown>[]

  meta: PaginationMeta

}

