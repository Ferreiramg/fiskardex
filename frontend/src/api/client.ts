import axios from 'axios'
import type { ImportResponse, UploadResponse } from '@/types'

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

export default api
