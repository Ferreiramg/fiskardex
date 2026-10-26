# Fiskardex

Especificações de um sistema de reconstrução de estoque e custos para subsidiar estudos de ICMS, auditorias e processos administrativos ou judiciais.

**Estado:** especificação inicial para revisão técnica, contábil e jurídica. Não há implementação nem cálculo de crédito validado.

## Documentação

1. [Produto, escopo e requisitos](docs/specs/01-produto.md)
2. [Diagnóstico da amostra fiscal](docs/specs/02-amostra-efd.md)
3. [Arquitetura e modelo de dados](docs/specs/03-arquitetura-dados.md)
4. [Kardex, custos e regras tributárias](docs/specs/04-regras-dominio.md)
5. [Contratos, segurança e operação](docs/specs/05-contratos-operacao.md)
6. [Aceite, testes, roadmap e decisões pendentes](docs/specs/06-validacao-roadmap.md)

## Diretrizes

- PHP 8.4 e orientação a objetos; monólito modular como arquitetura inicial.
- FIFO/PEPS como primeira estratégia; média ponderada móvel como cenário independente.
- Rastreabilidade até documentos originais e execuções versionadas.
- Estoque calculado não comprova origem física; simulação tributária não comprova direito a crédito.
- Arquivos fiscais, dados pessoais, credenciais e relatórios com dados reais não devem ser versionados neste repositório.

## Desenvolvimento

A seleção do framework e a implementação serão tarefas posteriores. Não há ainda comandos de instalação, servidor ou suíte executável. Os cenários de aceite estão documentados, não executados.
