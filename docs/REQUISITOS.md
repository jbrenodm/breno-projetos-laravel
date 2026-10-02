# breno-projetos-laravel — Requisitos e Arquitetura (fonte da verdade)

> Este documento é a **única fonte da verdade** do projeto. Qualquer mudança de regra de negócio,
> campo, status ou decisão de arquitetura deve ser registrada aqui **antes** de virar código.
> Se o código e este documento divergirem, o documento vence (ou é atualizado conscientemente).
>
> Origem: análise de requisitos feita entre jun–jul/2026 e consolidada em 02/10/2026.

---

## 1. Objetivo do sistema

Ferramenta de gestão de **Projetos** comerciais/de soluções (antes chamados de "Oportunidades"),
com acompanhamento das **Atividades** executadas por Account Managers (AM) e Analistas de Pré-vendas (PV).

## 2. Linguagem ubíqua

| Termo | Significado |
|---|---|
| **Projeto** | Agregado raiz. Antiga "Oportunidade". Tem identificador interno (UUID) e, opcionalmente, um Código de Oportunidade. |
| **Código de Oportunidade** | Metadado **opcional** vindo do comercial/CRM (ex.: `OPP-2026-0001`). Diferente do ID interno. |
| **Atividade** | Linha de trabalho dentro de um Projeto. Várias podem estar abertas ao mesmo tempo, sem dependência entre si. |
| **Cliente** | Empresa/entidade que originou o Projeto. |
| **Fornecedor** | Parceiro/fabricante envolvido no Projeto. |
| **Solução** | Produto/serviço do catálogo de um Fornecedor. |
| **AM** | Account Manager (usuário com papel `account_manager`). |
| **PV** | Analista de Pré-vendas (usuário com papel `pre_vendas`). |

## 3. Bounded Contexts

| Contexto | Tipo | Pasta | Conteúdo |
|---|---|---|---|
| **Projetos** | Core | `src/Projetos` | Projeto, Atividade, vínculos com fornecedores/soluções |
| **Parceiros** | Apoio | `src/Parceiros` | Cliente, Fornecedor, Solução |
| **Identidade (IAM)** | Genérico | `src/Identidade` | Usuário, Papéis (roles) |

O contexto Projetos conhece os outros **apenas por ID** (ClienteId, FornecedorId, SolucaoId, UsuarioId).

## 4. Regras de negócio (invariantes)

### 4.1 Projeto
- **RN-01** Todo Projeto tem um ID interno (UUID) gerado pelo sistema, imutável.
- **RN-02** Código de Oportunidade é **opcional** e pode ser informado/alterado depois.
- **RN-03** Todo Projeto pertence a **exatamente 1 Cliente**; um Cliente pode ter vários Projetos.
- **RN-04** Todo Projeto tem **no mínimo 1 Fornecedor**. Vincular uma Solução é **opcional**.
  Um Projeto pode ter vários fornecedores e várias soluções (ex.: licença do Fornecedor A + serviço do Fornecedor B).
- **RN-05** A Solução vinculada deve pertencer ao Fornecedor do mesmo vínculo.
- **RN-06** Só é possível abrir Projeto para Cliente **ativo** e Fornecedores **ativos**.
- **RN-07** O Projeto **nasce sem atividades e sem AM/PV** na raiz, com status `Não Iniciado`.

### 4.2 Status do Projeto (macro-status)
Valores: `Não Iniciado`, `Em Andamento`, `Parado`, `Concluído`, `Cancelado`.

Automação (decidida na fase de desenvolvimento do backend):
- **RN-08** Sem atividades → `Não Iniciado`.
- **RN-09** Todas as atividades `Concluída` → `Concluído`.
- **RN-10** Caso contrário (há ao menos uma atividade não concluída) → `Em Andamento`.
- **RN-11** `Cancelado` é definido manualmente. Projeto cancelado não aceita novas atividades nem mudanças de status de atividades.

### 4.3 Atividade
- **RN-12** Um Projeto pode ter **várias atividades abertas ao mesmo tempo**; uma não depende da outra.
- **RN-13** Toda atividade exige **1 AM e 1 PV**.
- **RN-14 (pré-preenchimento / fallback temporal)** Ao criar uma atividade, o formulário vem pré-preenchido com o AM e o PV
  da **última atividade criada** no projeto (ordem cronológica, aberta ou concluída), com opção de alteração.
  Se não houver atividade anterior, o usuário **deve** informar AM e PV. O domínio aplica a mesma regra
  se AM/PV chegarem vazios (proteção contra requisição incompleta).
- **RN-15** Status da atividade: `Não Iniciada`, `Em Andamento`, `Parada`, `Concluída`.
- **RN-16** Transições permitidas:

  | De \ Para | Não Iniciada | Em Andamento | Parada | Concluída |
  |---|---|---|---|---|
  | Não Iniciada | — | ✅ | ✅ | ✅ |
  | Em Andamento | ❌ | — | ✅ | ✅ |
  | Parada | ❌ | ✅ | — | ✅ |
  | Concluída | ❌ | ❌ | ❌ | — (final) |

- **RN-17** É possível **registrar uma atividade já concluída** (ou já em andamento), para fins de histórico.
- **RN-18 Datas da atividade:**
  - `data_entrada` — obrigatória (padrão: hoje).
  - `data_limite` — obrigatória; não pode ser anterior à `data_entrada`. (Nome em português: **não usar "deadline"**.)
  - `data_inicio` — opcional; preenchida automaticamente ao passar para `Em Andamento` se estiver vazia; pode ser informada manualmente (histórico). Não pode ser anterior à `data_entrada`.
  - `data_termino` — preenchida **somente** quando `Concluída` (informada ou, se vazia, a data atual). Não pode ser anterior à `data_entrada` nem à `data_inicio`.
- **RN-19** `tipo` da atividade: `Mapeamento`, `Homologação`, `Implantação`, `Comercial` (obrigatório).
- **RN-20** Observação da atividade: texto opcional, sanitizado, com **autor**. Só o autor pode editá-la
  (regra ativa quando o login estiver implementado — ver Roadmap).

### 4.4 Cliente e Fornecedor
- **RN-21** Cliente e Fornecedor: `razao_social` obrigatória; `nome_fantasia` e `cnpj` **opcionais**.
- **RN-22** CNPJ, se informado, deve ter 14 dígitos válidos e ser **único** (vários registros sem CNPJ são permitidos).
- **RN-23** Fornecedor tem um catálogo opcional de Soluções; nome de solução não se repete dentro do mesmo fornecedor.
- **RN-24** Cliente/Fornecedor/Solução inativos não aparecem para novos projetos, mas o histórico é preservado.

### 4.5 Usuários e permissões
- **RN-25** AM e PV **não são tabelas próprias**: são usuários com papéis (`account_manager`, `pre_vendas`, `admin_geral`).
- **RN-26** Inicialmente todos os usuários podem alterar status. No futuro um Admin Geral distribuirá permissões (RBAC).
- **RN-27 (BOLA)** A autorização acontece no **Caso de Uso** (não só em rotas/middleware), usando o ID do usuário autenticado.

## 5. Modelo de domínio

```
[Agregado: Projeto]  (src/Projetos/Domain)
├── ProjetoId (VO, UUID)
├── ClienteId (string UUID)
├── CodigoOportunidade (VO, opcional)
├── StatusProjeto (enum)
├── VinculoFornecedor[] (VO: fornecedorId, solucaoId?)  — mínimo 1
└── Atividade[] (entidade interna — só é alterada através do Projeto)
    ├── id, descricao, TipoAtividade (enum), StatusAtividade (enum)
    ├── PeriodoAtividade (VO: dataEntrada, dataLimite, dataInicio?, dataTermino?)
    ├── accountManagerId, preVendasId
    └── Observacao (VO: texto, autorId?) — opcional
```

## 6. Banco de dados (PostgreSQL)

**clientes**: `id uuid pk`, `razao_social varchar`, `nome_fantasia varchar null`, `cnpj varchar(14) null unique`, `ativo bool default true`, timestamps.

**fornecedores**: mesmos campos de clientes.

**solucoes**: `id uuid pk`, `fornecedor_id uuid fk→fornecedores cascade`, `nome varchar`, `descricao text null`, `ativo bool default true`, timestamps. Único: (`fornecedor_id`, `nome`).

**users**: `id uuid pk`, `name`, `email unique`, `password`, `ativo bool`, timestamps (+ campos padrão do Laravel).

**roles**: `id uuid pk`, `nome unique` (`account_manager`, `pre_vendas`, `admin_geral`), `descricao`.

**role_user**: `user_id uuid fk`, `role_id uuid fk`, pk composta.

**projetos**: `id uuid pk`, `cliente_id uuid fk→clientes`, `codigo_oportunidade varchar null`, `status varchar`, timestamps.

**projeto_fornecedores**: `id bigint pk`, `projeto_id uuid fk cascade`, `fornecedor_id uuid fk`, `solucao_id uuid null fk`, timestamps.

**atividades**: `id uuid pk`, `projeto_id uuid fk cascade`, `descricao text`, `tipo varchar`, `status varchar`,
`data_entrada date`, `data_limite date`, `data_inicio date null`, `data_termino date null`,
`account_manager_id uuid fk→users`, `pre_vendas_id uuid fk→users`, `observacao text null`, `observacao_autor_id uuid null fk→users`,
`sequencia int` (ordem cronológica de criação dentro do projeto — usada no fallback de AM/PV), timestamps.

> Campos que **não** existem e não devem ser criados sem decisão registrada aqui: `titulo`, `ordem`, `nome`, `deadline`, status `Pendente`.

## 7. Casos de uso

| Caso de uso | Contexto | Descrição |
|---|---|---|
| `CadastrarCliente` | Parceiros | RN-21/22 |
| `CadastrarFornecedor` (com soluções) | Parceiros | RN-21/22/23 |
| `AdicionarSolucao` | Parceiros | RN-23 |
| `RegistrarNovoProjeto` | Projetos | RN-01..07 |
| `RegistrarNovaAtividade` | Projetos | RN-12..20 |
| `AlterarStatusAtividade` | Projetos | RN-16, RN-18 (inclui concluir) |
| `CancelarProjeto` | Projetos | RN-11 |
| `ObterResponsaveisSugeridos` (query) | Projetos | RN-14 — AM/PV da última atividade |

Leituras para telas usam **Queries** (`Application/Queries`), implementadas na Infraestrutura. Escritas **sempre** passam por Casos de Uso.

## 8. Arquitetura

- **Laravel 13 + PHP 8.3+** (dev: PHP 8.5 no Ubuntu 26.04), **PostgreSQL**, **Pest 4**.
- **Front-end:** Livewire 4 (componentes de classe em `app/Livewire`, views em `resources/views/livewire`), Alpine.js (embutido no Livewire), Bootstrap 5.
- **Clean Architecture + DDD:**

```
src/<Contexto>/
├── Domain/          PHP puro. Sem Laravel, sem Eloquent, sem helpers globais (collect(), Str, now()...)
├── Application/     Casos de uso, DTOs (readonly), Ports (interfaces), Queries. Sem Laravel.
└── Infrastructure/  Eloquent Models, Repositórios, Mappers, implementações dos Ports.
app/                 Apresentação: Livewire, Controllers API, FormRequests, Providers.
```

- Regras verificadas por **testes de arquitetura** (`tests/Arch`): Domain e Application não dependem de `Illuminate`, `Livewire` ou `App`.
- **Segurança (shift-left / OWASP):**
  - Validação sintática na borda (FormRequest / regras do Livewire); validação semântica nos VOs/Entidades.
  - Sem mass assignment: nada de `$model->fill($request->all())`; o Mapper monta o array de persistência.
  - Eloquent Models usam `$fillable` explícito.
  - BOLA: Casos de Uso recebem o `usuarioExecutorId` (quando o login existir).
  - Criptografia/hash via interfaces no domínio, implementadas na infraestrutura.

## 9. Roadmap

1. ✅ Domínio de Projetos/Atividades e Fornecedores (fase anterior).
2. ✅ Realinhamento aos requisitos (este documento) + ambiente Ubuntu 26.04.
3. ⏳ Autenticação (login) + papéis + regra de observação por autor + BOLA (carteira do AM).
4. ⏳ Telas de cadastro de Clientes, Fornecedores/Soluções e Usuários.
5. ⏳ Edição de atividade (descrição, datas, responsáveis) e de Código de Oportunidade.

## 10. Decisões em aberto

- **D-01** Quando todas as atividades abertas estiverem `Parada`, o projeto deve ficar `Parado` automaticamente? (hoje fica `Em Andamento`)
- **D-02** Mover para `Parada` exige justificativa obrigatória na observação?
- **D-03** Quem pode cancelar projeto (todos vs. só Admin Geral)?
- **D-04** Carteira do AM (BOLA): o AM vê só projetos em que é AM de alguma atividade, ou há um "dono" do projeto?
