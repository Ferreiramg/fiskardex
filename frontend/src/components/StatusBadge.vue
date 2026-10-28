<script setup lang="ts">
import { computed } from 'vue'
import type { ImportStatus } from '@/types'

const props = defineProps<{
  status: ImportStatus
}>()

const label = computed(() => {
  const map: Record<ImportStatus, string> = {
    pending: 'Pendente',
    processing: 'Processando',
    completed: 'Concluído',
    completed_with_issues: 'Concluído c/ issues',
    failed: 'Falhou',
  }
  return map[props.status] ?? props.status
})

const classes = computed(() => {
  const map: Record<ImportStatus, string> = {
    pending: 'badge-neutral',
    processing: 'badge-warning',
    completed: 'badge-success',
    completed_with_issues: 'badge-warning',
    failed: 'badge-danger',
  }
  return map[props.status] ?? 'badge-neutral'
})
</script>

<template>
  <span :class="classes">
    <span
      v-if="status === 'processing'"
      class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"
    />
    {{ label }}
  </span>
</template>
