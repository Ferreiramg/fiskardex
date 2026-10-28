<script setup lang="ts">
import { ref } from 'vue'

const method = ref<'fifo' | 'average'>('fifo')
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-50">Custeio</h1>
        <p class="text-sm text-slate-400 mt-1">
          Comparação PEPS (FIFO) × Custo Médio a partir do Kardex reconstruído.
        </p>
      </div>

      <div class="inline-flex rounded-lg border border-surface-border bg-surface-elevated p-1">
        <button
          type="button"
          class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
          :class="method === 'fifo' ? 'bg-accent text-white' : 'text-slate-400 hover:text-slate-200'"
          @click="method = 'fifo'"
        >
          PEPS (FIFO)
        </button>
        <button
          type="button"
          class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
          :class="method === 'average' ? 'bg-accent text-white' : 'text-slate-400 hover:text-slate-200'"
          @click="method = 'average'"
        >
          Custo Médio
        </button>
      </div>
    </div>

    <div class="card p-8 text-center">
      <div class="mx-auto w-12 h-12 rounded-full bg-surface-elevated flex items-center justify-center mb-4 text-slate-500">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
      </div>
      <h3 class="text-base font-semibold text-slate-200 mb-1">
        Método ativo: {{ method === 'fifo' ? 'PEPS (FIFO)' : 'Custo Médio' }}
      </h3>
      <p class="text-sm text-slate-500 max-w-md mx-auto">
        Os resultados de custeio serão calculados pelo backend a partir do Kardex.
        Conecte a API <code class="text-accent">/api/costing/{{ method }}</code> para popular esta tela.
      </p>
    </div>
  </div>
</template>
