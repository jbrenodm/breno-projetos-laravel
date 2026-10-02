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
| **Admin Geral do Sistema** | Usuário com papel `admin_geral`: gerencia usuários e permissões (RN-35). |

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
- **RN-29 (troca de cliente)** O Cliente do Projeto pode ser trocado em qualquer status, exceto `Cancelado` (RN-11).
  O novo Cliente deve estar **ativo** (RN-06). A troca vale para o projeto inteiro (as atividades não têm cliente próprio).
  *(Decidido em 02/10/2026.)*

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
- **RN-28 (edição de atividade)** Uma atividade pode ser editada, inclusive quando `Concluída` (correção de histórico).
  - Campos editáveis: `descricao`, `tipo`, datas (`data_entrada`, `data_limite`, `data_inicio`, `data_termino`),
    AM/PV e observação.
  - O **status não é editável** aqui: muda apenas pelas transições da RN-16.
  - As datas seguem a RN-18 e a coerência com o status atual, igual ao registro: `Não Iniciada` não tem início;
    só `Concluída` tem término; `Em Andamento`/`Parada` sem início assumem a `data_entrada`; `Concluída` sem término assume hoje.
  - AM e PV continuam obrigatórios (RN-13) e, se trocados, devem ser usuários ativos com o papel correspondente (RN-25).
  - A observação pode ser alterada ou removida; a restrição por autor (RN-20) passa a valer quando houver login.
  - Projeto `Cancelado` não permite editar atividades (RN-11).
  *(Decidido em 02/10/2026.)*

### 4.4 Cliente e Fornecedor
- **RN-21** Cliente e Fornecedor: `razao_social` obrigatória; `nome_fantasia` e `cnpj` **opcionais**.
- **RN-22** CNPJ, se informado, deve ter 14 dígitos válidos e ser **único** (vários registros sem CNPJ são permitidos).
- **RN-23** Fornecedor tem um catálogo opcional de Soluções; nome de solução não se repete dentro do mesmo fornecedor.
- **RN-24** Cliente/Fornecedor/Solução inativos não aparecem para novos projetos, mas o histórico é preservado.
- **RN-30 (edição de cadastro)** Todos os dados de Cliente e Fornecedor podem ser editados: `razao_social`, `nome_fantasia` e `cnpj`,
  sempre com as validações da RN-21/RN-22 (o CNPJ alterado continua único; pode ser removido). *(Decidido em 02/10/2026.)*
- **RN-31 (inativar/reativar)** Cliente, Fornecedor e Solução podem ser inativados e reativados a qualquer momento. A inativação
  segue a RN-24: some das opções de novos projetos, e projetos/atividades existentes **não são alterados**. *(Decidido em 02/10/2026.)*
- **RN-32 (edição de solução)** Nome e descrição da Solução podem ser editados; o nome continua sem repetir dentro do mesmo
  fornecedor (RN-23). *(Decidido em 02/10/2026.)*

### 4.5 Usuários e permissões
- **RN-25** AM e PV **não são tabelas próprias**: são usuários com papéis (`account_manager`, `pre_vendas`, `admin_geral`).
- **RN-26** Inicialmente todos os usuários podem alterar status. No futuro um Admin Geral do Sistema distribuirá permissões (RBAC).
- **RN-27 (BOLA)** A autorização acontece no **Caso de Uso** (não só em rotas/middleware), usando o ID do usuário autenticado.

### 4.6 Autenticação e cadastro de usuários *(decidido em 02/10/2026)*
- **RN-33 (login)** Acesso por e-mail e senha. Usuário **inativo** não entra; se for inativado com a sessão aberta, é desconectado
  na requisição seguinte. Máximo de 5 tentativas por minuto por e-mail + IP. A mensagem de erro é genérica
  (não revela se o e-mail existe). Há logout ("Sair").
- **RN-34 (tudo exige login)** Todas as telas e a API exigem autenticação. Telas: sessão. API: **token pessoal** (Laravel Sanctum),
  gerado pelo próprio usuário em "Minha conta", exibido uma única vez e revogável. Inativar o usuário revoga os tokens dele.
- **RN-35 (cadastro de usuários)** Só o **Admin Geral do Sistema** cadastra e edita usuários: nome, e-mail (único, sem diferenciar maiúsculas),
  papéis (pelo menos 1) e situação (ativo/inativo). Usuário inativo some das listas de AM/PV para novas atividades;
  atividades existentes não mudam (como na RN-24).
- **RN-36 (senha temporária)** No cadastro o Admin define uma **senha temporária**; no primeiro acesso o usuário é obrigado a trocá-la
  antes de usar o sistema. O Admin pode redefinir uma senha temporária a qualquer momento (troca obrigatória de novo).
- **RN-37 (esqueci minha senha)** Na tela de login, o usuário pede um link de redefinição por e-mail, válido por 60 minutos.
  A resposta não revela se o e-mail existe; usuários inativos não recebem o link. Requer SMTP configurado no `.env`.
- **RN-38 (política de senha)** Mínimo de 8 caracteres, com letras e números; a nova senha deve ser diferente da atual.
- **RN-39 (sempre há um Admin)** Nenhuma operação pode deixar o sistema sem **Admin Geral do Sistema ativo** (inativar o último Admin ou
  remover o papel dele é rejeitado).
- **RN-40 (minha conta)** Todo usuário pode trocar a própria senha (informando a atual) e gerenciar seus tokens de API.
- **RN-41 (renomear papéis)** O Admin Geral do Sistema pode alterar o **nome exibido** e a **sigla** de cada papel
  (ex.: `account_manager` → "Gerente de Contas" / "GC"). O novo nome vale em todo o sistema: telas, filtros, gráficos e mensagens.
  Nome obrigatório (até 60 caracteres) e sigla opcional (até 10); nenhum dos dois se repete entre papéis (sem diferenciar maiúsculas).
  O identificador interno (`account_manager`, `pre_vendas`, `admin_geral`) **nunca muda** e os papéis não podem ser criados nem
  excluídos — papéis novos dependem do controle de permissões (RBAC, RN-26), ainda não definido. *(Decidido em 02/10/2026.)*
  Neste documento continuam valendo os termos AM, PV e Admin Geral do Sistema (linguagem ubíqua).
- **Instalação:** num banco sem Admin Geral do Sistema ativo, o primeiro Admin é criado pelo terminal com
  `php artisan usuarios:criar-admin {email} {nome}` (pede a senha). Havendo um Admin ativo, o comando é recusado (RN-35/RN-39).
- Com o login, a **RN-20** (só o autor edita a observação) passa a valer. A carteira do AM (D-04) continua em aberto:
  por enquanto todo usuário logado vê todos os projetos.

## 5. Modelo de domínio

```
[Agregado: Projeto]  (src/Projetos/Domain)
├── ProjetoId (VO, UUID)
├── ClienteId (string UUID) — trocável (RN-29)
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

**users**: `id uuid pk`, `name`, `email unique`, `password`, `ativo bool`, `deve_trocar_senha bool default false` (RN-36), timestamps (+ campos padrão do Laravel).

**personal_access_tokens**: tabela padrão do Laravel Sanctum (tokens de API, RN-34).

**roles**: `id uuid pk`, `nome unique` (identificador interno: `account_manager`, `pre_vendas`, `admin_geral`),
`descricao` (nome exibido, editável — RN-41), `sigla varchar(10) null` (padrão: AM, PV e vazio para o Admin).

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
| `EditarCliente` / `EditarFornecedor` | Parceiros | RN-30 |
| `AlterarSituacaoCliente` / `AlterarSituacaoFornecedor` | Parceiros | RN-31 (ativar/inativar) |
| `EditarSolucao` / `AlterarSituacaoSolucao` | Parceiros | RN-31, RN-32 |
| `CadastrarUsuario` / `EditarUsuario` / `AlterarSituacaoUsuario` | Identidade | RN-35, RN-39 (só Admin Geral do Sistema) |
| `RedefinirSenhaTemporaria` | Identidade | RN-36 (só Admin Geral do Sistema) |
| `TrocarSenha` / `RedefinirSenhaPorLink` | Identidade | RN-36..38, RN-40 |
| `GerarTokenDeApi` / `RevogarTokenDeApi` | Identidade | RN-34, RN-40 |
| `CriarPrimeiroAdmin` | Identidade | Instalação (comando `usuarios:criar-admin`) |
| `RenomearPapel` | Identidade | RN-41 (só Admin Geral do Sistema) |
| `RegistrarNovoProjeto` | Projetos | RN-01..07 |
| `RegistrarNovaAtividade` | Projetos | RN-12..20 |
| `AlterarStatusAtividade` | Projetos | RN-16, RN-18 (inclui concluir) |
| `CancelarProjeto` | Projetos | RN-11 |
| `EditarAtividade` | Projetos | RN-28 |
| `AlterarClienteDoProjeto` | Projetos | RN-29 |
| `ObterResponsaveisSugeridos` (query) | Projetos | RN-14 — AM/PV da última atividade |

| `PainelOperacional` (query) | Projetos | Indicadores do Dashboards › Painel operacional |
| `PrazosEEntrega` (query) | Projetos | Indicadores do Dashboards › Prazos e entrega |
| `ListarAtividades` (query) | Projetos | Todas as atividades de todos os projetos (tela Dashboards › Atividades) |

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
3. 🔶 Login/logout, esqueci minha senha, tokens de API e cadastro de usuários ✅ (RN-33..40); observação por autor ✅ (RN-20);
   BOLA da carteira do AM ⏳ (D-04).
4. ✅ Cadastro, edição e ativação/inativação de Clientes, Fornecedores e Soluções (RN-30..32) e de Usuários (RN-35);
   renomear papéis (RN-41). Papéis novos com permissões (RBAC) ⏳ — depende da lista de permissões e de D-03/D-04.
5. 🔶 Edição de atividade (RN-28) e troca de cliente do projeto (RN-29) ✅; edição de Código de Oportunidade ⏳.
6. 🔶 Menu **Dashboards** (ao lado de Projetos, Clientes e Fornecedores), que agrupa dashboards e relatórios:
   tela "Todas as atividades" ✅ (somente leitura: cliente em destaque, depois a atividade e os demais dados).
   - Ordenação por data de entrada (**padrão, mais recentes primeiro**), data limite, cliente, status ou tipo, em ambos os sentidos.
   - Filtros: status, cliente, tipo, AM, PV, fornecedor do projeto, somente atrasadas, período de entrada (de/até) e busca
     por cliente ou descrição.
   - Atrasada = `data_limite` anterior ao término (ou a hoje, se não concluída).
   Tela "Painel operacional" ✅ — responde "o que precisa de atenção agora?":
   - Considera apenas projetos **não cancelados**. "Aberta" = atividade com status diferente de `Concluída`.
   - Indicadores: atividades abertas; **atrasadas** (abertas com `data_limite` anterior a hoje); **vencem em 7 dias**
     (abertas com `data_limite` entre hoje e hoje + 7, inclusive); **concluídas no mês** (`data_termino` no mês corrente).
   - Gráficos (barras, do maior para o menor): atrasadas por AM, atrasadas por Cliente e atrasadas por Projeto.
     Projeto é identificado por "Cliente — Código de Oportunidade" ou, sem código, "Cliente — aberto em dd/mm/aaaa".
   - Lista dos próximos vencimentos: até 10 atividades abertas que vencem em 7 dias, pela data limite.
   Tela "Prazos e entrega" ✅ — responde "estamos entregando no prazo?":
   - Base: atividades `Concluída` com `data_termino` dentro do período (últimos 3, 6 ou **12 meses — padrão**, contando o mês atual),
     de projetos **não cancelados**.
   - **No prazo** = `data_termino` ≤ `data_limite`. **Atraso** = `data_termino − data_limite` em dias (só das entregues com atraso).
   - **Tempo de execução** = `data_termino − data_inicio` em dias; atividades sem `data_inicio` ficam fora dessa média.
   - Indicadores: concluídas no período, % no prazo, tempo médio de execução, atraso médio.
   - Gráficos: % no prazo por mês (linha; mês sem entregas fica sem ponto), tempo médio de execução por tipo,
     atraso médio por tipo e por AM.
   demais dashboards/relatórios ⏳.

## 10. Decisões em aberto

- **D-01** Quando todas as atividades abertas estiverem `Parada`, o projeto deve ficar `Parado` automaticamente? (hoje fica `Em Andamento`)
- **D-02** Mover para `Parada` exige justificativa obrigatória na observação?
- **D-03** Quem pode cancelar projeto (todos vs. só Admin Geral do Sistema)?
- **D-04** Carteira do AM (BOLA): o AM vê só projetos em que é AM de alguma atividade, ou há um "dono" do projeto?
