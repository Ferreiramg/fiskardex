<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'

const route = useRoute()
const theme = ref<'dark' | 'light'>(
  document.documentElement.dataset.theme === 'light' ? 'light' : 'dark',
)

function toggleTheme() {
  const nextTheme = theme.value === 'dark' ? 'light' : 'dark'
  localStorage.setItem('fiskardex-theme', nextTheme)
  document.documentElement.dataset.theme = nextTheme
  theme.value = nextTheme
}

const nav = [
  { to: '/imports', label: 'Importações', icon: 'upload' },
  { to: '/kardex', label: 'Kardex', icon: 'layers' },
  { to: '/costing', label: 'Custeio', icon: 'calculator' },
  { to: '/allocation', label: 'Alocação', icon: 'link' },
  { to: '/recoverable-icms', label: 'ICMS Recuperável', icon: 'currency' },
  { to: '/reports', label: 'Relatórios', icon: 'chart' },
]

</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Top bar -->
    <header class="border-b border-surface-border bg-surface-card/80 backdrop-blur sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <RouterLink to="/" class="flex items-center gap-2.5 group">
            <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-accent/20">
              Fk
            </div>
            <div class="leading-tight">
              <div class="font-semibold text-slate-100 group-hover:text-slate-100 transition-colors">
                Fiskardex
              </div>
              <div class="text-[11px] text-slate-500 -mt-0.5">
                MVP · Ingestão e análise fiscal
              </div>
            </div>
          </RouterLink>
        </div>

        <nav class="hidden md:flex items-center gap-1">
          <RouterLink
            v-for="item in nav"
            :key="item.to"
            :to="item.to"
            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
            :class="
              route.path.startsWith(item.to)
                ? 'bg-accent/15 text-accent'
                : 'text-slate-400 hover:text-slate-100 hover:bg-surface-elevated'
            "
          >
            {{ item.label }}
          </RouterLink>
        </nav>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="btn-ghost px-3"
            :aria-pressed="theme === 'light'"
            aria-label="Alternar tema de cores"
            :title="theme === 'dark' ? 'Ativar modo claro' : 'Ativar modo escuro'"
            @click="toggleTheme"
          >
            <svg
              v-if="theme === 'dark'"
              aria-hidden="true"
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <circle cx="12" cy="12" r="4" />
              <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
            </svg>
            <svg
              v-else
              aria-hidden="true"
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z" />
            </svg>
            <span class="hidden sm:inline">
              {{ theme === 'dark' ? 'Modo claro' : 'Modo escuro' }}
            </span>
          </button>
          <span class="badge-neutral hidden sm:inline-flex">
            <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
            Local
          </span>
        </div>
      </div>
    </header>

    <!-- Mobile nav -->
    <nav class="md:hidden border-b border-surface-border bg-surface-card overflow-x-auto">
      <div class="flex px-2 py-2 gap-1 min-w-max">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap"
          :class="
            route.path.startsWith(item.to)
              ? 'bg-accent/15 text-accent'
              : 'text-slate-400'
          "
        >
          {{ item.label }}
        </RouterLink>
      </div>
    </nav>

    <!-- Content -->
    <main class="flex-1">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <slot />
      </div>
    </main>

    <footer class="border-t border-surface-border py-4 text-center text-xs text-slate-500">
      Fiskardex · Laboratório Fiscal · Análise Kardex / PEPS / ICMS recuperável
    </footer>
  </div>
</template>
