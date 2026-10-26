# 04 — Regras de domínio

## Importação e prevalência das fontes

Preservar o arquivo antes de interpretar. Validar versão suportada e identificar organização/estabelecimento autorizado. Importação duplicada não duplica movimentos. Documento cancelado não gera movimento ativo sem análise de efeitos já escriturados; divergência entre evento e escrituração gera pendência.

XML autorizado documenta os itens da NF-e; EFD documenta a escrituração e seus ajustes. Nenhuma fonte sobrescreve a outra silenciosamente. Chave, item, quantidade, unidade e valores são conciliados; diferenças ficam registradas e podem bloquear custeio/tributação. Situação e autorização do XML precisam de verificação compatível com as evidências disponíveis.

Retificação seleciona nova versão de escrituração para estabelecimento/período, mediante conferência; não soma original e retificadora. XMLs vinculados permanecem fontes independentes. Resultados afetados ficam desatualizados e exigem nova execução.

## Identidade e natureza

Descrição, NCM e GTIN isolados não garantem equivalência. Mapeamentos por código/contexto/vigência e conversões exigem evidência. Sugestões probabilísticas nunca movimentam estoque automaticamente sem política aprovada.

CFOP auxilia, mas não define sozinho compra, venda, propriedade e efeito no estoque. Remessas, consignação, bonificações, perdas e ajustes exigem classificação explícita. Transformação e kits sem composição ficam bloqueados no MVP.

## Ordenação e saldo inicial

Particionar por organização, estabelecimento, produto, proprietário e local de estoque quando necessário. Usar data efetiva documentada; horário só quando confiável. Empates seguem regra estável versionada (sequência de origem e identificador); não presumir entrada antes de saída para esconder saldo negativo. Se a ordem modificar materialmente o custo e não houver evidência suficiente, emitir pendência.

Obter saldo inicial valorizado e histórico anterior suficiente para custos. Um saldo agregado pode virar camada de abertura claramente identificada, mas não autoriza inventar as notas que o originaram. Para rastrear compras específicas do saldo inicial, é necessário reconstruir suas camadas anteriores.

## Composição de custo

Política versionada define valor do item, descontos, rateios de frete/seguro/despesas e tratamento dos tributos recuperáveis/não recuperáveis conforme regime e operação. Evitar duplicidade de despesas já incluídas. Rateios usam base justificada (valor, peso ou quantidade) e conservação do total; resíduos têm destino determinístico documentado.

## Algoritmo FIFO

1. Carregar movimentos elegíveis e saldo inicial fixados para a execução.
2. Entrada valorizada cria camada com origem, quantidade e custo total.
3. Saída consome camadas anteriores elegíveis em ordem FIFO.
4. Para cada parcela, registrar quantidade, custo unitário, custo apropriado e camada.
5. Se faltar estoque, marcar quantidade descoberta. Não usar entrada futura ou custo zero fictício.
6. Separar custo conhecido e não apurado. Saída parcial não é apresentada como integralmente custeada; tributação definitiva fica bloqueada.
7. Conferir invariantes antes de publicar a execução.

Exemplo sintético: compra de 10 unidades a R$ 10 e outra de 5 a R$ 14; venda de 12 unidades. Alocações: 10 × 10 + 2 × 14 = R$ 128. Restam 3 unidades a R$ 14, total R$ 42. Custo inicial R$ 170 = saída R$ 128 + saldo R$ 42.

Devolução de venda referenciada reverte as alocações originais na quantidade comprovada, respeitando os custos originais. A política deve definir se a camada retornada participa da fila na data da devolução ou recupera antiguidade; essa decisão afeta FIFO e exige aprovação explícita. No MVP, adotar data da devolução e manter referência à camada original. Devolução sem vínculo vai para revisão, sem compra presumida.

Devolução de compra reduz camada identificada ainda disponível. Se já consumida, gerar pendência e definir recálculo/ajuste aprovado; não criar quantidade negativa silenciosa. Transferência preserva custo e proveniência entre estabelecimentos; obrigações fiscais da transferência são tratadas separadamente.

## Média ponderada móvel

Após entrada elegível: novo custo médio = (valor anterior + custo da entrada) / (quantidade anterior + quantidade da entrada). Saída usa o custo médio vigente e reduz quantidade/valor. Se estoque zerar, reconciliar resíduo segundo política; denominador nulo/negativo bloqueia o cálculo.

No mesmo exemplo FIFO, média = 170/15; venda de 12 tem custo R$ 136 e saldo de 3 tem valor R$ 34, após política de precisão. Esse método atribui custo agregado e não identifica uma nota de compra específica para cada venda. Não apresentar alocação FIFO como prova de custo médio.

## Reprocessamento

Entrada retroativa, retificação, cancelamento, conversão ou mapeamento alterado invalida execuções dependentes a partir da primeira data afetada. Reprocessar até o fim da janela ou ponto de equivalência demonstrado. Comparação entre execuções deve mostrar saldos, alocações e resultados alterados.

## Motor tributário

Cenário exige UF, regime, produtos/operações, período, documentos jurídicos, vigências, elegibilidade, alíquotas, composição de bases, ajustes, arredondamento e aprovadores. Não executar código arbitrário fornecido em fórmulas; usar estratégias revisadas ou linguagem restrita validada.

`(venda - custo) × alíquota` calcula imposto hipotético sobre uma diferença. Não define automaticamente recuperação. Se uma decisão determinar imposto recalculado sobre tal base, o candidato a diferença poderá ser `imposto original considerado - imposto recalculado`, sujeito a exclusões e critérios do título.

Exemplo apenas algébrico: venda R$ 200, custo R$ 128 e alíquota hipotética 18%. A primeira expressão produz R$ 12,96. Se o imposto original considerado fosse R$ 36, a diferença seria R$ 23,04. Nenhum desses valores é crédito reconhecido sem regra aplicável e evidências.

Não truncar resultados negativos, compensar períodos, aplicar juros ou atualizar valores sem regra explícita. Guardar valores brutos e tratamento aprovado. Crédito de entrada, ICMS-ST, DIFAL, FCP, benefícios, isenções e operações interestaduais demandam avaliação própria; pendências não podem ser ocultadas em alíquota fixa.

Estados: simulação → pendente de evidências → revisado tecnicamente → aprovado para emissão de relatório. Aprovação do relatório não equivale a reconhecimento do crédito pelo Estado.
