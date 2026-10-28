<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useImportsStore } from '@/stores/imports'
import type { EfdRecord } from '@/types'
import StatusBadge from '@/components/StatusBadge.vue'

const route = useRoute()
const store = useImportsStore()
const id = computed(() => route.params.id as string)
const item = computed(() => store.selected)
const activeTab = ref<'table' | 'preview'>('table')
const search = ref('')
const selectedCode = ref('')
const page = ref(1)
const pageSize = 50

const registerNames: Record<string, string> = {
  '0000': 'Abertura do arquivo e identificação do estabelecimento',
  '0001': 'Abertura do bloco 0',
  '0100': 'Dados do contabilista',
  '0150': 'Cadastro de participante',
  '0190': 'Cadastro de unidade de medida',
  '0200': 'Cadastro de item',
  '0220': 'Fatores de conversão de unidades',
  '0990': 'Encerramento do bloco 0',
  'C001': 'Abertura do bloco C',
  'C100': 'Documento fiscal (nota fiscal)',
  'C170': 'Itens do documento fiscal',
  'C190': 'Registro analítico do documento',
  'C990': 'Encerramento do bloco C',
  'D001': 'Abertura do bloco D',
  'E001': 'Abertura do bloco E',
  'E100': 'Período de apuração do ICMS',
  'E110': 'Apuração do ICMS — operações próprias',
  'E200': 'Período de apuração do ICMS-ST',
  'E210': 'Apuração do ICMS-ST',
  'E990': 'Encerramento do bloco E',
  'H001': 'Abertura do bloco H',
  'H005': 'Totais do inventário',
  'H010': 'Inventário — detalhe por item',
  'H990': 'Encerramento do bloco H',
  'K001': 'Abertura do bloco K',
  'K200': 'Estoque escriturado',
  'K990': 'Encerramento do bloco K',
  '9001': 'Abertura do bloco 9',
  '9990': 'Encerramento do bloco 9',
  '9999': 'Encerramento do arquivo digital',
}

onMounted(() => store.fetchOne(id.value))

function formatPeriod(start?: string, end?: string) {
  if (!start || !end) return '—'
  const format = (value: string) =>
    value.length === 8 ? `${value.slice(0, 2)}/${value.slice(2, 4)}/${value.slice(4)}` : value
  return `${format(start)} — ${format(end)}`
}

function decodeRaw(rawBase64: string) {
  try {
    const bytes = Uint8Array.from(atob(rawBase64), (char) => char.charCodeAt(0))
    const encoding = item.value?.encoding === 'windows-1252' ? 'windows-1252' : 'utf-8'
    return new TextDecoder(encoding).decode(bytes)
  } catch {
    return ''
  }
}

function recordDescription(record: EfdRecord) {
  return registerNames[record.type] ?? 'Registro do arquivo EFD'
}

const records = computed(() => item.value?.records ?? [])
const registerEntries = computed(() => {
  if (!item.value?.registers) return []
  return Object.entries(item.value.registers)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([code, count]) => ({ code, count }))
})
const totalRegisters = computed(() =>
  registerEntries.value.reduce((sum, register) => sum + register.count, 0),
)
const filteredRecords = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('pt-BR')
  return records.value.filter((record) => {
    if (selectedCode.value && record.type !== selectedCode.value) return false
    if (!query) return true
    return `${record.line} ${record.type} ${record.fields.join(' ')} ${decodeRaw(record.rawBase64)}`
      .toLocaleLowerCase('pt-BR')
      .includes(query)
  })
})
const pageCount = computed(() => Math.max(1, Math.ceil(filteredRecords.value.length / pageSize)))
const visibleRecords = computed(() => {
  const start = (page.value - 1) * pageSize
  return filteredRecords.value.slice(start, start + pageSize)
})
const fieldColumns = computed(() => {
  const max = Math.max(0, ...visibleRecords.value.map((record) => record.fields.length - 2))
  return Array.from({ length: max }, (_, index) => index + 2)
})
const previewRecords = computed(() => {
  const query = search.value.trim().toLocaleLowerCase('pt-BR')
  return records.value
    .filter((record) => !selectedCode.value || record.type === selectedCode.value)
    .filter((record) => !query || decodeRaw(record.rawBase64).toLocaleLowerCase('pt-BR').includes(query))
    .slice(0, 300)
})

function resetPage() {
  page.value = 1
}
</script>

<template>
  <div v-if="store.loading && !item" class="card p-6 text-sm text-slate-400">
    Carregando detalhes da importação…
  </div>
  <div v-else-if="!item" class="space-y-4">
    <p class="text-slate-400">Importação não encontrada.</p>
    <RouterLink to="/imports" class="btn-ghost">← Voltar</RouterLink>
  </div>

  <div v-else class="space-y-6">
    <div class="flex items-center gap-2 text-sm">
      <RouterLink to="/imports" class="text-slate-500 hover:text-slate-300">Importações</RouterLink>
      <span class="text-slate-600">/</span>
      <span class="truncate text-slate-300">{{ item.filename }}</span>
    </div>

    <section class="card p-5 sm:p-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="break-all text-lg font-bold text-slate-50 sm:text-xl">{{ item.filename }}</h1>
            <StatusBadge :status="item.status" />
            <span class="badge-neutral uppercase">{{ item.type }}</span>
          </div>
          <p class="mt-2 text-sm text-slate-400">Resumo da escrituração e conferência dos registros extraídos.</p>
        </div>
        <RouterLink to="/imports" class="btn-ghost">← Importações</RouterLink>
      </div>

      <dl class="mt-6 grid grid-cols-2 gap-4 text-sm lg:grid-cols-4">
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Período</dt>
          <dd class="mt-1 font-mono text-xs text-slate-200">{{ formatPeriod(item.periodStart, item.periodEnd) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Codificação</dt>
          <dd class="mt-1 text-slate-200">{{ item.encoding ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Registros contabilizados</dt>
          <dd class="mt-1 tabular-nums text-slate-200">{{ totalRegisters.toLocaleString('pt-BR') }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Hash SHA-256</dt>
          <dd class="mt-1 break-all font-mono text-xs text-slate-400">{{ item.hash }}</dd>
        </div>
      </dl>
    </section>

    <section v-if="item.issues?.length" class="card overflow-hidden">
      <div class="border-b border-surface-border px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-200">Ocorrências ({{ item.issues.length }})</h2>
      </div>
      <ul class="divide-y divide-surface-border">
        <li v-for="(issue, index) in item.issues" :key="index" class="flex gap-3 px-5 py-3 text-sm">
          <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="{
            'bg-blue-400': issue.severity === 'info',
            'bg-warning': issue.severity === 'warning',
            'bg-danger': issue.severity === 'error',
          }" />
          <div>
            <span class="font-mono text-xs text-slate-500">{{ issue.code }}</span>
            <p class="text-slate-300">{{ issue.message }}</p>
          </div>
        </li>
      </ul>
    </section>

    <section class="card overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-surface-border p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-base font-semibold text-slate-100">Explorador EFD</h2>
          <p class="mt-1 text-xs text-slate-500">{{ totalRegisters.toLocaleString('pt-BR') }} linhas no arquivo · {{ records.length.toLocaleString('pt-BR') }} linhas disponíveis para consulta</p>
        </div>
        <div class="flex rounded-lg border border-surface-border bg-surface p-1" role="tablist" aria-label="Visualização dos registros">
          <button class="rounded-md px-3 py-2 text-xs font-medium transition-colors" :class="activeTab === 'table' ? 'bg-surface-elevated text-white' : 'text-slate-400 hover:text-slate-200'" role="tab" :aria-selected="activeTab === 'table'" @click="activeTab = 'table'">
            Tabela de registros
          </button>
          <button class="rounded-md px-3 py-2 text-xs font-medium transition-colors" :class="activeTab === 'preview' ? 'bg-surface-elevated text-white' : 'text-slate-400 hover:text-slate-200'" role="tab" :aria-selected="activeTab === 'preview'" @click="activeTab = 'preview'">
            Prévia do arquivo
          </button>
        </div>
      </header>

      <div class="grid gap-0 xl:grid-cols-[250px_minmax(0,1fr)]">
        <aside class="border-b border-surface-border p-4 xl:border-b-0 xl:border-r">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tipos de registro</h3>
            <button v-if="selectedCode" class="text-xs text-accent hover:text-blue-300" @click="selectedCode = ''; resetPage()">Limpar</button>
          </div>
          <div class="max-h-64 space-y-1 overflow-y-auto xl:max-h-[620px]">
            <button class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs" :class="!selectedCode ? 'bg-accent/10 text-blue-200' : 'text-slate-400 hover:bg-surface-elevated'" @click="selectedCode = ''; resetPage()">
              <span>Todos os registros</span><span class="tabular-nums">{{ records.length }}</span>
            </button>
            <button v-for="register in registerEntries" :key="register.code" class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-xs" :class="selectedCode === register.code ? 'bg-accent/10 text-blue-200' : 'text-slate-400 hover:bg-surface-elevated'" @click="selectedCode = register.code; resetPage()">
              <span class="min-w-0"><span class="font-mono font-semibold">{{ register.code }}</span><span class="mt-0.5 block truncate text-[10px] text-slate-500">{{ registerNames[register.code] ?? 'Registro EFD' }}</span></span>
              <span class="shrink-0 tabular-nums">{{ register.count.toLocaleString('pt-BR') }}</span>
            </button>
          </div>
        </aside>

        <div class="min-w-0">
          <div class="flex flex-col gap-3 border-b border-surface-border p-4 sm:flex-row sm:items-center sm:justify-between">
            <label class="relative block w-full sm:max-w-sm">
              <span class="sr-only">Buscar nos registros</span>
              <input v-model="search" class="input pl-9" placeholder="Buscar código, linha ou conteúdo…" @input="resetPage" />
              <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </label>
            <span class="text-xs text-slate-500">{{ filteredRecords.length.toLocaleString('pt-BR') }} resultados</span>
          </div>

          <div v-if="activeTab === 'table'" class="max-h-[620px] overflow-auto">
            <table class="w-full min-w-[760px] border-collapse text-left text-xs">
              <thead class="sticky top-0 z-20 bg-surface text-[10px] uppercase tracking-wide text-slate-500">
                <tr>
                  <th class="sticky left-0 z-10 w-20 border-b border-surface-border bg-surface px-4 py-3">Linha</th>
                  <th class="w-24 border-b border-surface-border px-3 py-3">Código</th>
                  <th class="min-w-64 border-b border-surface-border px-3 py-3">Descrição</th>
                  <th v-for="field in fieldColumns" :key="field" class="min-w-40 border-b border-surface-border px-3 py-3">Campo {{ String(field - 1).padStart(2, '0') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-border/70">
                <tr v-for="record in visibleRecords" :key="record.line" class="align-top hover:bg-surface-elevated/50">
                  <td class="sticky left-0 bg-surface-card px-4 py-3 font-mono text-slate-500">{{ record.line }}</td>
                  <td class="px-3 py-3"><span class="rounded bg-surface-elevated px-2 py-1 font-mono font-semibold text-blue-300">{{ record.type }}</span></td>
                  <td class="px-3 py-3 text-slate-300">{{ recordDescription(record) }}</td>
                  <td v-for="field in fieldColumns" :key="field" class="max-w-72 whitespace-pre-wrap break-words px-3 py-3 font-mono text-slate-400">{{ record.fields[field] || '—' }}</td>
                </tr>
                <tr v-if="visibleRecords.length === 0"><td :colspan="3 + fieldColumns.length" class="px-4 py-12 text-center text-slate-500">Nenhum registro encontrado com esses filtros.</td></tr>
              </tbody>
            </table>
          </div>

          <div v-else class="max-h-[680px] overflow-auto bg-[#0b1020] p-2 font-mono text-xs">
            <div v-for="record in previewRecords" :key="record.line" class="group flex min-w-max border-b border-white/[0.04] hover:bg-white/[0.04]">
              <span class="sticky left-0 w-14 shrink-0 select-none bg-[#0b1020] px-3 py-2 text-right text-slate-600 group-hover:bg-[#11182a]">{{ record.line }}</span>
              <span class="w-16 shrink-0 px-2 py-2 font-semibold text-blue-300">{{ record.type }}</span>
              <span class="whitespace-pre px-2 py-2 text-slate-300">{{ decodeRaw(record.rawBase64) }}</span>
            </div>
            <p v-if="previewRecords.length === 0" class="p-10 text-center font-sans text-slate-500">Nenhuma linha encontrada com esses filtros.</p>
            <p v-else-if="previewRecords.length >= 300" class="border-t border-white/[0.06] px-3 py-3 font-sans text-[11px] text-slate-500">Prévia limitada às primeiras 300 linhas que correspondem ao filtro.</p>
          </div>

          <footer v-if="activeTab === 'table' && filteredRecords.length" class="flex items-center justify-between border-t border-surface-border px-4 py-3">
            <span class="text-xs text-slate-500">Página {{ page }} de {{ pageCount }}</span>
            <div class="flex gap-2">
              <button class="btn-ghost px-3 py-1.5 text-xs" :disabled="page <= 1" @click="page--">Anterior</button>
              <button class="btn-ghost px-3 py-1.5 text-xs" :disabled="page >= pageCount" @click="page++">Próxima</button>
            </div>
          </footer>
        </div>
      </div>
    </section>

    <div class="flex flex-wrap gap-3">
      <RouterLink to="/kardex" class="btn-primary">Ir para Kardex</RouterLink>
      <RouterLink to="/imports" class="btn-ghost">← Voltar à lista</RouterLink>
    </div>
  </div>
</template>
