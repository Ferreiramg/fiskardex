<script setup lang="ts">
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-50">Relatórios</h1>
      <p class="text-sm text-slate-400 mt-1">
        Insights e exportações para análise fiscal e jurídico.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="card p-5 hover:border-accent/40 transition-colors cursor-pointer">
        <h3 class="text-sm font-semibold text-slate-200">Resumo de importações</h3>
        <p class="text-xs text-slate-500 mt-1">Status, issues e contagem de registros por arquivo.</p>
      </div>
      <div class="card p-5 hover:border-accent/40 transition-colors cursor-pointer">
        <h3 class="text-sm font-semibold text-slate-200">Kardex consolidado</h3>
        <p class="text-xs text-slate-500 mt-1">Saldos e custos por produto no período.</p>
      </div>
      <div class="card p-5 hover:border-accent/40 transition-colors cursor-pointer">
        <h3 class="text-sm font-semibold text-slate-200">Comparativo PEPS × Média</h3>
        <p class="text-xs text-slate-500 mt-1">Diferenças de base de custo entre métodos.</p>
      </div>
      <div class="card p-5 hover:border-accent/40 transition-colors cursor-pointer">
        <h3 class="text-sm font-semibold text-slate-200">ICMS recuperável</h3>
        <p class="text-xs text-slate-500 mt-1">Base, alíquota e valor potencial por período.</p>
      </div>
    </div>

    <div class="card p-6 text-center text-sm text-slate-500">
      Exportações (PDF / Excel) serão geradas pelo backend e disponibilizadas aqui.
    </div>
  </div>
</template>
