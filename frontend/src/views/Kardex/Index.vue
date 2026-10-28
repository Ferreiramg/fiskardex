<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import {
  listKardexExceptions,
  listKardexMovements,
  listKardexRuns,
} from '@/api/client'
import type { KardexEntriesPage, KardexException, KardexRun } from '@/types'

const runs = ref<KardexRun[]>([])
const selectedRunId = ref('')
const entries = ref<KardexEntriesPage | null>(null)
const exceptions = ref<KardexException[]>([])
const search = ref('')
const appliedSearch = ref('')
const page = ref(1)
const activeTab = ref<'entries' | 'exceptions'>('entries')
const loadingRuns = ref(true)
const loadingData = ref(false)
const error = ref('')
const selectedRun = computed(() => runs.value.find((run) => run.id === selectedRunId.value))

onMounted(loadRuns)

async function loadRuns() {
  loadingRuns.value = true
  error.value = ''
  try {
    runs.value = await listKardexRuns()
    if (runs.value.length && !selectedRunId.value) {
      selectedRunId.value = runs.value[0].id
      await loadRunData()
    }
  } catch {
    error.value = 'Não foi possível carregar as execuções do Kardex.'
  } finally {
    loadingRuns.value = false
  }
}

async function loadRunData() {
  if (!selectedRunId.value) return
  loadingData.value = true
  error.value = ''
  try {
    const [movementPage, pending] = await Promise.all([
      listKardexMovements(selectedRunId.value, page.value, appliedSearch.value),
      listKardexExceptions(selectedRunId.value),
    ])
    entries.value = movementPage
    exceptions.value = pending
  } catch {
    error.value = 'Não foi possível carregar os movimentos desta execução.'
  } finally {
    loadingData.value = false
  }
}

function selectRun() {
  page.value = 1
  appliedSearch.value = ''
  search.value = ''
  entries.value = null
  loadRunData()
}

function applySearch() {
  appliedSearch.value = search.value.trim()
  page.value = 1
  loadRunData()
}

function goToPage(nextPage: number) {
  page.value = nextPage
  loadRunData()
}

function formatNumber(value: string | number, digits = 2) {
  const number = Number(value)
  return Number.isFinite(number)
    ? number.toLocaleString('pt-BR', { maximumFractionDigits: digits })
    : '—'
}

function formatMoney(value: string | number) {
  const number = Number(value)
  return Number.isFinite(number)
    ? number.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    : '—'
}

function formatDate(value: string | null) {
  if (!value) return '—'
  const [year, month, day] = value.split('-')
  return `${day}/${month}/${year}`
}

function formatPeriod(run: KardexRun) {
  const fmt = (value?: string) => value?.length === 8
    ? `${value.slice(0, 2)}/${value.slice(2, 4)}/${value.slice(4)}`
    : '—'
  return `${fmt(run.period_start)} a ${fmt(run.period_end)}`
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-50">Kardex</h1>
        <p class="mt-1 text-sm text-slate-400">
          Itens de entrada por documento, com quantidades originais e normalizadas.
        </p>
      </div>
      <button class="btn-ghost" :disabled="loadingRuns || loadingData" @click="loadRuns">
        Atualizar
      </button>
    </div>

    <div class="card p-5">
      <label for="run-select" class="label">Execução / arquivo EFD</label>
      <select
        id="run-select"
        v-model="selectedRunId"
        class="input max-w-2xl"
        :disabled="loadingRuns || runs.length === 0"
        @change="selectRun"
      >
        <option value="" disabled>Selecione uma execução processada</option>
        <option v-for="run in runs" :key="run.id" :value="run.id">
          {{ run.original_name }} · {{ formatPeriod(run) }} · {{ run.algorithm_version }}
        </option>
      </select>
      <p v-if="selectedRun" class="mt-2 text-xs text-slate-500">
        Hash da fonte: <span class="font-mono">{{ selectedRun.source_sha256 }}</span>
      </p>
    </div>

    <div v-if="error" class="rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-red-200">
      {{ error }}
    </div>

    <div v-if="runs.length === 0 && !loadingRuns" class="card p-6">
      <EmptyState
        title="Nenhuma execução de Kardex"
        description="Importe um EFD e processe suas entradas com o lote do Kardex para visualizar os itens aqui."
      />
    </div>

    <template v-if="selectedRun">
      <div class="grid gap-3 sm:grid-cols-3">
        <div class="card p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Itens aceitos</div>
          <div class="mt-2 text-xl font-semibold tabular-nums text-slate-100">
            {{ selectedRun.accepted_count.toLocaleString('pt-BR') }}
          </div>
        </div>
        <div class="card p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Quantidade normalizada</div>
          <div class="mt-2 text-xl font-semibold tabular-nums text-slate-100">
            {{ formatNumber(selectedRun.accepted_quantity_base, 8) }}
          </div>
          <div class="mt-1 text-xs text-slate-500">Soma das quantidades na unidade base</div>
        </div>
        <div class="card p-4">
          <div class="text-xs font-medium uppercase tracking-wide text-slate-500">Pendências</div>
          <div class="mt-2 text-xl font-semibold tabular-nums" :class="selectedRun.exception_count ? 'text-amber-300' : 'text-slate-100'">
            {{ selectedRun.exception_count.toLocaleString('pt-BR') }}
          </div>
        </div>
      </div>

      <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-surface-border px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-1">
            <button
              class="rounded-lg px-3 py-2 text-sm font-semibold transition-colors"
              :class="activeTab === 'entries' ? 'bg-accent/15 text-accent' : 'text-slate-400 hover:text-slate-100'"
              @click="activeTab = 'entries'"
            >
              Itens aceitos
            </button>
            <button
              class="rounded-lg px-3 py-2 text-sm font-semibold transition-colors"
              :class="activeTab === 'exceptions' ? 'bg-accent/15 text-accent' : 'text-slate-400 hover:text-slate-100'"
              @click="activeTab = 'exceptions'"
            >
              Pendências <span class="ml-1 text-xs">{{ exceptions.length }}</span>
            </button>
          </div>
          <form v-if="activeTab === 'entries'" class="flex gap-2" @submit.prevent="applySearch">
            <input v-model="search" class="input min-w-52" placeholder="Produto, código, NF ou CFOP" />
            <button class="btn-primary" type="submit" :disabled="loadingData">Buscar</button>
          </form>
        </div>

        <div v-if="activeTab === 'entries'">
          <div v-if="loadingData" class="p-8 text-center text-sm text-slate-500">Carregando itens…</div>
          <div v-else-if="entries && entries.items.length === 0" class="p-6">
            <EmptyState title="Nenhum item encontrado" description="Ajuste a busca ou confira as pendências desta execução." />
          </div>
          <div v-else class="max-h-[68vh] overflow-auto">
            <table class="min-w-[1750px] w-full text-xs">
              <thead class="sticky top-0 z-20 bg-surface-card shadow-sm">
                <tr class="border-b border-surface-border text-left text-[11px] uppercase tracking-wide text-slate-500">
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">Cód.</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">NCM</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">CFOP</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">CST</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">NF</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">Data</th>
                  <th class="px-2.5 py-2.5 font-semibold">Fornecedor</th>
                  <th class="px-2.5 py-2.5 font-semibold">Produto</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">Un. orig.</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">Qtd. orig.</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 font-semibold">Un. base</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">Qtd. base</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">Fator</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">Valor item</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">Desconto</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">BC ICMS</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">Alíq. %</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">ICMS</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">BC ICMS-ST</th>
                  <th class="whitespace-nowrap px-2.5 py-2.5 text-right font-semibold">ICMS-ST</th>
                  <th class="px-2.5 py-2.5 text-center font-semibold">Origem</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, index) in entries?.items ?? []"
                  :key="row.id"
                  class="border-b border-surface-border/70 transition-colors hover:bg-accent/10"
                  :class="index % 2 === 0 ? 'bg-surface-card' : 'bg-surface-elevated/45'"
                >
                  <td class="whitespace-nowrap px-2.5 py-2 font-mono font-semibold text-accent">{{ row.productCode }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 font-mono text-slate-400">{{ row.ncm || '—' }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 font-mono text-slate-300">{{ row.cfop || '—' }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 font-mono text-slate-400">{{ row.cst || '—' }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 font-mono text-slate-300">{{ row.documentNumber || '—' }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-slate-400">{{ formatDate(row.date) }}</td>
                  <td class="max-w-48 truncate px-2.5 py-2 text-slate-300" :title="row.partner">{{ row.partner || '—' }}</td>
                  <td class="max-w-64 truncate px-2.5 py-2 text-slate-200" :title="row.product">{{ row.product || '—' }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-slate-400">{{ row.unitOriginal }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-200">{{ formatNumber(row.quantityOriginal, 8) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-slate-400">{{ row.unitBase }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums font-semibold text-slate-100">{{ formatNumber(row.quantityBase, 8) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">{{ formatNumber(row.factor, 8) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-200">R$ {{ formatMoney(row.itemValue) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">R$ {{ formatMoney(row.discount) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">{{ formatNumber(row.icmsBase) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">{{ formatNumber(row.icmsRate, 4) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">R$ {{ formatMoney(row.icmsValue) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">{{ formatNumber(row.icmsStBase) }}</td>
                  <td class="whitespace-nowrap px-2.5 py-2 text-right tabular-nums text-slate-400">R$ {{ formatMoney(row.icmsStValue) }}</td>
                  <td class="px-2.5 py-2 text-center">
                    <button
                      type="button"
                      class="inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-500 hover:bg-accent/15 hover:text-accent focus:outline-none focus:ring-2 focus:ring-accent"
                      :title="`Origem: C170 linha ${row.sourceLine}; documento pai C100 linha ${row.parentLine}.`"
                      :aria-label="`Origem: C170 linha ${row.sourceLine}; documento pai C100 linha ${row.parentLine}.`"
                    >
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" d="M12 11v5m0-8h.01" />
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="entries && entries.total > 0" class="border-t border-surface-border bg-surface-elevated/30 px-4 py-3">
            <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-6">
              <div class="rounded-lg border border-surface-border bg-surface-card px-3 py-2">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">Valor dos itens</div>
                <div class="mt-1 text-xs font-semibold tabular-nums text-slate-100">R$ {{ formatMoney(entries.monetaryTotals.itemValue) }}</div>
              </div>
              <div class="rounded-lg border border-surface-border bg-surface-card px-3 py-2">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">Descontos</div>
                <div class="mt-1 text-xs font-semibold tabular-nums text-slate-100">R$ {{ formatMoney(entries.monetaryTotals.discount) }}</div>
              </div>
              <div class="rounded-lg border border-surface-border bg-surface-card px-3 py-2">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">Base ICMS</div>
                <div class="mt-1 text-xs font-semibold tabular-nums text-slate-100">R$ {{ formatMoney(entries.monetaryTotals.icmsBase) }}</div>
              </div>
              <div class="rounded-lg border border-surface-border bg-surface-card px-3 py-2">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">ICMS informado</div>
                <div class="mt-1 text-xs font-semibold tabular-nums text-slate-100">R$ {{ formatMoney(entries.monetaryTotals.icmsValue) }}</div>
              </div>
              <div class="rounded-lg border border-surface-border bg-surface-card px-3 py-2">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">Base ICMS-ST</div>
                <div class="mt-1 text-xs font-semibold tabular-nums text-slate-100">R$ {{ formatMoney(entries.monetaryTotals.icmsStBase) }}</div>
              </div>
              <div class="rounded-lg border border-surface-border bg-surface-card px-3 py-2">
                <div class="text-[10px] uppercase tracking-wide text-slate-500">ICMS-ST informado</div>
                <div class="mt-1 text-xs font-semibold tabular-nums text-slate-100">R$ {{ formatMoney(entries.monetaryTotals.icmsStValue) }}</div>
              </div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-500">
              <span>{{ entries.total.toLocaleString('pt-BR') }} item(ns) · página {{ entries.page }} de {{ Math.max(entries.pages, 1) }} · totais sobre todos os itens filtrados</span>
              <div class="flex gap-1.5">
                <button class="btn-ghost px-2.5 py-1 text-xs" :disabled="page <= 1 || loadingData" @click="goToPage(page - 1)">Anterior</button>
                <button class="btn-ghost px-2.5 py-1 text-xs" :disabled="page >= entries.pages || loadingData" @click="goToPage(page + 1)">Próxima</button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'entries'" class="border-t border-surface-border px-4 py-3 text-[11px] leading-relaxed text-slate-500">
          <span class="font-semibold text-slate-400">Fator:</span> quantidade de unidades base correspondente a uma unidade original; a quantidade base é a quantidade original multiplicada por esse fator.
          <span class="mx-1.5 text-slate-700">·</span>
          <span class="font-semibold text-slate-400">Unidade base:</span> unidade de inventário definida no registro 0200.
          <span class="mx-1.5 text-slate-700">·</span>
          Valores de ICMS são os informados no item da EFD; não representam cálculo de crédito recuperável.
        </div>

        <div v-else-if="exceptions.length === 0" class="p-6">
          <EmptyState title="Nenhuma pendência" description="Todos os itens elegíveis desta execução foram normalizados." />
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-surface-border text-left text-xs text-slate-500">
                <th class="px-5 py-3 font-medium">Código</th>
                <th class="px-5 py-3 font-medium">C170</th>
                <th class="px-5 py-3 font-medium">Produto</th>
                <th class="px-5 py-3 font-medium">Pendência</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-border">
              <tr v-for="issue in exceptions" :key="`${issue.source_line}-${issue.code}`" class="hover:bg-surface-elevated/50">
                <td class="px-5 py-3 font-mono text-accent">{{ issue.product_code || '—' }}</td>
                <td class="px-5 py-3 font-mono text-slate-400">{{ issue.source_line }}</td>
                <td class="px-5 py-3 font-mono text-slate-500">C100 {{ issue.parent_c100_line ?? '—' }}</td>
                <td class="px-5 py-3">
                  <div class="font-medium text-amber-200">{{ issue.code }}</div>
                  <div class="mt-0.5 text-xs text-slate-400">{{ issue.message }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
