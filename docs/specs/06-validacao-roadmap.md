# 06 — Validação, roadmap e decisões

## Critérios de prontidão

Uma venda está integralmente custeada quando sua identidade/unidade está aprovada, a quantidade total está alocada em fontes elegíveis, a política de custo está definida e não há pendência impeditiva.

Um período está conciliado quando há cobertura declarada das fontes necessárias, documentos deduplicados, situações verificadas, movimentos reconciliados e divergências resolvidas ou formalmente segregadas. Percentual agregado nunca encobre vendas não custeadas.

Um cálculo pode ser submetido à revisão quando regra e escopo estão fixados, fontes/custos estão rastreáveis e exclusões/limitações aparecem no resultado. Emitir relatório aprovado exige aprovadores habilitados; isso não reconhece juridicamente o direito.

## Cenários de aceite a implementar

| ID | Dado / ação | Resultado esperado |
| --- | --- | --- |
| CT-01 | Entradas 10×10 e 5×14; saída 12 | FIFO custa 128; saldo 3 unidades/42 |
| CT-02 | Após CT-01, saída 1 | Custo 14; saldo 2/28 |
| CT-03 | Mesmo conjunto por média móvel | Custo da saída 136; saldo 3/34 |
| CT-04 | Entrada 2 caixas, fator 12 un/caixa, custo total 240; saída 5 un | Entrada 24 un; custo da saída 50; saldo 19/190 |
| CT-05 | Saldo 3×10; saída 5 | Custo conhecido 30; 2 unidades descobertas; tributação final bloqueada |
| CT-06 | Reimportar mesmo arquivo e NF-e presente na EFD | Uma fonte por hash e uma movimentação econômica; múltiplas proveniências |
| CT-07 | Cancelamento comprovado após cálculo | Nova versão exclui efeito aplicável; execução anterior preservada e desatualizada |
| CT-08 | Retificadora substitui original no período | Não somar escriturações; gerar nova execução com diferenças |
| CT-09 | Venda 3×10 devolve 2 unidades comprovadas | Reentrada 2/20 com vínculo à saída; não duplicar devolução |
| CT-10 | Compra 10×10 devolve 2 ainda disponíveis | Camada remanescente 8/80 |
| CT-11 | Devolução de compra já consumida | Pendência explícita; nenhum saldo negativo silencioso |
| CT-12 | Entrada retroativa elegível anterior à venda | Reprocessar alocações e saldos; manter resultado anterior |
| CT-13 | Rateio de 0,01 entre três itens | Soma dos valores rateados igual a 0,01; resíduo determinístico |
| CT-14 | XML/EFD com quantidade divergente | Conflito visível, sem sobrescrita nem dupla movimentação |
| CT-15 | Arquivo de outra organização / acesso cruzado | Rejeição; nenhum dado ou URL privado exposto |
| CT-16 | XML com entidade externa ou expansão abusiva | Rejeição sem requisição de rede e sem leitura local |
| CT-17 | Amostra real identificada pelo hash | 18.007 registros, 5.979 C100, 1.608 C170; saída sem itens bloqueia vínculo completo |
| CT-18 | Venda 200, custo 128, hipótese 18%, original 36 | Imposto hipotético 12,96; diferença 23,04; ambos rotulados como simulação |

CT-17 exige disponibilização local autorizada da amostra, sem incluí-la no Git. Problema de codificação deve ser registrado; contagem ASCII não significa normalização válida.

## Invariantes

- Quantidade inicial + entradas − saídas = quantidade final, considerando reversões classificadas.
- Valor inicial + entradas valorizadas − custos/ajustes = valor final.
- Soma das alocações não excede a saída nem a camada disponível.
- Mesmas fontes, regras, precisão e ordenação produzem o mesmo resultado de domínio; IDs e horários operacionais podem variar.
- Execução com fonte ou regra alterada não herda aprovação automaticamente.
- Reentrega de jobs não cria duplicação; falha transacional não publica resultado parcial.

Testes unitários para cálculos, integração para importação/transações, propriedades para conservação, testes de autorização e E2E para importar → conciliar → custear → relatar. Fixtures sintéticas pequenas com resultado independente calculado manualmente.

## Roadmap com gates

1. **Descoberta documental:** obter itens de saída, saldo inicial, histórico e decisão. Gate: cobertura mínima demonstrada e pendências classificadas.
2. **Fundação:** projeto PHP 8.4, banco, autorização, armazenamento e CI. Gate: isolamento e upload seguro testados.
3. **Importação:** EFD/XML, versões e conciliação. Gate: sem duplicação e origem acessível por item.
4. **Identidade e FIFO:** mapeamentos, conversões, abertura, Kardex e exceções. Gate: CT-01/02/04/05 e invariantes aprovados.
5. **Auditoria do custeio:** relatórios, manifesto e reprocessamento. Gate: amostra de vendas revisada pelo contador.
6. **Ampliação:** média, devoluções e transferências. Gate: casos correspondentes e políticas aprovadas antes de habilitar essas operações.
7. **Tributação:** estratégia validada pelo jurídico/contador, simulações e aprovações. Gate: memória independente confere com cálculos aprovados.
8. **Histórico completo:** benchmark, restauração e homologação. Gate: cobertura e divergências formalmente aceitas; não prometer recuperação financeira.

Não estimar prazo fechado sem volumes, equipe, cobertura e complexidade jurídica. A ausência de itens de saída impede concluir o gate 1 para vínculo integral, mas não impede desenvolver a base de importação.

## Perguntas prioritárias

### Advogado

- Qual é o inteiro teor da decisão, seu alcance subjetivo/territorial e situação processual?
- Qual tributo, operação, fórmula, base, período, exclusão e atualização foram efetivamente definidos?
- Como tratar resultados negativos, créditos já apropriados e risco de recuperação em duplicidade?
- Há exigência de identificação física, ou a apropriação contábil por método aprovado atende à prova requerida?

### Contador

- Qual regime por estabelecimento/período, e qual composição de custo é aplicável?
- Qual método e política para devoluções, transferências, fretes e tributos?
- Existem inventários valorizados e histórico para reconstruir a abertura?
- Como reconciliar estoque contábil, físico e fiscal, inclusive perdas e ajustes?

### Responsável pelos dados

- Estão disponíveis XMLs e eventos de todas as saídas e entradas? Há NFC-e ou outros modelos?
- Quais períodos, retificações, sistemas de origem, volumes e códigos históricos existem?
- Há conversões comprovadas e relação de códigos fornecedor ↔ código interno?
- Qual a origem dos bytes incompatíveis com UTF-8/Windows-1252 na amostra?

## Estado desta entrega

Especificações redigidas e amostra inspecionada estruturalmente. Nenhum algoritmo de produção, importador, teste de aplicação, validação PVA ou cálculo fiscal real foi executado. Decisões de framework, escalas numéricas e metas são propostas sujeitas aos gates acima.
