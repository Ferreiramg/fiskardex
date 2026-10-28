# 07 — MVP de upload e visualização fiscal

## Objetivo

Entregar o primeiro fluxo utilizável do Fiskardex: um operador seleciona uma empresa e um estabelecimento, envia arquivos fiscais, acompanha o processamento e inspeciona os dados preservados e normalizados com suas pendências.

Este incremento prova a fundação de ingestão, rastreabilidade, isolamento e leitura dos dados. Ele não promete custeio FIFO, vínculo completo entre compra e venda, crédito tributário ou conformidade PVA.

## Usuário e resultado

O usuário do MVP é o operador. Ao final de uma importação, ele deve conseguir:

1. identificar o arquivo por nome original, tamanho, hash, período inferido e status;
2. saber se o arquivo foi aceito, colocado em quarentena ou rejeitado;
3. consultar contagens por tipo de registro e os primeiros problemas encontrados;
4. navegar pelos documentos fiscais e itens que puderam ser extraídos;
5. abrir a origem de um registro sem alterar o arquivo original;
6. exportar um diagnóstico técnico pequeno para revisão, sem expor o arquivo completo por padrão.

## Escopo do primeiro corte

### Incluído

- Um monólito web PHP 8.4 usando componentes Symfony leves, PostgreSQL e armazenamento privado local/S3 compatível.
- Organização, empresa/estabelecimento e usuário operador em um contexto autorizado.
- Upload de EFD ICMS/IPI texto e XML NF-e modelo 55, inicialmente um arquivo por requisição.
- Preservação byte a byte, SHA-256, tamanho, mídia, nome original sanitizado e URI privada.
- Limites de tamanho, extensão/mídia, timeout, armazenamento fora da pasta pública e idempotência por hash.
- Processamento assíncrono com estados `recebido`, `validando`, `quarentena`, `normalizando`, `concluído`, `concluído_com_pendências` e `falhou`.
- Detecção de encoding sem substituição silenciosa. Bytes incompatíveis permanecem preservados e geram pendência.
- Parser estrutural EFD baseado em delimitador, versão informada, linha e tipo de registro.
- Parser XML com entidades externas, DTD e rede desabilitados, além de limites de profundidade/tamanho.
- Extração inicial de cabeçalho, participante, unidade, produto, documento e item quando presentes.
- Visualização paginada de importações, registros, documentos, itens e problemas.
- Link seguro para visualizar a localização no original; download do original somente para usuário autorizado.
- Auditoria de upload, consulta, download e mudança de estado.
- Fixtures sintéticas e o conjunto de testes CT-06, CT-14, CT-15, CT-16 e CT-17 quando a amostra real estiver disponível localmente e autorizada.

### Explicitamente fora

- Custeio FIFO/média, reconstrução de Kardex e cálculo tributário.
- Conciliação econômica completa entre EFD e XML; o MVP apenas registra proveniências e conflitos candidatos.
- Aprovação de mapeamento de produto, conversões, saldo inicial e retificações operacionais.
- PVA, assinatura, transmissão, geração/retificação de SPED e laudo jurídico.
- Upload em lote real, apesar de o modelo já suportar vários arquivos por importação futura.

## Fluxo de telas

### 1. Contexto

Selecionar organização, empresa e estabelecimento. O backend deriva o escopo da sessão/autorização; nenhum `organization_id` enviado pelo cliente concede acesso.

### 2. Upload

Área de arrastar/selecionar arquivo com tipo, limite e aviso de que o original será preservado. Antes do envio, mostrar nome e tamanho; depois, mostrar hash quando calculado.

### 3. Acompanhamento

Lista de importações com status, progresso aproximado, período, tipo inferido, contagens e pendências. O estado `concluído` significa apenas ingestão concluída, não aptidão fiscal.

### 4. Diagnóstico

Resumo da fonte com:

- identidade técnica e hash;
- estabelecimento/período inferidos e confiança;
- codificação e versão do leiaute;
- contagem por registro;
- documentos e itens extraídos;
- problemas classificados como erro, aviso ou informação;
- limitações conhecidas, como ausência de C170 em saídas.

### 5. Exploração

Tabela paginada por documento/item e painel de detalhe com valores decimais como strings, campos originais e normalizados, referência de linha/caminho XML e link de evidência. Filtros mínimos: período, modelo, direção, participante, código do item, tipo de registro, severidade e status.

## Contrato HTTP do MVP

Prefixo `/api/v1`; autenticação e escopo organizacional são obrigatórios.

| Método | Rota | Resultado |
| --- | --- | --- |
| `GET` | `/contexts` | Empresas/estabelecimentos disponíveis ao usuário |
| `POST` | `/imports` | Recebe arquivo e retorna `202` com `import_id` |
| `GET` | `/imports` | Lista paginada por contexto/status/período |
| `GET` | `/imports/{id}` | Estado, progresso, resumo e limitações |
| `GET` | `/imports/{id}/records` | Registros paginados com referência de origem |
| `GET` | `/imports/{id}/documents` | Documentos extraídos e conflitos candidatos |
| `GET` | `/imports/{id}/documents/{documentId}` | Documento, itens e proveniências |
| `GET` | `/imports/{id}/issues` | Problemas paginados por severidade/status |
| `GET` | `/imports/{id}/source-link` | URL temporária autorizada ou localização segura |
| `GET` | `/health` | Liveness/readiness sem dados fiscais |

Comandos aceitam `Idempotency-Key`. Repetição com mesmo hash e contexto retorna a importação existente; o mesmo arquivo em outro contexto é rejeitado. Erros usam Problem Details com código estável e correlation ID.

## Modelo mínimo de dados

As tabelas devem incluir `organization_id` e chaves estrangeiras com verificação de escopo:

- `organizations`, `users`, `establishments`;
- `source_files`: hash único por organização, bytes imutáveis, metadados e URI privada;
- `import_batches` e `import_attempts`: estado, cursor, parser_version, timestamps e resumo;
- `source_records`: arquivo, linha/caminho, tipo, payload estruturado e referência segura;
- `fiscal_documents` e `fiscal_items`: campos normalizados, valores originais e confiança;
- `document_sources`: ligação documento↔fonte, sem sobrescrever divergências;
- `issues`: severidade, código, mensagem segura, localização, estado e resolução;
- `audit_events`: ator, ação, objeto, contexto e timestamp append-only.

Valores monetários e quantidades devem ser `NUMERIC` no banco e strings nos contratos. O payload original não deve ser usado como SQL, shell, template ou instrução executável.

## Decisões de parsing

- O arquivo original é persistido antes de qualquer interpretação.
- O parser EFD conta e referencia registros sem modificar bytes; normalização é derivada e versionada.
- O parser deve aceitar delimitadores e campos vazios, mas rejeitar estrutura impossível com diagnóstico localizado.
- A codificação é reportada como detectada, válida, incompatível ou desconhecida; nunca trocar caracteres inválidos silenciosamente.
- O XML deve usar parser seguro, sem entidades externas, DTD ou resolução de rede.
- Campos não reconhecidos são preservados como desconhecidos e não descartados.
- C100/C170 e equivalentes XML são dados de inspeção no MVP; nenhuma movimentação de estoque é criada.

## Critérios de aceite

- Upload válido retorna `202`, cria uma fonte com hash e pode ser acompanhado até um estado terminal.
- Reenvio do mesmo arquivo no mesmo contexto não cria segunda fonte nem segundo lote efetivo.
- Arquivo malformado, grande demais ou inseguro vai para rejeição/quarentena com motivo localizado.
- O original baixado por usuário autorizado tem o mesmo SHA-256 registrado.
- A tela de diagnóstico mostra contagens, período, versão, problemas e limitações sem afirmar conformidade.
- Um documento e seus itens podem ser rastreados até linha EFD ou caminho XML.
- Usuário de outra organização recebe `404`/`403` conforme a política, sem vazamento de existência, URL ou metadados.
- XML com entidade externa não faz requisição de rede nem lê arquivo local.
- A amostra fornecida pode ser identificada por SHA-256 `00369654419a2a0cd15b851760f10e2fbdeb52ae9b28b6165e02574356ac2cd4`; a tela registra 18.007 linhas, 5.979 C100 e 1.608 C170, além da pendência de codificação e da ausência de C170 nas saídas, sem normalizar o original.

## Plano de entrega

1. Fundação: esqueleto PHP/Symfony, autenticação, contexto, migrations, armazenamento e fila.
2. Ingestão segura: upload, hash, idempotência, limites, auditoria e estados.
3. Parser EFD: leitura streaming, registros, codificação, diagnóstico e fixtures.
4. Parser XML: NF-e 55, segurança XML, documentos/itens e fixtures.
5. API e telas: lista, progresso, diagnóstico, tabelas e detalhe com evidência.
6. Gate MVP: testes de autorização, segurança, idempotência, rastreabilidade e CT-06/14/15/16/17.

## Perguntas que não bloqueiam o primeiro protótipo

- O armazenamento de produção será Azure Blob, S3 compatível ou inicialmente filesystem privado?
- O login inicial será somente conta local ou haverá um provedor OIDC?
- Qual limite operacional de arquivo e retenção deve ser usado no ambiente de laboratório?

Essas escolhas podem ser adaptadas por configuração sem alterar o contrato de domínio. O MVP deve manter o `fiscal-factory` como ferramenta de geração de fixtures/laboratório, nunca como fonte de dados de produção.
