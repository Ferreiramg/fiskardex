export type ImportStatus =
  | 'pending'
  | 'processing'
  | 'completed'
  | 'completed_with_issues'
  | 'failed'

export type DocumentType = 'efd' | 'nfe-xml' | 'unknown'

export interface ImportRecord {
  id: string
  filename: string
  type: DocumentType
  status: ImportStatus
  hash: string
  periodStart?: string
  periodEnd?: string
  encoding?: string
  createdAt: string
  updatedAt?: string
  registers?: Record<string, number>
  issues?: ImportIssue[]
  records?: EfdRecord[]
}

export interface EfdRecord {
  line: number
  type: string
  fields: string[]
  rawBase64: string
}

export interface ImportResponse {
  id: string
  originalName: string
  kind: DocumentType
  status: ImportStatus
  sha256: string
  period?: {
    start?: string
    end?: string
  }
  encoding?: string
  createdAt: string
  recordCounts?: Record<string, number>
  issues?: ImportIssue[]
  records?: EfdRecord[]
}

export interface UploadResponse {
  id: string
  status: ImportStatus
  sha256: string
}

export interface ImportIssue {
  code: string
  message: string
  severity: 'info' | 'warning' | 'error'
  line?: number
  register?: string
}

export interface ImportSummary {
  source: {
    id: string
    filename: string
    sha256: string
    recordsAvailable: number
    recordsDeclared: number | null
    coverageComplete: boolean
  }
  taxpayer: {
    name: string
    cnpj: string
    stateRegistration: string
    profile: string
    periodStart: string | null
    periodEnd: string | null
  } | null
  documents: {
    entries: { count: number; value: string; icmsBase: string; icms: string; icmsStBase: string; icmsSt: string; ipi: string }
    exits: { count: number; value: string; icmsBase: string; icms: string; icmsStBase: string; icmsSt: string; ipi: string }
  }
  analytical: {
    entries: Record<'operationValue' | 'icmsBase' | 'icms' | 'icmsStBase' | 'icmsSt' | 'ipi', string>
    exits: Record<'operationValue' | 'icmsBase' | 'icms' | 'icmsStBase' | 'icmsSt' | 'ipi', string>
  }
  apurations: Array<{
    period: { start: string | null; end: string | null } | null
    debits: string
    entryCredits: string
    balanceDue: string
    deductions: string
    payable: string
    creditBalanceForward: string
    specialDebits: string
  }>
  obligations: Array<{
    code: string
    amount: string
    dueDate: string | null
    revenueCode: string
    sourceLine: number
  }>
  exitC170Count: number
  notes: string[]
}

export interface C100Document {
  line: number
  date: string | null
  model: string
  series: string
  number: string
  status: string
  documentValue: string
  icmsBase: string
  icms: string
  icmsStBase: string
  icmsSt: string
  key: string
}

export interface C100DocumentPage {
  items: C100Document[]
  page: number
  perPage: number
  total: number
  pages: number
  totals: {
    documentValue: string
    icmsBase: string
    icms: string
    icmsStBase: string
    icmsSt: string
  }
}

export interface KardexMovement {
  id: string
  date: string
  documentKey: string
  type: 'entry' | 'exit' | 'adjustment'
  quantity: number
  unitCost: number
  totalCost: number
  balanceQty: number
  balanceCost: number
  partnerName?: string
  cfop?: string
}

export interface KardexProduct {
  productId: string
  code: string
  description: string
  ncm: string
  unit: string
  currentQty: number
  currentCost: number
  movements: KardexMovement[]
}

export interface KardexRun {
  id: string
  import_id: string
  algorithm_version: string
  source_sha256: string
  status: string
  accepted_quantity_base: string
  accepted_count: number
  exception_count: number
  created_at: string
  original_name: string
  period_start?: string
  period_end?: string
}

export interface KardexEntryRow {
  id: string
  date: string | null
  documentNumber: string
  partner: string
  productCode: string
  product: string
  ncm: string
  unitOriginal: string
  quantityOriginal: string
  unitBase: string
  quantityBase: string
  factor: string
  itemValue: string
  discount: string
  cst: string
  cfop: string
  icmsBase: string
  icmsRate: string
  icmsValue: string
  icmsStBase: string
  icmsStValue: string
  sourceLine: number
  parentLine: number
}

export interface KardexEntriesPage {
  items: KardexEntryRow[]
  page: number
  perPage: number
  total: number
  pages: number
  monetaryTotals: {
    itemValue: string
    discount: string
    icmsBase: string
    icmsValue: string
    icmsStBase: string
    icmsStValue: string
  }
}

export interface KardexException {
  source_line: number
  parent_c100_line: number | null
  product_code: string | null
  code: string
  message: string
  details: Record<string, unknown>
}

export interface CostingResult {
  method: 'fifo' | 'average'
  productId: string
  productCode: string
  totalCost: number
  totalQty: number
  unitCost: number
  layers?: Array<{
    purchaseDate: string
    qty: number
    unitCost: number
    remaining: number
  }>
}

export interface AllocationLink {
  saleDocumentKey: string
  saleDate: string
  saleQty: number
  saleUnitPrice: number
  purchaseDocumentKey: string
  purchaseDate: string
  purchaseQty: number
  purchaseUnitCost: number
  allocatedQty: number
  costBase: number
  recoverableIcms: number
}

export interface RecoverableIcmsSummary {
  periodStart: string
  periodEnd: string
  totalSales: number
  totalCostBase: number
  totalRecoverableIcms: number
  rate: number
  linksCount: number
}
