<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { getImportSummary, listImportC100Documents } from '@/api/client'
import type { C100DocumentPage, ImportSummary } from '@/types'
import EmptyState from '@/components/EmptyState.vue'

const route = useRoute()
const summary = ref<ImportSummary | null>(null)
const loading = ref(true)
const error = ref('')
const documentPage = ref<C100DocumentPage | null>(null)
const documentDirection = ref<'entries' | 'exits'>('exits')
const documentSearch = ref('')
const appliedDocumentSearch = ref('')
const documentPageNumber = ref(1)
const documentsLoading = ref(false)

onMounted(loadSummary)

async function loadSummary() {
  loading.value = true
  error.value = ''
  try {
    summary.value = await getImportSummary(route.params.id as string)
    await loadDocuments()
  } catch {
    error.value = 'Não foi possível montar o resumo desta importação EFD.'
  } finally {
    loading.value = false
  }
}

async function loadDocuments() {
  documentsLoading.value = true
  try {
    documentPage.value = await listImportC100Documents(
      route.params.id as string,
      documentDirection.value,
      documentPageNumber.value,
      appliedDocumentSearch.value,
    )
  } catch {
    error.value = 'Não foi possível carregar os documentos C100.'
  } finally {
    documentsLoading.value = false
  }
}

function changeDocumentDirection(direction: 'entries' | 'exits') {
  documentDirection.value = direction
  documentPageNumber.value = 1
  loadDocuments()
}

function searchDocuments() {
  appliedDocumentSearch.value = documentSearch.value.trim()
  documentPageNumber.value = 1
  loadDocuments()
}

function pageDocuments(page: number) {
  documentPageNumber.value = page
  loadDocuments()
}

function money(value: string) {
  const amount = Number(value)
  return Number.isFinite(amount)
    ? amount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
    : '—'
}

function date(value: string | null) {
  if (!value) return '—'
  const [year, month, day] = value.split('-')
  return `${day}/${month}/${year}`
}

function period(start: string | null, end: string | null) {
  if (!start || !end) return '—'
  return `${date(start)} a ${date(end)}`
}
</script>

<template>
  <div v-if="loading" class="card p-6 text-sm text-slate-400">Montando resumo da escrituração…</div>
  <div v-else-if="error" class="space-y-4">
    <div class="rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-red-200">{{ error }}</div>
    <RouterLink to="/imports" class="btn-ghost">Voltar para importações</RouterLink>
  </div>
  <div v-else-if="summary && summary.taxpayer" class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <div class="text-xs text-slate-500">
          <RouterLink to="/imports" class="hover:text-slate-300">Importações</RouterLink>
          <span class="mx-1.5">/</span>
          <RouterLink :to="`/imports/${summary.source.id}`" class="hover:text-slate-300">{{ summary.source.filename }}</RouterLink>
        </div>
        <h1 class="mt-2 text-xl font-bold tracking-tight text-slate-50 sm:text-2xl">Relatório contábil — Entradas e saídas</h1>
        <p class="mt-1 text-sm text-slate-400">Resumo dos valores e da apuração declarados nesta EFD ICMS/IPI.</p>
      </div>
      <RouterLink :to="`/imports/${summary.source.id}`" class="btn-ghost">Explorar registros EFD</RouterLink>
    </div>

    <div class="card overflow-hidden">
      <div class="border-b border-surface-border px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-200">Identificação da escrituração</h2>
      </div>
      <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <dt class="text-xs font-medium text-slate-500">Contribuinte</dt>
          <dd class="mt-1 text-sm text-slate-100">{{ summary.taxpayer.name || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">CNPJ</dt>
          <dd class="mt-1 font-mono text-sm text-slate-200">{{ summary.taxpayer.cnpj || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">Inscrição estadual</dt>
          <dd class="mt-1 font-mono text-sm text-slate-200">{{ summary.taxpayer.stateRegistration || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">Período</dt>
          <dd class="mt-1 text-sm text-slate-200">{{ period(summary.taxpayer.periodStart, summary.taxpayer.periodEnd) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">Perfil EFD</dt>
          <dd class="mt-1 text-sm text-slate-200">{{ summary.taxpayer.profile || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">Cobertura dos registros</dt>
          <dd class="mt-1 text-sm" :class="summary.source.coverageComplete ? 'text-emerald-300' : 'text-amber-300'">
            {{ summary.source.recordsAvailable.toLocaleString('pt-BR') }} disponíveis
            <span v-if="summary.source.recordsDeclared">/ {{ summary.source.recordsDeclared.toLocaleString('pt-BR') }} declarados</span>
          </dd>
        </div>
      </dl>
    </div>

    <div v-if="summary.notes.length" class="space-y-2">
      <div
        v-for="note in summary.notes"
        :key="note"
        class="rounded-lg border border-amber-400/25 bg-amber-400/5 px-4 py-3 text-xs leading-relaxed text-amber-100"
      >
        {{ note }}
      </div>
    </div>

    <div class="card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[620px] text-sm">
          <thead>
            <tr class="bg-[#1e3a67] text-left text-xs font-semibold text-white">
              <th class="px-4 py-3">Indicador</th>
              <th class="px-4 py-3 text-right">Entradas (compras)</th>
              <th class="px-4 py-3 text-right">Saídas (vendas)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-border">
            <tr class="bg-surface-elevated/40">
              <td class="px-4 py-2.5 text-slate-300">Quantidade de documentos fiscais regulares</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ summary.documents.entries.count.toLocaleString('pt-BR') }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ summary.documents.exits.count.toLocaleString('pt-BR') }}</td>
            </tr>
            <tr>
              <td class="px-4 py-2.5 text-slate-300">Valor total dos documentos (C100)</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.entries.value) }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.exits.value) }}</td>
            </tr>
            <tr class="bg-surface-elevated/40">
              <td class="px-4 py-2.5 text-slate-300">Base de cálculo ICMS (C100)</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.entries.icmsBase) }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.exits.icmsBase) }}</td>
            </tr>
            <tr>
              <td class="px-4 py-2.5 text-slate-300">ICMS destacado (C100)</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.entries.icms) }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.exits.icms) }}</td>
            </tr>
            <tr class="bg-surface-elevated/40">
              <td class="px-4 py-2.5 text-slate-300">Base de cálculo ICMS-ST (C100)</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.entries.icmsStBase) }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.exits.icmsStBase) }}</td>
            </tr>
            <tr>
              <td class="px-4 py-2.5 text-slate-300">ICMS-ST (C100)</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.entries.icmsSt) }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.exits.icmsSt) }}</td>
            </tr>
            <tr class="bg-surface-elevated/40">
              <td class="px-4 py-2.5 text-slate-300">IPI (C100)</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.entries.ipi) }}</td>
              <td class="px-4 py-2.5 text-right tabular-nums text-slate-100">{{ money(summary.documents.exits.ipi) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <details class="card overflow-hidden">
      <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-200">
        Conferência analítica C190
        <span class="ml-2 text-xs font-normal text-slate-500">Totais por CST, CFOP e alíquota; não substituem os campos por documento do C100.</span>
      </summary>
      <div class="overflow-x-auto border-t border-surface-border">
        <table class="w-full min-w-[620px] text-xs">
          <thead class="bg-surface-elevated/50 text-left text-slate-500">
            <tr><th class="px-4 py-2.5">Indicador C190</th><th class="px-4 py-2.5 text-right">Entradas</th><th class="px-4 py-2.5 text-right">Saídas</th></tr>
          </thead>
          <tbody class="divide-y divide-surface-border">
            <tr><td class="px-4 py-2.5 text-slate-300">Valor da operação</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.entries.operationValue) }}</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.exits.operationValue) }}</td></tr>
            <tr><td class="px-4 py-2.5 text-slate-300">Base de cálculo ICMS</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.entries.icmsBase) }}</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.exits.icmsBase) }}</td></tr>
            <tr><td class="px-4 py-2.5 text-slate-300">ICMS</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.entries.icms) }}</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.exits.icms) }}</td></tr>
            <tr><td class="px-4 py-2.5 text-slate-300">Base ICMS-ST</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.entries.icmsStBase) }}</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.exits.icmsStBase) }}</td></tr>
            <tr><td class="px-4 py-2.5 text-slate-300">ICMS-ST</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.entries.icmsSt) }}</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.exits.icmsSt) }}</td></tr>
            <tr><td class="px-4 py-2.5 text-slate-300">IPI</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.entries.ipi) }}</td><td class="px-4 py-2.5 text-right">{{ money(summary.analytical.exits.ipi) }}</td></tr>
          </tbody>
        </table>
      </div>
    </details>

    <section class="card overflow-hidden">
      <header class="flex flex-col gap-3 border-b border-surface-border p-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-sm font-semibold text-slate-100">Documentos fiscais (registro C100)</h2>
          <p class="mt-1 text-xs text-slate-500">Valores por documento conforme a planilha de entradas e saídas.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div class="flex rounded-lg border border-surface-border bg-surface p-1">
            <button class="rounded-md px-3 py-1.5 text-xs" :class="documentDirection === 'entries' ? 'bg-surface-elevated text-slate-100' : 'text-slate-400'" @click="changeDocumentDirection('entries')">Entradas</button>
            <button class="rounded-md px-3 py-1.5 text-xs" :class="documentDirection === 'exits' ? 'bg-surface-elevated text-slate-100' : 'text-slate-400'" @click="changeDocumentDirection('exits')">Saídas</button>
          </div>
          <form class="flex gap-2" @submit.prevent="searchDocuments">
            <input v-model="documentSearch" class="input w-44 py-1.5 text-xs" placeholder="Buscar número ou chave" />
            <button class="btn-ghost px-3 py-1.5 text-xs" type="submit" :disabled="documentsLoading">Buscar</button>
          </form>
        </div>
      </header>
      <div v-if="documentsLoading" class="p-6 text-center text-xs text-slate-500">Carregando documentos…</div>
      <div v-else-if="documentPage && documentPage.items.length" class="max-h-[60vh] overflow-auto">
        <table class="w-full min-w-[1250px] text-xs">
          <thead class="sticky top-0 z-10 bg-[#1e3a67] text-left font-semibold text-white">
            <tr>
              <th class="px-3 py-2.5">Data</th><th class="px-3 py-2.5">Mod.</th><th class="px-3 py-2.5">Série</th>
              <th class="px-3 py-2.5">Número</th><th class="px-3 py-2.5">Sit.</th><th class="px-3 py-2.5 text-right">Valor doc.</th>
              <th class="px-3 py-2.5 text-right">BC ICMS</th><th class="px-3 py-2.5 text-right">ICMS</th>
              <th class="px-3 py-2.5 text-right">BC ST</th><th class="px-3 py-2.5 text-right">ICMS-ST</th><th class="px-3 py-2.5">Chave</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(document, index) in documentPage.items" :key="document.line" :class="index % 2 ? 'bg-surface-elevated/40' : 'bg-surface-card'" class="border-b border-surface-border/70">
              <td class="whitespace-nowrap px-3 py-2 text-slate-300">{{ date(document.date) }}</td>
              <td class="px-3 py-2 font-mono text-slate-400">{{ document.model }}</td>
              <td class="px-3 py-2 font-mono text-slate-400">{{ document.series }}</td>
              <td class="px-3 py-2 font-mono text-slate-200">{{ document.number }}</td>
              <td class="px-3 py-2 font-mono text-slate-400">{{ document.status }}</td>
              <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums text-slate-100">{{ money(document.documentValue) }}</td>
              <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums text-slate-300">{{ money(document.icmsBase) }}</td>
              <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums text-slate-300">{{ money(document.icms) }}</td>
              <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums text-slate-300">{{ money(document.icmsStBase) }}</td>
              <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums text-slate-300">{{ money(document.icmsSt) }}</td>
              <td class="max-w-72 truncate px-3 py-2 font-mono text-[10px] text-slate-500" :title="document.key">{{ document.key || '—' }}</td>
            </tr>
          </tbody>
          <tfoot class="sticky bottom-0 border-t-2 border-surface-border bg-surface-elevated font-semibold text-slate-100 shadow-[0_-4px_8px_rgba(0,0,0,0.15)]">
            <tr>
              <td colspan="5" class="px-3 py-2.5">TOTAL{{ appliedDocumentSearch ? ' DO FILTRO' : '' }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ money(documentPage.totals.documentValue) }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ money(documentPage.totals.icmsBase) }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ money(documentPage.totals.icms) }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ money(documentPage.totals.icmsStBase) }}</td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ money(documentPage.totals.icmsSt) }}</td>
              <td class="px-3 py-2.5"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <EmptyState v-else title="Nenhum documento encontrado" description="Não há documentos regulares para este filtro nesta direção." />
      <footer v-if="documentPage && documentPage.total" class="flex items-center justify-between border-t border-surface-border px-4 py-3 text-xs text-slate-500">
        <span>{{ documentPage.total.toLocaleString('pt-BR') }} documentos · página {{ documentPage.page }} de {{ Math.max(documentPage.pages, 1) }}</span>
        <div class="flex gap-2">
          <button class="btn-ghost px-3 py-1.5" :disabled="documentPageNumber <= 1 || documentsLoading" @click="pageDocuments(documentPageNumber - 1)">Anterior</button>
          <button class="btn-ghost px-3 py-1.5" :disabled="documentPageNumber >= documentPage.pages || documentsLoading" @click="pageDocuments(documentPageNumber + 1)">Próxima</button>
        </div>
      </footer>
    </section>

    <section class="card overflow-hidden">
      <div class="border-b border-surface-border px-5 py-3">
        <h2 class="text-sm font-semibold text-slate-100">Apuração do ICMS (registro E110)</h2>
        <p class="mt-1 text-xs text-slate-500">Valores transcritos da apuração informada na EFD; não são cálculo de crédito recuperável.</p>
      </div>
      <div v-if="summary.apurations.length" class="divide-y divide-surface-border">
        <div v-for="(apuration, index) in summary.apurations" :key="index" class="p-5">
          <div class="mb-3 text-xs font-medium text-slate-400">Período de apuração: {{ apuration.period ? period(apuration.period.start, apuration.period.end) : 'não informado' }}</div>
          <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg bg-surface-elevated/45 p-3">
              <dt class="text-xs text-slate-500">Débito por saídas</dt>
              <dd class="mt-1 font-semibold tabular-nums text-slate-100">{{ money(apuration.debits) }}</dd>
            </div>
            <div class="rounded-lg bg-surface-elevated/45 p-3">
              <dt class="text-xs text-slate-500">Créditos por entradas</dt>
              <dd class="mt-1 font-semibold tabular-nums text-slate-100">{{ money(apuration.entryCredits) }}</dd>
            </div>
            <div class="rounded-lg bg-surface-elevated/45 p-3">
              <dt class="text-xs text-slate-500">Saldo devedor apurado</dt>
              <dd class="mt-1 font-semibold tabular-nums text-slate-100">{{ money(apuration.balanceDue) }}</dd>
            </div>
            <div class="rounded-lg bg-surface-elevated/45 p-3">
              <dt class="text-xs text-slate-500">Deduções</dt>
              <dd class="mt-1 font-semibold tabular-nums text-slate-100">{{ money(apuration.deductions) }}</dd>
            </div>
            <div class="rounded-lg border border-accent/20 bg-accent/5 p-3">
              <dt class="text-xs text-slate-400">ICMS a recolher</dt>
              <dd class="mt-1 font-semibold tabular-nums text-blue-200">{{ money(apuration.payable) }}</dd>
            </div>
          </dl>
        </div>
      </div>
      <EmptyState v-else title="Sem registro E110" description="Esta importação não contém valores de apuração própria do ICMS." />
      <div v-if="summary.obligations.length" class="border-t border-surface-border px-5 py-4">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Obrigações informadas (E116)</h3>
        <div class="mt-2 flex flex-wrap gap-2">
          <div v-for="(item, index) in summary.obligations" :key="index" class="rounded-lg border border-surface-border px-3 py-2 text-xs text-slate-300">
            Vencimento {{ date(item.dueDate) }} · {{ money(item.amount) }}<span v-if="item.revenueCode"> · receita {{ item.revenueCode }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card p-5">
      <h2 class="text-sm font-semibold text-slate-100">Observações e origem dos dados</h2>
      <ul class="mt-3 space-y-2 text-xs leading-relaxed text-slate-400">
        <li>Os totais de documentos consideram C100 com situação regular (COD_SIT = 00). Os campos por documento vêm do C100; os valores analíticos agregados do C190 são mostrados separadamente para conferência.</li>
        <li v-if="summary.exitC170Count === 0">A EFD não contém C170 de saída para detalhamento por produto; os valores de venda vêm dos registros analíticos C190.</li>
        <li v-else>Foram encontrados {{ summary.exitC170Count.toLocaleString('pt-BR') }} registro(s) C170 de saída; a cobertura por produto deve ser conferida com a escrituração e as regras aplicáveis.</li>
        <li>Fonte original: {{ summary.source.filename }} · SHA-256 <span class="break-all font-mono">{{ summary.source.sha256 }}</span></li>
      </ul>
    </section>
  </div>
  <div v-else class="card p-6">
    <EmptyState title="Resumo indisponível" description="Não foi possível localizar os dados cadastrais do registro 0000 nesta EFD." />
  </div>
</template>
