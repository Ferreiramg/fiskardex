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

Kardex tem endpoints de consulta das execuções, movimentos e pendências. Custeio,
alocação e ICMS permanecem como telas de produto sem processamento conectado.

## Kardex: entradas EFD

Após importar um EFD, execute o lote para criar movimentos dos C170 ligados a
C100 de entrada (`IND_OPER = 0`):

```powershell
php bin/process-kardex <import-id>
```

Importações antigas que tenham sido persistidas parcialmente podem ser
reconstruídas do arquivo original preservado, sem alterar seus bytes ou hash:

```powershell
php bin/reparse-efd.php <import-id>
```

O lote usa o 0200 para identificar o código e a unidade de inventário e o 0220
para converter a unidade do C170. Fatores ausentes, inválidos, conflitantes,
produtos ambíguos e documentos cancelados ficam no relatório como exceções; não
geram movimentos. A mesma importação e versão do algoritmo retornam a execução
existente. Uma atualização de regra deve usar nova versão de algoritmo.

Cada movimento representa um C170 e mantém quantidades/unidades original e
base, fator, valor original e linhas/base64 dos registros de origem. A conversão
guarda até 8 casas decimais. O arquivo carregado permanece preservado no
armazenamento de importação. Camadas FIFO, alocações e ICMS recuperável não fazem
parte deste incremento.

Na interface, `/kardex` apresenta os itens aceitos e as pendências de cada
execução. A tabela inclui os campos fiscais do C170 e permite localizar o C170
e seu C100 pelas linhas de origem. A API de consulta oferece:

- `GET /api/v1/kardex/runs`
- `GET /api/v1/kardex/runs/{id}/movements` (paginado e com busca)
- `GET /api/v1/kardex/runs/{id}/exceptions`

O detalhe de cada importação também oferece o resumo contábil em
`/imports/{id}/summary`, com totais C100/C190 e os valores declarados em
E110/E116. A página não calcula ICMS recuperável.

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
