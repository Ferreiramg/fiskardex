import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { ImportRecord } from '@/types'
import * as api from '@/api/client'

/** Mock data based on the real EFD shown in the MVP */
const MOCK_IMPORTS: ImportRecord[] = [
  {
    id: 'imp-001',
    filename: 'SpedEFD-00820836000132-3089474860060-Remessa de arquivo original-jul.2026.txt',
    type: 'efd',
    status: 'completed_with_issues',
    hash: '00369654419a2a0cd15b8517...',
    periodStart: '01072026',
    periodEnd: '31072026',
    encoding: 'unknown',
    createdAt: '2026-10-26T18:00:00Z',
    updatedAt: '2026-10-26T18:05:00Z',
    registers: {
      '0000': 1,
      '0001': 1,
      '0005': 1,
      '0100': 1,
      '0150': 54,
      '0190': 32,
      '0200': 994,
      '0220': 627,
      '0450': 1,
      '0500': 1,
      '0990': 1,
      '1001': 1,
      '1010': 1,
      '1400': 1,
      '1990': 1,
      '9001': 1,
      '9900': 42,
      '9990': 1,
      '9999': 1,
    },
    issues: [
      {
        code: 'EFD-ENC',
        message: 'Codificação do arquivo não detectada automaticamente',
        severity: 'warning',
      },
      {
        code: 'EFD-HASH',
        message: 'Hash SHA-256 parcial (truncado na visualização)',
        severity: 'info',
      },
    ],
  },
]

export const useImportsStore = defineStore('imports', () => {
  const items = ref<ImportRecord[]>([...MOCK_IMPORTS])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const selectedId = ref<string | null>(MOCK_IMPORTS[0]?.id ?? null)

  const selected = computed(() =>
    items.value.find((i) => i.id === selectedId.value) ?? null
  )

  async function fetchAll() {
    loading.value = true
    error.value = null
    try {
      // When backend is ready, replace with:
      // items.value = await api.listImports()
      await new Promise((r) => setTimeout(r, 300))
      items.value = [...MOCK_IMPORTS]
    } catch (e: any) {
      error.value = e?.message ?? 'Falha ao carregar importações'
    } finally {
      loading.value = false
    }
  }

  async function upload(file: File) {
    loading.value = true
    error.value = null
    try {
      // When backend is ready:
      // const record = await api.uploadImport(file)
      // items.value.unshift(record)
      // selectedId.value = record.id

      // Mock for now
      await new Promise((r) => setTimeout(r, 800))
      const mock: ImportRecord = {
        id: `imp-${Date.now()}`,
        filename: file.name,
        type: 'efd',
        status: 'processing',
        hash: Math.random().toString(16).slice(2, 18) + '...',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      }
      items.value.unshift(mock)
      selectedId.value = mock.id

      // Simulate completion
      setTimeout(() => {
        mock.status = 'completed'
        mock.updatedAt = new Date().toISOString()
        mock.registers = { '0000': 1, '0150': 10, '0200': 50 }
      }, 1500)
    } catch (e: any) {
      error.value = e?.message ?? 'Falha no upload'
    } finally {
      loading.value = false
    }
  }

  function select(id: string) {
    selectedId.value = id
  }

  return {
    items,
    loading,
    error,
    selectedId,
    selected,
    fetchAll,
    upload,
    select,
  }
})
