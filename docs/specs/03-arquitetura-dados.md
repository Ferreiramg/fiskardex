# 03 — Arquitetura e dados

## Decisões propostas

- PHP 8.4, Composer e PSR-4, com domínio orientado a objetos.
- Monólito modular: reduz coordenação operacional e permite transações entre estoque e alocações. Microsserviços não são necessários inicialmente.
- Laravel com versão compatível com PHP 8.4 é recomendação para HTTP, filas e autenticação; fixar versão e lockfile na implementação. Regras contábeis não dependem do framework.
- PostgreSQL para integridade, DECIMAL e consultas de auditoria; Redis opcional para filas, não como fonte de verdade.
- Armazenamento de objetos compatível com S3 para originais e relatórios; adaptador local no desenvolvimento.
- Objetos de valor Money, Quantity, UnitCost, ProductId e FiscalPeriod; estratégias CostingStrategy e TaxRule; serviços de aplicação coordenam transações.

## Módulos e limites

Ingestão preserva bytes e metadados. Normalização interpreta versões e unidades. Conciliação resolve fontes e identidade. Estoque cria movimentos e saldos. Custeio gera camadas e alocações. Tributação consome custos e documentos sem alterar estoque. Auditoria mantém eventos e evidências. Identidade controla organizações e permissões.

Estrutura sugerida: `src/Modules/{Modulo}/{Domain,Application,Infrastructure,Presentation}`. Evitar entidades de domínio acopladas ao ORM e um serviço único para importação, estoque e cálculo fiscal.

## Entidades e cardinalidades

Todas as entidades de negócio incluem organization_id; referências devem impedir associações entre organizações.

| Entidade | Campos e relações principais |
| --- | --- |
| Establishment | organização, identificação fiscal protegida, UF e vigências |
| SourceFile | organização, hash SHA-256, tamanho, mídia, URI privada, upload, bytes imutáveis |
| ImportBatch / ImportAttempt | fonte, estado, parser_version, cursor, tentativas e resumo |
| SourceRecord | arquivo, número de linha ou caminho XML, tipo, versão e referência segura ao original |
| FiscalDatasetVersion | estabelecimento, período, finalidade, versão ativa e substituída, aprovação |
| FiscalDocument | estabelecimento, modelo, chave quando existente, direção, datas, situação |
| FiscalDocumentSource | documento canônico ↔ registros EFD/XML; preserva valores divergentes |
| FiscalItem | documento, número do item, código original, quantidade, unidade e valores originais |
| Product / ProductMapping | produto canônico, código contextual, fornecedor, vigência, estado e aprovação |
| UnitConversion | produto, unidade origem/destino, fator decimal/racional, vigência e evidência |
| Movement | estabelecimento, produto, item de origem, natureza, data, quantidade normalizada e titularidade |
| OpeningBalance | data, quantidade, custo, camadas quando conhecidas, documento e limitações |
| CostPolicyVersion | componentes de custo, rateios, precisão e regras de arredondamento |
| CostRun / CostRunInput | método, janela, hash dos insumos ordenados, política, versão de código e fontes selecionadas |
| CostLayer | execução, entrada/saldo, quantidade inicial, custo e saldo derivado |
| CostAllocation | execução, saída, camada, quantidade e custo apropriado |
| TaxScenario / TaxRuleVersion | escopo, fórmula identificada, vigência, documentos jurídicos e aprovações |
| TaxRun / TaxResult | execução de custo fixada, regra fixada, item, bases, imposto original e recalculado, diferença |
| Issue / Approval / AuditEvent | objeto, severidade, autor, justificativa, evidência e carimbo temporal |
| ReportArtifact | execução, filtros, versão do gerador, hash, URI e manifesto |

## Integridade e índices

- Unique (organization_id, sha256) em SourceFile; reenvio referencia a fonte existente.
- Documento com chave: identidade fiscal validada e escopo explícito. Sem chave: identidade composta contextual com colisões encaminhadas à revisão.
- Unique (document_id, item_number) no item canônico; múltiplas fontes não duplicam itens.
- Mapeamentos de produto e conversões não podem ter vigências conflitantes para a mesma chave contextual.
- Índices de movimentos: (organization_id, establishment_id, product_id, effective_at, stable_order).
- Índices de alocações por (run_id, outbound_movement_id) e (run_id, layer_id).
- Quantidades de alocação positivas; soma não ultrapassa camada nem saída. Restrições agregadas são verificadas em transação e por auditoria de invariantes.
- FK e políticas de acesso por organização; RLS como defesa adicional, não substituto da autorização da aplicação.

## Precisão e imutabilidade

Proposta inicial: NUMERIC(24,8) para quantidades e custos unitários; NUMERIC(24,8) para valores intermediários; valores apresentados em reais com duas casas conforme política fiscal. Validar limites, escalas e resíduos com exemplos reais antes de fixar schema.

Usar biblioteca decimal de precisão arbitrária; strings decimais nos contratos. Arredondamento explicitamente configurado por componente e regra; nunca arredondar todas as etapas indiscriminadamente.

Execuções concluídas são imutáveis. Uma mudança produz nova execução com referência à anterior; não reescreve o passado. Guardar versão do código, políticas, fontes, ordenação e digest dos insumos. Eventos de auditoria são append-only; hash isolado não substitui controle de acesso, retenção ou assinatura quando exigida.

## Transações e concorrência

Fila entrega pelo menos uma vez; cada job usa chave idempotente. Um único processamento ativo por partição organização/estabelecimento/produto/cenário, com locks e retomada segura. Publicar resultados completos atomicamente; resultados em construção não podem ser usados em relatório final. Outbox transacional coordena eventos e jobs posteriores.
