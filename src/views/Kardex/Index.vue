<script setup lang="ts">
import { ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'

// Placeholder mock — will be replaced by API
const products = ref([
  {
    code: 'PROD-001',
    description: 'Produto exemplo A',
    ncm: '22021000',
    unit: 'UN',
    currentQty: 120,
    currentCost: 1845.60,
  },
  {
    code: 'PROD-002',
    description: 'Produto exemplo B',
    ncm: '22030000',
    unit: 'CX',
    currentQty: 45,
    currentCost: 980.00,
  },
])

const selected = ref<string | null>(null)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-50">Kardex</h1>
      <p class="text-sm text-slate-400 mt-1">
        Movimentações de estoque reconstruídas a partir de EFD e NF-e.
      </p>
    </div>

    <div class="card overflow-hidden">
      <div class="px-5 py-3 border-b border-surface-border flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-200">Produtos</h2>
        <span class="text-xs text-slate-500">{{ products.length }} item(ns)</span>
      </div>

      <div v-if="products.length === 0" class="p-6">
        <EmptyState
          title="Kardex vazio"
          description="Importe um EFD ou NF-e e processe o estoque para visualizar o Kardex."
        />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-slate-500 border-b border-surface-border">
              <th class="px-5 py-3 font-medium">Código</th>
              <th class="px-5 py-3 font-medium">Descrição</th>
              <th class="px-5 py-3 font-medium hidden sm:table-cell">NCM</th>
              <th class="px-5 py-3 font-medium text-right">Saldo</th>
              <th class="px-5 py-3 font-medium text-right">Custo atual</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-border">
            <tr
              v-for="p in products"
              :key="p.code"
              class="hover:bg-surface-elevated/50 cursor-pointer"
              :class="selected === p.code ? 'bg-accent/5' : ''"
              @click="selected = p.code"
            >
              <td class="px-5 py-3.5 font-mono text-accent">{{ p.code }}</td>
              <td class="px-5 py-3.5 text-slate-200">{{ p.description }}</td>
              <td class="px-5 py-3.5 text-slate-400 font-mono text-xs hidden sm:table-cell">{{ p.ncm }}</td>
              <td class="px-5 py-3.5 text-right tabular-nums text-slate-200">
                {{ p.currentQty.toLocaleString('pt-BR') }} {{ p.unit }}
              </td>
              <td class="px-5 py-3.5 text-right tabular-nums text-slate-200">
                R$ {{ p.currentCost.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="selected" class="card p-5">
      <h3 class="text-sm font-semibold text-slate-200 mb-3">
        Movimentações · {{ selected }}
      </h3>
      <p class="text-sm text-slate-500">
        Em breve: timeline de entradas, saídas e ajustes com saldo e custo unitário (PEPS / média).
      </p>
    </div>
  </div>
</template>
