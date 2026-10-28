# Fiskardex

MVP para ingestão e visualização de documentos fiscais EFD/TXT e NF-e XML.

## Estrutura

- `src/Application/`: ações de aplicação organizadas por domínio, como upload, consulta e exclusão de importações.
- `src/Domain/`: contratos e regras centrais do negócio, sem dependência de HTTP ou banco.
- `src/Http/`: rotas e controllers que traduzem requisições e respostas HTTP.
- `src/Storage/` e `src/Database/`: persistência SQLite e migrations.
- `src/Fiscal/`: parsers dos formatos fiscais suportados.
- `src/Application.php`: composição das dependências e entrada HTTP; regras de negócio ficam nas ações.
- `frontend/src/`: código da aplicação Vue 3.
- raiz: configuração e dependências do frontend (Vite, TypeScript, Tailwind e npm).
- `public/`: front controller PHP e arquivos estáticos gerados pelo build do Vue.
- `tests/`: testes do backend.

## Executar em desenvolvimento

Em um terminal, inicie a API PHP:

```powershell
composer serve
```

Em outro terminal, instale dependências e inicie o frontend:

```powershell
npm install
npm run dev
```

O Vite atende em `http://localhost:5173` e encaminha `/api` para o backend em
`http://localhost:8000`. A interface usa os endpoints reais abaixo:

- `GET /api/v1/imports`
- `GET /api/v1/imports/{id}`
- `POST /api/v1/imports`
- `DELETE /api/v1/imports/{id}`

As áreas de Kardex, custeio, alocação e ICMS permanecem como telas de produto:
os endpoints correspondentes ainda não existem no backend.

## Build de produção

```powershell
npm run build
```

O build é emitido em `public/`; em seguida, `composer serve` disponibiliza a
aplicação integrada.

## Qualidade

```powershell
composer check
npm run typecheck
```

## Banco de dados

O MVP usa SQLite em `var/data/fiskardex.sqlite`. Para criar ou atualizar o
schema, execute:

```powershell
composer migrate
```

A migration inicial cria as tabelas `imports`, `efd_records` e `import_issues`
para receber os metadados, registros e ocorrências extraídos de arquivos EFD.
