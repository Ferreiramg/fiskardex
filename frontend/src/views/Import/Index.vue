<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useImportsStore } from '@/stores/imports'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

const store = useImportsStore()
const router = useRouter()
const fileInput = ref<HTMLInputElement | null>(null)
const dragOver = ref(false)
const pendingDeletion = ref<{ id: string; filename: string } | null>(null)

onMounted(() => {
  store.fetchAll()
})

function openFilePicker() {
  fileInput.value?.click()
}

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) {
    store.upload(file)
    input.value = ''
  }
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) store.upload(file)
}

function goDetail(id: string) {
  store.select(id)
  router.push(`/imports/${id}`)
}

function askToDelete(item: { id: string; filename: string }) {
  pendingDeletion.value = item
}

async function confirmDeletion() {
  if (!pendingDeletion.value) return

  const deleted = await store.remove(pendingDeletion.value.id)

  if (deleted) {
    pendingDeletion.value = null
  }
}

function formatPeriod(start?: string, end?: string) {
  if (!start || !end) return '—'
  // SPED format DDMMYYYY
  const fmt = (s: string) =>
    s.length === 8 ? `${s.slice(0, 2)}/${s.slice(2, 4)}/${s.slice(4)}` : s
  return `${fmt(start)} — ${fmt(end)}`
}

function shortHash(h: string) {
  return h.length > 20 ? h.slice(0, 18) + '…' : h
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-50">Importações</h1>
        <p class="text-sm text-slate-400 mt-1">
          Envie arquivos SPED EFD ou NF-e XML para montar o Kardex e calcular ICMS recuperável.
        </p>
      </div>
    </div>

    <!-- Upload zone -->
    <div
      class="card p-6 transition-colors"
      :class="dragOver ? 'border-accent bg-accent/5' : ''"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="onDrop"
    >
      <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
          <h2 class="text-sm font-semibold text-slate-200 mb-1">Enviar documento</h2>
          <p class="text-xs text-slate-500">
            Arraste um arquivo .txt (EFD) ou .xml (NF-e) ou clique em Importar.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <input
            ref="fileInput"
            type="file"
            accept=".txt,.xml"
            class="hidden"
            @change="onFileChange"
          />
          <button
            type="button"
            class="btn-primary"
            :disabled="store.loading"
            @click="openFilePicker"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            {{ store.loading ? 'Enviando…' : 'Importar' }}
          </button>
        </div>
      </div>
      <div v-if="store.error || store.notice" class="mt-4 rounded-lg border px-3 py-2 text-sm" :class="store.error ? 'border-danger/30 bg-danger/10 text-red-200' : 'border-success/30 bg-success/10 text-green-200'">
        <div class="flex items-center gap-2">
          <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path v-if="store.error" stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3Z" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
          </svg>
          {{ store.error ?? store.notice }}
        </div>
      </div>
    </div>

    <!-- List -->
    <div class="card overflow-hidden">
      <div class="px-5 py-3 border-b border-surface-border flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-200">Importações recentes</h2>
        <span class="text-xs text-slate-500">{{ store.items.length }} arquivo(s)</span>
      </div>

      <div v-if="store.items.length === 0" class="p-6">
        <EmptyState
          title="Nenhuma importação ainda"
          description="Envie um SPED EFD ou NF-e para começar a análise."
        />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-slate-500 border-b border-surface-border">
              <th class="px-5 py-3 font-medium">Arquivo</th>
              <th class="px-5 py-3 font-medium hidden md:table-cell">Tipo</th>
              <th class="px-5 py-3 font-medium">Status</th>
              <th class="px-5 py-3 font-medium hidden lg:table-cell">Período</th>
              <th class="px-5 py-3 font-medium hidden sm:table-cell">Hash</th>
              <th class="px-5 py-3 font-medium w-20 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-border">
            <tr
              v-for="item in store.items"
              :key="item.id"
              class="hover:bg-surface-elevated/50 cursor-pointer transition-colors"
              @click="goDetail(item.id)"
            >
              <td class="px-5 py-3.5">
                <div class="font-medium text-slate-200 truncate max-w-xs sm:max-w-md" :title="item.filename">
                  {{ item.filename }}
                </div>
              </td>
              <td class="px-5 py-3.5 hidden md:table-cell">
                <span class="badge-neutral uppercase tracking-wide">{{ item.type }}</span>
              </td>
              <td class="px-5 py-3.5">
                <StatusBadge :status="item.status" />
              </td>
              <td class="px-5 py-3.5 text-slate-400 font-mono text-xs hidden lg:table-cell">
                {{ formatPeriod(item.periodStart, item.periodEnd) }}
              </td>
              <td class="px-5 py-3.5 text-slate-500 font-mono text-xs hidden sm:table-cell">
                {{ shortHash(item.hash) }}
              </td>
              <td class="px-5 py-3.5 text-right">
                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-surface hover:text-accent"
                  title="Ver importação"
                  aria-label="Ver importação"
                  @click.stop="goDetail(item.id)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7s-8.27-2.94-9.54-7Z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
                <button
                  type="button"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-400 hover:bg-danger/10 hover:text-danger disabled:cursor-not-allowed disabled:opacity-50"
                  title="Excluir importação"
                  aria-label="Excluir importação"
                  :disabled="store.deletingId === item.id"
                  @click.stop="askToDelete(item)"
                >
                  <svg v-if="store.deletingId === item.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.1 1.02.16m-1.02-.16L18.16 19a2.25 2.25 0 0 1-2.24 2.06H8.08A2.25 2.25 0 0 1 5.84 19L4.77 5.79m14.46 0A48.1 48.1 0 0 0 16.5 5.5m-12.75.29c.34-.06.68-.11 1.02-.16m0 0A48.1 48.1 0 0 1 7.5 5.5m0 0V4.75A2.25 2.25 0 0 1 9.75 2.5h4.5a2.25 2.25 0 0 1 2.25 2.25v.75m-9 0h9" />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog
      :open="pendingDeletion !== null"
      title="Excluir importação?"
      :description="pendingDeletion ? `O arquivo ${pendingDeletion.filename} e todos os dados extraídos serão removidos permanentemente.` : ''"
      confirm-label="Excluir"
      :loading="store.deletingId !== null"
      @cancel="pendingDeletion = null"
      @confirm="confirmDeletion"
    />
  </div>
</template>
