<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useImportsStore } from '@/stores/imports'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'

const store = useImportsStore()
const router = useRouter()
const fileInput = ref<HTMLInputElement | null>(null)
const dragOver = ref(false)

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
      <p v-if="store.error" class="mt-3 text-sm text-danger">{{ store.error }}</p>
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
              <th class="px-5 py-3 font-medium w-10"></th>
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
                  class="text-xs text-accent hover:text-blue-400 font-medium"
                  @click.stop="goDetail(item.id)"
                >
                  Ver
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
