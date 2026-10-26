# 05 — Contratos, segurança e operação

## Interface e navegação

Fluxo: selecionar empresa/estabelecimento → importar fontes → verificar cobertura e inconsistências → revisar produtos/unidades → configurar saldo e política → executar Kardex → revisar custos → executar cenário tributário → aprovar e exportar.

Cada tela de resultado deve mostrar método, período, execução, cobertura e pendências. Um relatório consolidado deve permitir abrir a venda, suas alocações e as fontes correspondentes. Mostrar custo conhecido e quantidade não custeada separadamente.

## Contratos HTTP propostos

Prefixo `/api/v1`; autenticação obrigatória e escopo organizacional determinado pelo usuário autorizado, nunca apenas por um identificador enviado no corpo.

| Método e rota | Comportamento |
| --- | --- |
| POST /imports | Upload autorizado; retorna 202 e import_id |
| GET /imports/{id} | Estado, progresso, contagens e problemas |
| GET /issues | Lista paginada por fonte, severidade e situação |
| POST /product-mappings | Propõe vínculo com evidência e vigência |
| POST /product-mappings/{id}/approvals | Aprova/rejeita com justificativa |
| POST /cost-runs | Fixa fontes, janela, método e política; retorna 202 |
| GET /cost-runs/{id} | Estado e resumo de cobertura |
| GET /cost-runs/{id}/kardex | Movimentos paginados e saldos |
| GET /cost-runs/{id}/allocations | Vínculos e custos por saída |
| POST /tax-runs | Fixa cost_run_id e rule_version_id; retorna 202 |
| POST /reports | Exportação assíncrona vinculada a execução |
| GET /reports/{id} | Metadados e download temporário autorizado |

Comandos assíncronos aceitam Idempotency-Key, ligada ao hash da requisição e organização; repetição retorna o mesmo recurso e corpo diferente produz 409. Valores decimais trafegam como strings. Erros seguem Problem Details com código estável e referência de correlação, sem conteúdo fiscal sensível.

Estados de importação: recebido, validando, quarentena, normalizando, conciliando, concluído, concluído_com_pendências, falhou. Conclusão da importação não significa período apto ao cálculo.

## Relatórios

- Cobertura por período, estabelecimento e modelo; documentos e itens ausentes.
- Divergências EFD/XML e motivos de conciliação pendente.
- Mapeamentos e conversões aprovados/não aprovados.
- Kardex por produto, com saldo quantitativo e valor.
- Camadas e alocações FIFO por venda.
- Comparação de cenários FIFO/média sem misturar resultados.
- Memória tributária por item: base, alíquota, regra, custo, imposto original/recalculado e diferença.
- Consolidação por competência com valores excluídos e pendentes destacados.

Exportar CSV UTF-8 para conferência e PDF para leitura; incluir manifesto JSON versionado para reprodução. CSV deve neutralizar fórmulas de planilha sem alterar a representação preservada na fonte. PDF não é laudo profissional automático.

Pacote de evidências inclui hashes das fontes, identificadores de execução, seleção de dados, parâmetros, versões, aprovações, exceções e hashes dos relatórios. Originais só entram em pacote autorizado, com acesso e tratamento fiscal adequados.

## Segurança e privacidade

- Upload com limites de tamanho, tipo e expansão; nomes de armazenamento gerados pelo sistema.
- Parser XML com entidades externas e resolução de rede desabilitadas; limites contra expansão e profundidade excessiva.
- Arquivos privados, criptografia em trânsito/repouso e URLs curtas temporárias.
- Texto importado nunca é instrução executável; descrições e fórmulas não geram SQL ou shell.
- RBAC, trilha de acesso/exportação e segregação de funções nas aprovações.
- Retenção, descarte e base legal definidos pelo controlador com responsáveis; considerar LGPD e deveres fiscais sem fixar prazo universal.
- Logs com IDs técnicos, métricas e códigos de erro; não registrar XML completo, chaves ou credenciais.
- Backups e restauração testados; metas RPO/RTO serão definidas com o negócio.

## Operação e desenvolvimento futuro

Dependências previstas: PHP 8.4 com extensões XML, mbstring, pdo_pgsql e decimal/bcmath conforme biblioteca escolhida; Composer; PostgreSQL; worker de fila; armazenamento privado. Fixar versões e lockfiles na implementação.

Cada tarefa cloud já é isolada; usar checkout existente, sem criar worktree salvo pedido explícito. Não há serviços implementados para iniciar nesta etapa. Nenhuma credencial ou arquivo fiscal deve ser incluído em scripts versionados.

Métricas: tempo por fase, taxa de falhas, itens sem identidade, quantidade descoberta, cobertura documental e filas atrasadas. Health checks de infraestrutura não substituem teste funcional de importação/custeio.
