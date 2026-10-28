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
