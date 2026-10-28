<script setup lang="ts">
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-50">ICMS Recuperável</h1>
      <p class="text-sm text-slate-400 mt-1">
        Cálculo parametrizável: (preço venda − custo compra) × alíquota, com base no link compra→venda.
      </p>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card p-4">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total vendas</div>
        <div class="mt-1 text-xl font-bold text-slate-100 tabular-nums">—</div>
      </div>
      <div class="card p-4">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Base de custo</div>
        <div class="mt-1 text-xl font-bold text-slate-100 tabular-nums">—</div>
      </div>
      <div class="card p-4">
        <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Alíquota</div>
        <div class="mt-1 text-xl font-bold text-slate-100 tabular-nums">18%</div>
      </div>
      <div class="card p-4 border-accent/30 bg-accent/5">
        <div class="text-xs font-medium text-accent uppercase tracking-wide">ICMS recuperável</div>
        <div class="mt-1 text-xl font-bold text-accent tabular-nums">—</div>
      </div>
    </div>

    <div class="card p-8 text-center">
      <div class="mx-auto w-12 h-12 rounded-full bg-surface-elevated flex items-center justify-center mb-4 text-slate-500">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-base font-semibold text-slate-200 mb-1">Aguardando processamento</h3>
      <p class="text-sm text-slate-500 max-w-lg mx-auto">
        A lógica proposta (Ney): mapear compra↔venda via Kardex/PEPS nos últimos 5 anos e aplicar
        <code class="text-accent">(preço venda − custo compra) × 18%</code>.
        Conecte <code class="text-accent">/api/recoverable-icms</code> para popular os KPIs.
      </p>
    </div>
  </div>
</template>
