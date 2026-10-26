# 01 — Produto, escopo e requisitos

## Objetivo e viabilidade

Responder, de forma reproduzível: para uma saída de produto, quais entradas ou saldos iniciais fornecem seu custo conforme o método escolhido, e qual é o resultado de uma regra tributária documentada?

A viabilidade técnica é condicionada à disponibilidade de itens de saída, identidade dos produtos, conversões de unidade e estoque inicial custeado. A amostra disponível não contém itens de saída; portanto, ela sozinha não demonstra a viabilidade do vínculo completo. A viabilidade jurídica permanece pendente da decisão e da validação profissional.

O sistema não deverá criar compras fictícias, presumir alíquota de 18% ou considerar cinco anos como prazo recuperável universal. Não deverá classificar automaticamente diferenças de ICMS como indébito.

## Perfis

- Operador: importa arquivos e acompanha erros.
- Analista fiscal: concilia documentos, aprova mapeamentos e trata exceções.
- Revisor contábil: revisa custo, inventário e fechamento.
- Revisor jurídico: documenta e aprova a regra aplicável ao caso.
- Auditor: consulta execuções e evidências sem alterá-las.
- Administrador: gerencia usuários, empresas e permissões; não ganha aprovação fiscal implícita.

## Glossário

- Kardex: registro cronológico de entradas, saídas, quantidades e valores de estoque.
- FIFO/PEPS: consumo das camadas elegíveis mais antigas primeiro.
- Média móvel: custo médio recalculado a cada entrada valorizada elegível.
- Camada: quantidade e custo disponíveis de uma entrada ou saldo inicial.
- Alocação: parcela de uma saída custeada por uma camada.
- Produto canônico: identidade interna que reúne códigos comprovadamente equivalentes.
- Execução: processamento imutável de um conjunto de fontes com parâmetros e versões fixados.
- Cenário: configuração independente de método de custo ou regra tributária.
- Crédito escritural: crédito conforme regras de apuração; não equivale automaticamente a indébito.
- Valor potencialmente recuperável: resultado condicionado à elegibilidade e à regra jurídica aprovada.

## Requisitos funcionais

| ID | Requisito | Aceite principal |
| --- | --- | --- |
| RF-01 | Preservar e importar EFD e XML NF-e em lote | Original, hash, origem e erros recuperáveis registrados |
| RF-02 | Validar leiaute, estrutura e semântica | Erros classificados por severidade e referência de origem |
| RF-03 | Conciliar fontes e evitar duplicação econômica | EFD e XML da mesma operação geram uma movimentação |
| RF-04 | Versionar retificações e eventos | Cancelamento/retificação invalida cenários afetados sem apagar histórico |
| RF-05 | Mapear produtos e unidades com vigência | Correspondência não comprovada exige revisão |
| RF-06 | Reconstruir Kardex por estabelecimento e produto | Saldos e exceções reconciliáveis com as fontes |
| RF-07 | Valorar por FIFO | Cada saída apresenta camadas, quantidades e custos |
| RF-08 | Valorar por média móvel em cenário separado | Resultados não se misturam aos de FIFO |
| RF-09 | Aplicar regra tributária versionada | Regra não aprovada produz apenas simulação |
| RF-10 | Gerar memória de cálculo e pacote de evidências | Resultado navegável até origem e versão do algoritmo |
| RF-11 | Reprocessar histórico afetado | Nova execução preserva a anterior e identifica diferenças |
| RF-12 | Controlar acesso, revisão e fechamento | Alterações e aprovações têm autor e justificativa |

## Escopo incremental

MVP: EFD e NF-e modelo 55, documentos itemizados de compra e venda, identidade de produto, conversões, saldo inicial, Kardex FIFO, exceções e relatórios de custeio. Operações não suportadas devem ser explicitamente bloqueadas ou segregadas, nunca ignoradas.

Incremento seguinte: média móvel e tratamentos de devolução/transferência validados. Motor tributário: simulação inicialmente; uso revisado somente após regra e evidências aprovadas.

Fora do primeiro MVP: produção industrial e transformação, composição de kits sem ficha, NFC-e e outros modelos sem especificação própria, geração/retificação de SPED, transmissão ao fisco, peticionamento e recuperação automática.

## Requisitos não funcionais

- Nenhum valor monetário ou quantidade de domínio em ponto flutuante binário.
- Isolamento por organização e estabelecimento; acesso negado por padrão.
- Importações idempotentes e retomáveis, sem sucesso parcial oculto.
- Memória limitada por streaming; sem carregar todo o arquivo em memória.
- Meta inicial de benchmark: processar a amostra de 18.007 linhas em até 60 segundos, em 2 vCPU/4 GiB, excluídos upload e fila. Meta proposta, ainda não medida.
- Definir metas adicionais após medir volumes reais de cinco anos; não assumir capacidade comprovada.
- Todas as execuções devem registrar versões, parâmetros, cobertura e limitações.
