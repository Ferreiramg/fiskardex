<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useImportsStore } from '@/stores/imports'
import StatusBadge from '@/components/StatusBadge.vue'

const route = useRoute()
const router = useRouter()
const store = useImportsStore()

const id = computed(() => route.params.id as string)

onMounted(() => {
  if (!store.items.length) store.fetchAll()
  store.select(id.value)
})

const item = computed(() => store.selected)

function formatPeriod(start?: string, end?: string) {
  if (!start || !end) return '—'
  const fmt = (s: string) =>
    s.length === 8 ? `${s.slice(0, 2)}/${s.slice(2, 4)}/${s.slice(4)}` : s
  return `${fmt(start)} — ${fmt(end)}`
}

const registerEntries = computed(() => {
  if (!item.value?.registers) return []
  return Object.entries(item.value.registers)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([code, count]) => ({ code, count }))
})

const totalRegisters = computed(() =>
  registerEntries.value.reduce((sum, r) => sum + r.count, 0)
)
</script>

<template>
  <div v-if="!item" class="space-y-4">
    <p class="text-slate-400">Importação não encontrada.</p>
    <RouterLink to="/imports" class="btn-ghost">← Voltar</RouterLink>
  </div>

  <div v-else class="space-y-6">
    <!-- Breadcrumb / back -->
    <div class="flex items-center gap-2 text-sm">
      <RouterLink to="/imports" class="text-slate-500 hover:text-slate-300">Importações</RouterLink>
      <span class="text-slate-600">/</span>
      <span class="text-slate-300 truncate max-w-xs">{{ item.filename }}</span>
    </div>

    <!-- Header card -->
    <div class="card p-5 sm:p-6">
      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="min-w-0">
          <h1 class="text-lg sm:text-xl font-bold text-slate-50 break-all">
            {{ item.filename }}
          </h1>
          <div class="mt-3 flex flex-wrap gap-2">
            <StatusBadge :status="item.status" />
            <span class="badge-neutral uppercase">{{ item.type }}</span>
          </div>
        </div>
      </div>

      <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div>
          <dt class="text-slate-500 text-xs font-medium uppercase tracking-wide">Status</dt>
          <dd class="mt-1 text-slate-200">
            <StatusBadge :status="item.status" />
          </dd>
        </div>
        <div>
          <dt class="text-slate-500 text-xs font-medium uppercase tracking-wide">Período</dt>
          <dd class="mt-1 text-slate-200 font-mono text-xs">
            {{ formatPeriod(item.periodStart, item.periodEnd) }}
          </dd>
        </div>
        <div>
          <dt class="text-slate-500 text-xs font-medium uppercase tracking-wide">Codificação</dt>
          <dd class="mt-1 text-slate-200">{{ item.encoding ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-slate-500 text-xs font-medium uppercase tracking-wide">SHA-256</dt>
          <dd class="mt-1 text-slate-400 font-mono text-xs break-all">{{ item.hash }}</dd>
        </div>
      </dl>
    </div>

    <!-- Issues -->
    <div v-if="item.issues?.length" class="card overflow-hidden">
      <div class="px-5 py-3 border-b border-surface-border">
        <h2 class="text-sm font-semibold text-slate-200">Issues ({{ item.issues.length }})</h2>
      </div>
      <ul class="divide-y divide-surface-border">
        <li
          v-for="(issue, idx) in item.issues"
          :key="idx"
          class="px-5 py-3 flex gap-3 text-sm"
        >
          <span
            class="mt-0.5 shrink-0 w-2 h-2 rounded-full"
            :class="{
              'bg-blue-400': issue.severity === 'info',
              'bg-warning': issue.severity === 'warning',
              'bg-danger': issue.severity === 'error',
            }"
          />
          <div>
            <span class="font-mono text-xs text-slate-500">{{ issue.code }}</span>
            <p class="text-slate-300">{{ issue.message }}</p>
          </div>
        </li>
      </ul>
    </div>

    <!-- Registers -->
    <div class="card overflow-hidden">
      <div class="px-5 py-3 border-b border-surface-border flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-200">Registros</h2>
        <span class="text-xs text-slate-500">
          {{ totalRegisters }} total · {{ registerEntries.length }} tipos
        </span>
      </div>

      <div v-if="registerEntries.length === 0" class="p-6 text-sm text-slate-500">
        Nenhum registro processado ainda.
      </div>

      <div v-else class="p-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <div
            v-for="reg in registerEntries"
            :key="reg.code"
            class="rounded-lg bg-surface-elevated border border-surface-border px-3 py-2.5"
          >
            <div class="font-mono text-xs text-slate-500">{{ reg.code }}</div>
            <div class="text-lg font-semibold text-slate-100 tabular-nums">
              {{ reg.count.toLocaleString('pt-BR') }}
            </div>
          </div>
        </div>

        <!-- JSON preview (like the original MVP) -->
        <details class="mt-6 group">
          <summary class="text-xs text-slate-500 cursor-pointer hover:text-slate-300 select-none">
            Ver JSON bruto
          </summary>
          <pre class="mt-3 p-4 rounded-lg bg-surface text-xs font-mono text-slate-400 overflow-x-auto border border-surface-border">{{ JSON.stringify(item.registers, null, 2) }}</pre>
        </details>
      </div>
    </div>

    <!-- Next actions -->
    <div class="flex flex-wrap gap-3">
      <RouterLink to="/kardex" class="btn-primary">
        Ir para Kardex
      </RouterLink>
      <RouterLink to="/imports" class="btn-ghost">
        ← Voltar à lista
      </RouterLink>
    </div>
  </div>
</template>
