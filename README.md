# Fiskardex

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
