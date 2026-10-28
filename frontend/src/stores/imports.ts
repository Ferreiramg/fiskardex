import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type { ImportRecord, ImportResponse } from '@/types'
import * as api from '@/api/client'

function toImportRecord(importResponse: ImportResponse): ImportRecord {
  return {
    id: importResponse.id,
    filename: importResponse.originalName,
    type: importResponse.kind,
    status: importResponse.status,
    hash: importResponse.sha256,
    periodStart: importResponse.period?.start,
    periodEnd: importResponse.period?.end,
    encoding: importResponse.encoding,
    createdAt: importResponse.createdAt,
    registers: importResponse.recordCounts,
    issues: importResponse.issues,
    records: importResponse.records,
  }
}

export const useImportsStore = defineStore('imports', () => {
  const items = ref<ImportRecord[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const notice = ref<string | null>(null)
  const deletingId = ref<string | null>(null)
  const selectedId = ref<string | null>(null)

  const selected = computed(() =>
    items.value.find((item) => item.id === selectedId.value) ?? null,
  )

  async function fetchAll() {
    loading.value = true
    error.value = null
    notice.value = null

    try {
      items.value = (await api.listImports()).map(toImportRecord)
    } catch (exception: unknown) {
      error.value = exception instanceof Error
        ? exception.message
        : 'Falha ao carregar importações.'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string) {
    loading.value = true
    error.value = null

    try {
      const item = toImportRecord(await api.getImport(id))
      const index = items.value.findIndex((candidate) => candidate.id === id)

      if (index === -1) {
        items.value.push(item)
      } else {
        items.value[index] = item
      }

      selectedId.value = id
    } catch (exception: unknown) {
      error.value = exception instanceof Error
        ? exception.message
        : 'Falha ao carregar a importação.'
    } finally {
      loading.value = false
    }
  }

  async function upload(file: File) {
    loading.value = true
    error.value = null
    notice.value = null

    try {
      const result = await api.uploadImport(file)
      await fetchOne(result.id)
      notice.value = 'Importação processada com sucesso.'
    } catch (exception: unknown) {
      error.value = exception instanceof Error
        ? exception.message
        : 'Falha no upload do arquivo.'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<boolean> {
    deletingId.value = id
    error.value = null
    notice.value = null

    try {
      await api.deleteImport(id)
      items.value = items.value.filter((item) => item.id !== id)

      if (selectedId.value === id) {
        selectedId.value = null
      }

      notice.value = 'Arquivo e dados da importação foram excluídos.'

      return true
    } catch (exception: unknown) {
      error.value = exception instanceof Error
        ? exception.message
        : 'Não foi possível excluir a importação.'

      return false
    } finally {
      deletingId.value = null
    }
  }

  function select(id: string) {
    selectedId.value = id
  }

  return {
    items,
    loading,
    error,
    notice,
    deletingId,
    selectedId,
    selected,
    fetchAll,
    fetchOne,
    upload,
    remove,
    select,
  }
})
