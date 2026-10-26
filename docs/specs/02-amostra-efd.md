# 02 — Diagnóstico preliminar da amostra EFD

## Evidência e método

Inspeção local em 26/10/2026 do arquivo fornecido pelo usuário. O original não está incluído no Git. Foram examinados bytes, delimitadores, registros e a associação hierárquica C100/C170. Não foi executado PVA, validação de assinatura, conferência completa de campos ou validação tributária.

- Tamanho: 1.445.662 bytes.
- SHA-256: `00369654419a2a0cd15b851760f10e2fbdeb52ae9b28b6165e02574356ac2cd4`.
- Registro 0000: COD_VER `020`, COD_FIN `0`, período declarado de 01/07/2026 a 31/07/2026.
- 18.007 linhas de registros; o 9999 declara 18.007 linhas. A coincidência não valida os demais totalizadores.
- Decodificação estrita como UTF-8 e Windows-1252 falhou. A codificação não foi determinada; pode haver bytes inválidos ou conteúdo misto. A contagem foi feita por delimitadores ASCII, preservando os bytes. Não usar substituição silenciosa de caracteres.

## Registros relevantes observados

| Registro | Quantidade | Uso potencial / limite |
| --- | ---: | --- |
| 0150 | 54 | Participantes; conteúdo não validado |
| 0190 | 32 | Unidades declaradas |
| 0200 | 994 | Cadastro de itens; não prova equivalência |
| 0220 | 627 | Conversões candidatas, sujeitas à validação |
| C100 | 5.979 | 168 entradas e 5.811 saídas por IND_OPER |
| C170 | 1.608 | Todos associados a documentos de entrada |
| C190 | 8.457 | Analíticos tributários; não substituem itens de saída |
| C110 | 168 | Informações complementares |
| C113 | 1 | Documento referenciado |
| C191 | 14 | Informação complementar de apuração, conforme leiaute aplicável |
| E110 | 1 | Apuração; não comprova composição por produto |
| H001 / H990 | 1 cada | Abertura/encerramento sem H005/H010 |
| K001 / K990 | 1 cada | Abertura/encerramento sem K200 |

Há C170 em 167 dos 168 C100 de entrada e em zero dos 5.811 C100 de saída. A ausência no documento restante exige classificação antes de ser chamada de erro. A falta de C170 nas saídas também pode decorrer das regras de escrituração aplicáveis; não é prova isolada de irregularidade fiscal.

## Consequências para o projeto

1. Não é possível construir o vínculo completo compra → venda por produto usando somente esta amostra.
2. Solicitar XMLs de saída ou exportação itemizada reconciliável do ERP; se houver operações de outros modelos, especificar sua fonte própria.
3. Validar se entradas têm todos os campos de quantidade e custo necessários; presença de C170 não comprova completude.
4. Não há registros de saldo de inventário H010/K200 nesta amostra; obter saldo inicial valorizado e histórico anterior suficiente.
5. Inspecionar e resolver codificação sem alterar o original. Derivações normalizadas precisam registrar transformação e hash próprios.
6. Obter o histórico completo e suas retificações; um mês não comprova cobertura de cinco anos.

## Próxima validação documental

Conferir guia oficial e tabelas correspondentes ao leiaute 020, totalizadores por bloco/registro, chaves, situações dos documentos, datas, unidades, valores, elegibilidade e divergências com XMLs. Referência oficial a consultar: portal SPED / EFD ICMS IPI, incluindo Guia Prático e tabelas de leiaute em https://sped.rfb.gov.br/. Nenhuma conformidade com o guia foi certificada nesta etapa.

Não publicar CNPJ, participantes, chaves NF-e ou itens reais nos exemplos e testes do repositório. Usar fixtures sintéticas.
