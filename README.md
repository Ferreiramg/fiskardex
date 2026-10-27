# Fiskardex — Frontend

MVP de ingestão e visualização de documentos fiscais para o laboratório Fiskardex.

## Estado atual

Aplicação em PHP 8.4 usando componentes Symfony leves para HTTP, roteamento e identificadores. O frontend é HTML, JavaScript e Tailwind via CDN no protótipo.

O MVP implementado:

- recebe EFD/TXT e XML NF-e;
- preserva o original em `var/data/uploads`;
- calcula SHA-256 e aplica idempotência por hash;
- interpreta registros EFD em streaming;
- identifica período e contagens por registro;
- processa a estrutura inicial de NF-e XML;
- registra problemas de codificação e parsing;
- exibe importações, diagnóstico e documentos na interface web.

Ainda não implementa autenticação, PostgreSQL, fila, conciliação econômica, Kardex, FIFO ou cálculo tributário.

## Executar

```powershell
composer install
composer test
composer serve
```

Abra `http://127.0.0.1:8000`.

## Estrutura

- `public/index.php`: front controller HTTP.
- `src/Application.php`: rotas e casos de uso do MVP.
- `src/Fiscal/`: parsers EFD e XML.
- `src/Storage/`: persistência local de laboratório.
- `public/index.html`: interface de upload e visualização.
- `docs/specs/07-mvp-upload-visualizacao.md`: especificação do incremento.

Dados fiscais reais não devem ser versionados neste repositório. A persistência JSON e o Tailwind via CDN são escolhas temporárias para o laboratório; antes de produção devem ser substituídos por PostgreSQL, armazenamento privado de objetos, autenticação e build local do frontend.
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
