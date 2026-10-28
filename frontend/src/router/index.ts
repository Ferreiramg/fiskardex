import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      redirect: '/imports',
    },
    {
      path: '/imports',
      name: 'imports',
      component: () => import('@/views/Import/Index.vue'),
      meta: { title: 'Importações' },
    },
    {
      path: '/imports/:id/summary',
      name: 'import-summary',
      component: () => import('@/views/Import/Summary.vue'),
      meta: { title: 'Resumo da Importação' },
    },
    {
      path: '/imports/:id',
      name: 'import-detail',
      component: () => import('@/views/Import/Detail.vue'),
      meta: { title: 'Detalhe da Importação' },
    },
    {
      path: '/kardex',
      name: 'kardex',
      component: () => import('@/views/Kardex/Index.vue'),
      meta: { title: 'Kardex' },
    },
    {
      path: '/costing',
      name: 'costing',
      component: () => import('@/views/Costing/Index.vue'),
      meta: { title: 'Custeio' },
    },
    {
      path: '/allocation',
      name: 'allocation',
      component: () => import('@/views/Allocation/Index.vue'),
      meta: { title: 'Alocação Compra → Venda' },
    },
    {
      path: '/recoverable-icms',
      name: 'recoverable-icms',
      component: () => import('@/views/RecoverableIcms/Index.vue'),
      meta: { title: 'ICMS Recuperável' },
    },
    {
      path: '/reports',
      name: 'reports',
      component: () => import('@/views/Reports/Index.vue'),
      meta: { title: 'Relatórios' },
    },
  ],
})

router.afterEach((to) => {
  const title = (to.meta.title as string) || 'Fiskardex'
  document.title = `${title} · Fiskardex`
})

export default router
