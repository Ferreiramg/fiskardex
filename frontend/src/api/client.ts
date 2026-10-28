import axios from 'axios'
import type {
  ImportResponse,
  UploadResponse,
  ImportSummary,
  C100DocumentPage,
  KardexRun,
  KardexEntriesPage,
  KardexException,
} from '@/types'

const api = axios.create({
  baseURL: '/api/v1',
  timeout: 30000,
  headers: {
    Accept: 'application/json',
  },
})

// --- Imports ---

export async function listImports(): Promise<ImportResponse[]> {
  const { data } = await api.get<ImportResponse[]>('/imports')

  return data
}

export async function getImport(id: string): Promise<ImportResponse> {
  const { data } = await api.get<ImportResponse>(`/imports/${id}`)

  return data
}

export async function getImportSummary(id: string): Promise<ImportSummary> {
  const { data } = await api.get<ImportSummary>(`/imports/${id}/summary`)
  return data
}

export async function listImportC100Documents(
  id: string,
  direction: 'entries' | 'exits',
  page = 1,
  search = '',
): Promise<C100DocumentPage> {
  const { data } = await api.get<C100DocumentPage>(`/imports/${id}/summary/documents`, {
    params: { direction, page, per_page: 50, q: search },
  })
  return data
}

export async function uploadImport(file: File): Promise<UploadResponse> {
  const form = new FormData()
  form.append('file', file)
  const { data } = await api.post<UploadResponse>('/imports', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return data
}

export async function deleteImport(id: string): Promise<void> {
  await api.delete(`/imports/${id}`)
}

export async function listKardexRuns(): Promise<KardexRun[]> {
  const { data } = await api.get<KardexRun[]>('/kardex/runs')
  return data
}

export async function listKardexMovements(
  runId: string,
  page = 1,
  search = '',
): Promise<KardexEntriesPage> {
  const { data } = await api.get<KardexEntriesPage>(`/kardex/runs/${runId}/movements`, {
    params: { page, per_page: 50, q: search },
  })
  return data
}

export async function listKardexExceptions(runId: string): Promise<KardexException[]> {
  const { data } = await api.get<KardexException[]>(`/kardex/runs/${runId}/exceptions`)
  return data
}

export default api
