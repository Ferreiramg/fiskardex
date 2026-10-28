<script setup lang="ts">
defineProps<{
  open: boolean
  title: string
  description: string
  confirmLabel?: string
  loading?: boolean
}>()

const emit = defineEmits<{
  cancel: []
  confirm: []
}>()
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    :aria-label="title"
  >
    <button
      type="button"
      class="absolute inset-0 bg-slate-950/70"
      aria-label="Fechar confirmação"
      :disabled="loading"
      @click="emit('cancel')"
    />
    <section class="card relative w-full max-w-md p-6 shadow-2xl">
      <div class="flex gap-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-danger/10 text-danger">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3Z" />
          </svg>
        </div>
        <div>
          <h2 class="text-base font-semibold text-slate-100">{{ title }}</h2>
          <p class="mt-1 text-sm leading-6 text-slate-400">{{ description }}</p>
        </div>
      </div>
      <div class="mt-6 flex justify-end gap-3">
        <button type="button" class="btn-ghost" :disabled="loading" @click="emit('cancel')">Cancelar</button>
        <button type="button" class="btn-danger" :disabled="loading" @click="emit('confirm')">
          <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
            <path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z" />
          </svg>
          {{ loading ? 'Excluindo...' : (confirmLabel ?? 'Confirmar') }}
        </button>
      </div>
    </section>
  </div>
</template>
