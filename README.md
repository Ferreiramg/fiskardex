# Fiskardex — Frontend

Cliente web para o sistema de análise Kardex (SPED EFD + NF-e XML).

Stack: **Vite · Vue 3 · TypeScript · Tailwind CSS · Pinia · Vue Router**

## Pré-requisitos

- Node.js 20+
- npm ou pnpm

## Setup

```bash
cd fiskardex
npm install
npm run dev
```

Abre em `http://localhost:5173`.

O Vite faz proxy de `/api` → `http://localhost:8000` (Symfony).

## Build para produção

```bash
npm run build
```

O output fica em `dist/`. Pode ser servido pelo Symfony (ex.: `public/build`) ou por qualquer static server.

## Estrutura

```
src/
├── api/           # Cliente Axios tipado
├── assets/        # CSS (Tailwind)
├── components/    # StatusBadge, EmptyState…
├── composables/
├── layouts/       # AppLayout (nav + shell)
├── router/
├── stores/        # Pinia (imports, …)
├── types/         # Interfaces TypeScript
└── views/
    ├── Import/    # Lista + detalhe (já funcional com mock)
    ├── Kardex/
    ├── Costing/
    ├── Allocation/
    ├── RecoverableIcms/
    └── Reports/
```

## Integração com o backend Symfony

Endpoints esperados (já tipados em `src/api/client.ts`):

| Método | Path | Descrição |
|--------|------|-----------|
| GET | `/api/imports` | Lista importações |
| GET | `/api/imports/{id}` | Detalhe + registros + issues |
| POST | `/api/imports` | Upload (multipart) |
| GET | `/api/kardex` | Kardex por produto |
| GET | `/api/costing/{fifo\|average}` | Resultado de custeio |
| GET | `/api/allocation` | Links compra → venda |
| GET | `/api/recoverable-icms` | Resumo ICMS recuperável |

Enquanto a API não estiver pronta, a store de importações usa **mock** baseado no EFD real do MVP (`completed_with_issues`, registros 0150, 0200, etc.).

## Próximos passos

1. Conectar `stores/imports.ts` à API real (remover mock).
2. Popular Kardex / Custeio / Alocação / ICMS a partir dos endpoints.
3. Adicionar gráficos (Chart.js ou similar) nos relatórios.
4. Autenticação se necessário (token / session Symfony).
