import axios from 'axios'
import type {
  ImportRecord,
  KardexProduct,
  CostingResult,
  AllocationLink,
  RecoverableIcmsSummary,
} from '@/types'

const api = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    Accept: 'application/json',
  },
})

// --- Imports ---

export async function listImports(): Promise<ImportRecord[]> {
  const { data } = await api.get<ImportRecord[]>('/imports')
  return data
}

export async function getImport(id: string): Promise<ImportRecord> {
  const { data } = await api.get<ImportRecord>(`/imports/${id}`)
  return data
}

export async function uploadImport(file: File, type: string = 'efd'): Promise<ImportRecord> {
  const form = new FormData()
  form.append('file', file)
  form.append('type', type)
  const { data } = await api.post<ImportRecord>('/imports', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data
}

// --- Kardex ---

export async function getKardex(productId?: string): Promise<KardexProduct[]> {
  const { data } = await api.get<KardexProduct[]>('/kardex', {
    params: productId ? { productId } : undefined,
  })
  return data
}

// --- Costing ---

export async function getCosting(method: 'fifo' | 'average'): Promise<CostingResult[]> {
  const { data } = await api.get<CostingResult[]>(`/costing/${method}`)
  return data
}

// --- Allocation ---

export async function getAllocation(): Promise<AllocationLink[]> {
  const { data } = await api.get<AllocationLink[]>('/allocation')
  return data
}

// --- Recoverable ICMS ---

export async function getRecoverableIcms(): Promise<RecoverableIcmsSummary> {
  const { data } = await api.get<RecoverableIcmsSummary>('/recoverable-icms')
  return data
}

export default api
