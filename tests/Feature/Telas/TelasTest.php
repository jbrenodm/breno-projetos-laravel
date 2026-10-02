<?php

declare(strict_types=1);

use App\Livewire\Dashboards\PainelOperacional;
use App\Livewire\Dashboards\PrazosEEntrega;
use App\Livewire\Dashboards\TodasAsAtividades;
use App\Livewire\Parceiros\Clientes;
use App\Livewire\Parceiros\Fornecedores;
use App\Livewire\Projetos\DetalheProjeto;
use App\Livewire\Projetos\PainelProjetos;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Src\Identidade\Domain\Papel;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Shared\Application\Ports\Relogio;

beforeEach(function () {
    $this->clienteId = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Banco Teste S.A.', 'Banco Teste'));
    $this->fornecedorId = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Alpha Ltda', 'Alpha', null, ['EDR']));
    $this->am = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create(['name' => 'Ana AM']);
    $this->pv = User::factory()->comPapel(Papel::PRE_VENDAS)->create(['name' => 'Paulo PV']);
});

it('todas as páginas abrem (HTTP 200)', function () {
    $this->get('/')->assertOk()->assertSee('Projetos');
    $this->get('/clientes')->assertOk()->assertSee('Banco Teste');
    $this->get('/fornecedores')->assertOk()->assertSee('EDR');
    $this->get('/projetos/'.Str::uuid())->assertNotFound();
});

it('cria projeto pelo painel e mostra na lista', function () {
    Livewire::test(PainelProjetos::class)
        ->call('abrirFormulario')
        ->assertSee('Banco Teste')
        ->set('clienteId', $this->clienteId)
        ->set('vinculos.0.fornecedor_id', $this->fornecedorId)
        ->assertSee('EDR') // soluções aparecem ao escolher o fornecedor
        ->set('codigoOportunidade', 'OPP-1')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSet('mostrarFormulario', false)
        ->assertSee('OPP-1');

    $this->assertDatabaseHas('projetos', ['cliente_id' => $this->clienteId, 'status' => 'Não Iniciado']);
});

it('exige cliente e fornecedor no painel', function () {
    Livewire::test(PainelProjetos::class)
        ->call('abrirFormulario')
        ->call('salvar')
        ->assertHasErrors(['clienteId', 'vinculos.0.fornecedor_id']);
});

it('registra atividades, pré-preenche AM/PV e muda status na tela de detalhe', function () {
    $this->post('/api/v1/projetos', ['cliente_id' => $this->clienteId, 'fornecedores' => [['fornecedor_id' => $this->fornecedorId]]]);
    $projetoId = DB::table('projetos')->value('id');

    $tela = Livewire::test(DetalheProjeto::class, ['projetoId' => $projetoId])
        ->call('abrirFormulario')
        ->assertSet('accountManagerId', '') // 1ª atividade: sem sugestão
        ->set('descricao', 'Levantamento de requisitos')
        ->set('dataLimite', now()->addDays(10)->toDateString())
        ->set('accountManagerId', $this->am->id)
        ->set('preVendasId', $this->pv->id)
        ->call('registrarAtividade')
        ->assertHasNoErrors()
        ->assertSee('Levantamento de requisitos')
        ->assertSee('Ana AM');

    $tela->call('abrirFormulario')
        ->assertSet('accountManagerId', $this->am->id) // RN-14
        ->assertSet('preVendasId', $this->pv->id);

    $atividadeId = DB::table('atividades')->value('id');
    $tela->call('prepararMudancaDeStatus', $atividadeId, 'Concluída')
        ->set('dataDoStatus', now()->toDateString())
        ->call('confirmarMudancaDeStatus')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('atividades', ['id' => $atividadeId, 'status' => 'Concluída']);
    $this->assertDatabaseHas('projetos', ['id' => $projetoId, 'status' => 'Concluído']);
});

it('mostra erro de regra de negócio na tela (data limite antes da entrada)', function () {
    $this->post('/api/v1/projetos', ['cliente_id' => $this->clienteId, 'fornecedores' => [['fornecedor_id' => $this->fornecedorId]]]);

    Livewire::test(DetalheProjeto::class, ['projetoId' => DB::table('projetos')->value('id')])
        ->call('abrirFormulario')
        ->set('descricao', 'X')
        ->set('dataEntrada', '2026-10-10')
        ->set('dataLimite', '2026-10-01')
        ->set('accountManagerId', $this->am->id)
        ->set('preVendasId', $this->pv->id)
        ->call('registrarAtividade')
        ->assertHasErrors('dataLimite');
});

it('cadastra cliente e fornecedor pelas telas', function () {
    Livewire::test(Clientes::class)
        ->set('razaoSocial', 'Novo Cliente Ltda')
        ->set('cnpj', '123')
        ->call('salvar')
        ->assertHasErrors('geral') // CNPJ inválido vem do domínio
        ->set('cnpj', '')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSee('Novo Cliente Ltda');

    Livewire::test(Fornecedores::class)
        ->set('razaoSocial', 'Beta Ltda')
        ->set('solucoes', "Firewall\nVPN")
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSee('VPN');
});

it('edita atividade e troca o cliente pela tela de detalhe (RN-28/RN-29)', function () {
    $this->post('/api/v1/projetos', ['cliente_id' => $this->clienteId, 'fornecedores' => [['fornecedor_id' => $this->fornecedorId]]]);
    $projetoId = DB::table('projetos')->value('id');
    $outroAm = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create(['name' => 'Bia AM']);
    $novoCliente = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Varejo Novo S.A.', 'Varejo Novo'));

    $tela = Livewire::test(DetalheProjeto::class, ['projetoId' => $projetoId])
        ->call('abrirFormulario')
        ->set('descricao', 'Levantamento')
        ->set('dataLimite', now()->addDays(10)->toDateString())
        ->set('accountManagerId', $this->am->id)
        ->set('preVendasId', $this->pv->id)
        ->call('registrarAtividade');

    $atividadeId = DB::table('atividades')->value('id');

    $tela->call('editarAtividade', $atividadeId)
        ->assertSet('descricao', 'Levantamento')
        ->assertSet('accountManagerId', $this->am->id)
        ->assertSee('Editar atividade')
        ->set('descricao', 'Levantamento revisado')
        ->set('accountManagerId', $outroAm->id)
        ->call('salvarEdicao')
        ->assertHasNoErrors()
        ->assertSet('mostrarFormulario', false)
        ->assertSee('Levantamento revisado')
        ->assertSee('Bia AM');

    $tela->call('abrirTrocaDeCliente')
        ->assertSee('Varejo Novo')
        ->set('novoClienteId', $novoCliente)
        ->call('salvarCliente')
        ->assertHasNoErrors()
        ->assertSee('Varejo Novo');

    $this->assertDatabaseHas('atividades', ['id' => $atividadeId, 'descricao' => 'Levantamento revisado', 'account_manager_id' => $outroAm->id]);
    $this->assertDatabaseHas('projetos', ['id' => $projetoId, 'cliente_id' => $novoCliente]);
});

it('Dashboards › Todas as atividades: ordena por data de entrada (mais recentes primeiro) e filtra', function () {
    $outroCliente = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Varejo Novo S.A.', 'Varejo Novo'));
    $outroFornecedor = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Beta Ltda', 'Beta'));
    $outroAm = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create(['name' => 'Bia AM']);

    $registrar = function (string $cliente, string $fornecedor, array $atividade) {
        $projetoId = $this->postJson('/api/v1/projetos', ['cliente_id' => $cliente, 'fornecedores' => [['fornecedor_id' => $fornecedor]]])->json('id');
        $this->postJson("/api/v1/projetos/{$projetoId}/atividades", $atividade + [
            'tipo' => 'Mapeamento', 'status' => 'Não Iniciada', 'data_limite' => '2099-12-31',
            'account_manager_id' => $this->am->id, 'pre_vendas_id' => $this->pv->id,
        ])->assertCreated();
    };

    $registrar($this->clienteId, $this->fornecedorId, ['descricao' => 'Atividade Antiga', 'data_entrada' => '2020-01-10', 'data_limite' => '2020-01-20']); // atrasada
    $registrar($outroCliente, $outroFornecedor, ['descricao' => 'Atividade Recente', 'data_entrada' => '2026-09-15', 'tipo' => 'Comercial', 'account_manager_id' => $outroAm->id]);
    $registrar($this->clienteId, $this->fornecedorId, ['descricao' => 'Atividade Do Meio', 'data_entrada' => '2025-05-05', 'status' => 'Concluída', 'data_termino' => '2025-05-06']);

    $tela = Livewire::test(TodasAsAtividades::class)
        ->assertSet('ordenarPor', 'data_entrada')
        ->assertSet('sentido', 'desc')
        ->assertSeeInOrder(['Atividade Recente', 'Atividade Do Meio', 'Atividade Antiga'])
        ->call('inverterSentido')
        ->assertSeeInOrder(['Atividade Antiga', 'Atividade Do Meio', 'Atividade Recente'])
        ->set('ordenarPor', 'cliente')
        ->assertSeeInOrder(['Banco Teste', 'Varejo Novo'])
        ->set('ordenarPor', 'campo; DROP TABLE atividades') // valor fora da lista volta ao padrão
        ->assertSeeInOrder(['Atividade Antiga', 'Atividade Do Meio', 'Atividade Recente']);

    $apenas = function (array $filtros, string $esperada) {
        $tela = Livewire::test(TodasAsAtividades::class);
        foreach ($filtros as $campo => $valor) {
            $tela->set($campo, $valor);
        }
        $tela->assertSee($esperada);
        foreach (array_diff(['Atividade Antiga', 'Atividade Recente', 'Atividade Do Meio'], [$esperada]) as $outra) {
            $tela->assertDontSee($outra);
        }
    };

    $apenas(['clienteId' => $outroCliente], 'Atividade Recente');
    $apenas(['fornecedorId' => $outroFornecedor], 'Atividade Recente');
    $apenas(['tipo' => 'Comercial'], 'Atividade Recente');
    $apenas(['accountManagerId' => $outroAm->id], 'Atividade Recente');
    $apenas(['status' => 'Concluída'], 'Atividade Do Meio');
    $apenas(['somenteAtrasadas' => true], 'Atividade Antiga');
    $apenas(['entradaDe' => '2025-01-01', 'entradaAte' => '2025-12-31'], 'Atividade Do Meio');
    $apenas(['busca' => 'meio'], 'Atividade Do Meio');

    Livewire::test(TodasAsAtividades::class)
        ->set('preVendasId', $this->pv->id)
        ->assertSee(['Atividade Antiga', 'Atividade Recente', 'Atividade Do Meio'])
        ->set('busca', '100%_')
        ->assertSee('Nenhuma atividade encontrada')
        ->call('limparFiltros')
        ->assertSet('preVendasId', '')
        ->assertSee('Atividade Recente');

    $this->get('/dashboards/atividades?ordem=cliente&sentido=asc&cliente=nao-e-uuid&de=lixo')->assertOk()->assertSee('Atividade Recente');
});

it('Dashboards › Painel operacional: indicadores, atrasadas por AM/PV e próximos vencimentos', function () {
    app()->instance(Relogio::class, new class implements Relogio
    {
        public function hoje(): DateTimeImmutable
        {
            return new DateTimeImmutable('2026-10-15');
        }
    });
    $outroAm = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create(['name' => 'Bia AM']);

    $projeto = fn (array $extra = []) => $this->postJson('/api/v1/projetos', $extra + ['cliente_id' => $this->clienteId, 'fornecedores' => [['fornecedor_id' => $this->fornecedorId]]])->json('id');
    $registrar = fn (string $projetoId, string $descricao, string $limite, array $extra = []) => $this->postJson("/api/v1/projetos/{$projetoId}/atividades", $extra + [
        'descricao' => $descricao, 'tipo' => 'Mapeamento', 'status' => 'Em Andamento',
        'data_entrada' => '2026-09-01', 'data_limite' => $limite,
        'account_manager_id' => $this->am->id, 'pre_vendas_id' => $this->pv->id,
    ])->assertCreated();

    $outroCliente = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Varejo Novo S.A.', 'Varejo Novo'));
    $p1 = $projeto(['codigo_oportunidade' => 'OPP-2026-0042']);
    $p2 = $projeto(['cliente_id' => $outroCliente]); // sem código de oportunidade
    $registrar($p1, 'Atrasada da Ana 1', '2026-10-01');
    $registrar($p1, 'Atrasada da Ana 2', '2026-10-10');
    $registrar($p2, 'Atrasada da Bia', '2026-10-14', ['account_manager_id' => $outroAm->id]);
    $registrar($p1, 'Vence hoje', '2026-10-15');
    $registrar($p1, 'Vence no limite da janela', '2026-10-22');
    $registrar($p1, 'Vence depois da janela', '2026-10-23');
    $registrar($p1, 'Concluída no mês', '2026-10-05', ['status' => 'Concluída', 'data_termino' => '2026-10-03']); // atrasou, mas não está aberta
    $registrar($p1, 'Concluída mês passado', '2026-09-30', ['status' => 'Concluída', 'data_termino' => '2026-09-20']);

    $cancelado = $projeto();
    $registrar($cancelado, 'De projeto cancelado', '2026-10-01');
    $this->postJson("/api/v1/projetos/{$cancelado}/cancelar")->assertOk();

    $painel = app(ProjetoQuery::class)->painelOperacional(new DateTimeImmutable('2026-10-15'));

    expect($painel)->toMatchArray(['abertas' => 6, 'atrasadas' => 3, 'vencem_em_7_dias' => 2, 'concluidas_no_mes' => 1])
        ->and($painel['atrasadas_por_am'])->toBe([
            ['id' => $this->am->id, 'nome' => 'Ana AM', 'total' => 2],
            ['id' => $outroAm->id, 'nome' => 'Bia AM', 'total' => 1],
        ])
        ->and($painel['atrasadas_por_cliente'])->toBe([
            ['id' => $this->clienteId, 'nome' => 'Banco Teste', 'total' => 2],
            ['id' => $outroCliente, 'nome' => 'Varejo Novo', 'total' => 1],
        ])
        ->and($painel['atrasadas_por_projeto'])->toBe([
            ['id' => $p1, 'nome' => 'Banco Teste — OPP-2026-0042', 'total' => 2],
            ['id' => $p2, 'nome' => 'Varejo Novo — aberto em '.now()->format('d/m/Y'), 'total' => 1],
        ])
        ->and(array_column($painel['proximos_vencimentos'], 'descricao'))->toBe(['Vence hoje', 'Vence no limite da janela'])
        ->and(array_column($painel['proximos_vencimentos'], 'dias_restantes'))->toBe([0, 7]);

    $this->get('/dashboards/operacional')->assertOk()
        ->assertSee(['Painel operacional', 'Atrasadas', 'Vencem em 7 dias', 'Concluídas no mês', 'Outubro de 2026'])
        ->assertSeeInOrder(['Ana AM', 'Bia AM'])
        ->assertSee(['Atividades atrasadas por Cliente', 'Atividades atrasadas por Projeto', 'Banco Teste — OPP-2026-0042'])
        ->assertDontSee('Atividades atrasadas por Pré-vendas')
        ->assertSee(route('projetos.show', $p1))
        ->assertSee(route('dashboards.atividades', ['cliente' => $this->clienteId]), false)
        ->assertSee(['vence hoje', 'em 7 dias'])
        ->assertDontSee('De projeto cancelado')
        ->assertDontSee('Vence depois da janela');
});

it('Dashboards › Painel operacional vazio mostra estados vazios', function () {
    Livewire::test(PainelOperacional::class)
        ->assertSee('Nenhuma atividade atrasada.')
        ->assertSee('Nenhuma atividade vence nos próximos 7 dias.');
});

it('Dashboards › Prazos e entrega: % no prazo, execução e atraso no período', function () {
    app()->instance(Relogio::class, new class implements Relogio
    {
        public function hoje(): DateTimeImmutable
        {
            return new DateTimeImmutable('2026-10-15');
        }
    });
    $outroAm = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create(['name' => 'Bia AM']);

    $projeto = fn () => $this->postJson('/api/v1/projetos', ['cliente_id' => $this->clienteId, 'fornecedores' => [['fornecedor_id' => $this->fornecedorId]]])->json('id');
    $concluir = fn (string $projetoId, string $tipo, ?string $inicio, string $limite, string $termino, array $extra = []) => $this->postJson("/api/v1/projetos/{$projetoId}/atividades", $extra + [
        'descricao' => "{$tipo} {$termino}", 'tipo' => $tipo, 'status' => 'Concluída',
        'data_entrada' => '2025-09-01', 'data_inicio' => $inicio, 'data_limite' => $limite, 'data_termino' => $termino,
        'account_manager_id' => $this->am->id, 'pre_vendas_id' => $this->pv->id,
    ])->assertCreated();

    $p = $projeto();
    $concluir($p, 'Mapeamento', '2026-10-01', '2026-10-10', '2026-10-05');                                               // no prazo, 4 dias
    $concluir($p, 'Implantação', '2026-10-02', '2026-10-08', '2026-10-12', ['account_manager_id' => $outroAm->id]);       // 4 dias de atraso, 10 de execução
    $concluir($p, 'Implantação', null, '2026-07-10', '2026-07-20');                                                      // 10 de atraso, sem início
    $concluir($p, 'Comercial', '2025-09-01', '2025-10-30', '2025-10-20');                                                // fora dos 12 meses
    $this->postJson("/api/v1/projetos/{$p}/atividades", ['descricao' => 'Aberta', 'tipo' => 'Comercial', 'status' => 'Em Andamento',
        'data_entrada' => '2026-10-01', 'data_limite' => '2026-10-05'])->assertCreated();                               // aberta não entra

    $cancelado = $projeto();
    $concluir($cancelado, 'Comercial', '2026-10-01', '2026-10-02', '2026-10-09');
    $this->postJson("/api/v1/projetos/{$cancelado}/cancelar")->assertOk();

    $dados = app(ProjetoQuery::class)->prazosEEntrega(new DateTimeImmutable('2026-10-15'));

    expect($dados)->toMatchArray([
        'inicio' => '2025-11-01', 'fim' => '2026-10-31',
        'concluidas' => 3, 'no_prazo' => 1, 'com_atraso' => 2, 'percentual_no_prazo' => 33.3,
        'execucao_media_dias' => 7.0, 'atraso_medio_dias' => 7.0,
    ])
        ->and($dados['por_mes'])->toHaveCount(12)
        ->and(collect($dados['por_mes'])->keyBy('mes')->only(['2026-07', '2026-09', '2026-10'])->map(fn ($m) => $m['percentual_no_prazo'])->all())
        ->toBe(['2026-07' => 0.0, '2026-09' => null, '2026-10' => 50.0])
        ->and($dados['execucao_por_tipo'])->toBe([
            ['nome' => 'Implantação', 'media_dias' => 10.0, 'atividades' => 1],
            ['nome' => 'Mapeamento', 'media_dias' => 4.0, 'atividades' => 1],
        ])
        ->and($dados['atraso_por_tipo'])->toBe([['nome' => 'Implantação', 'media_dias' => 7.0, 'atividades' => 2]])
        ->and($dados['atraso_por_am'])->toBe([
            ['id' => $this->am->id, 'nome' => 'Ana AM', 'media_dias' => 10.0, 'atividades' => 1],
            ['id' => $outroAm->id, 'nome' => 'Bia AM', 'media_dias' => 4.0, 'atividades' => 1],
        ]);

    $this->get('/dashboards/prazos')->assertOk()
        ->assertSee(['Prazos e entrega', '33,3%', '1 de 3', '7 dias', 'Cumprimento de prazo por mês', 'out/26: 50% no prazo (1 de 2)', 'set/26: sem entregas'])
        ->assertSeeInOrder(['Atraso médio por Account Manager', 'Ana AM', '10 d', 'Bia AM', '4 d']);

    Livewire::test(PrazosEEntrega::class)
        ->call('$set', 'meses', 3)
        ->assertSee(['01/08/2026', '100%', '1 de 2']) // julho sai do período
        ->assertDontSee('jul/26');

    $this->get('/dashboards/prazos?meses=99')->assertOk()->assertSee('01/11/2025'); // período inválido volta ao padrão
});

it('Dashboards › Prazos e entrega vazio e com execução de 0 dias não quebra', function () {
    Livewire::test(PrazosEEntrega::class)
        ->assertSee(['Nenhuma atividade concluída no período.', 'Nenhuma entrega com atraso no período.']);

    $projetoId = $this->postJson('/api/v1/projetos', ['cliente_id' => $this->clienteId, 'fornecedores' => [['fornecedor_id' => $this->fornecedorId]]])->json('id');
    $hoje = now()->toDateString();
    $this->postJson("/api/v1/projetos/{$projetoId}/atividades", ['descricao' => 'Mesmo dia', 'tipo' => 'Comercial', 'status' => 'Concluída',
        'data_entrada' => $hoje, 'data_inicio' => $hoje, 'data_limite' => $hoje, 'data_termino' => $hoje,
        'account_manager_id' => $this->am->id, 'pre_vendas_id' => $this->pv->id])->assertCreated();

    $this->get('/dashboards/prazos')->assertOk()->assertSee(['0 dias', '0 d']);
});

it('edita, inativa e reativa clientes pela tela (RN-30/RN-31)', function () {
    Livewire::test(Clientes::class)
        ->call('editar', $this->clienteId)
        ->assertSet('razaoSocial', 'Banco Teste S.A.')
        ->assertSet('nomeFantasia', 'Banco Teste')
        ->assertSee('Editar cliente')
        ->set('nomeFantasia', 'Banco Renomeado')
        ->set('cnpj', '11.444.777/0001-61')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSet('editandoId', null)
        ->assertSee(['Banco Renomeado', '11.444.777/0001-61', 'Cliente atualizado.'])
        ->call('alterarSituacao', $this->clienteId, false)
        ->assertSee(['Inativo', 'Reativar']);

    $this->assertDatabaseHas('clientes', ['id' => $this->clienteId, 'nome_fantasia' => 'Banco Renomeado', 'ativo' => false]);

    Livewire::test(PainelProjetos::class)->call('abrirFormulario')->assertDontSee('Banco Renomeado'); // RN-24

    Livewire::test(Clientes::class)->call('alterarSituacao', $this->clienteId, true);
    $this->assertDatabaseHas('clientes', ['id' => $this->clienteId, 'ativo' => true]);
});

it('edita fornecedor e soluções, inativa e reativa pela tela (RN-30..32)', function () {
    $solucaoId = DB::table('solucoes')->where('fornecedor_id', $this->fornecedorId)->value('id');

    Livewire::test(Fornecedores::class)
        ->call('editar', $this->fornecedorId)
        ->assertSet('razaoSocial', 'Alpha Ltda')
        ->set('razaoSocial', 'Alpha Segurança Ltda')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertSee(['Alpha Segurança Ltda', 'Fornecedor atualizado.'])
        ->call('editarSolucao', $this->fornecedorId, $solucaoId)
        ->assertSet('solucaoNome', 'EDR')
        ->set('solucaoNome', 'EDR Avançado')
        ->set('solucaoDescricao', 'Detecção e resposta')
        ->call('salvarSolucao')
        ->assertHasNoErrors()
        ->assertSet('solucaoEditandoId', null)
        ->assertSee(['EDR Avançado', 'Detecção e resposta'])
        ->call('alterarSituacaoSolucao', $this->fornecedorId, $solucaoId, false)
        ->assertSee('Inativa')
        ->call('alterarSituacao', $this->fornecedorId, false)
        ->assertSee('Reativar');

    $this->assertDatabaseHas('fornecedores', ['id' => $this->fornecedorId, 'razao_social' => 'Alpha Segurança Ltda', 'ativo' => false]);
    $this->assertDatabaseHas('solucoes', ['id' => $solucaoId, 'nome' => 'EDR Avançado', 'ativo' => false]);

    Livewire::test(Fornecedores::class)
        ->call('adicionarSolucao', $this->fornecedorId) // nome vazio: ignora
        ->set("novaSolucao.{$this->fornecedorId}", 'XDR')
        ->call('adicionarSolucao', $this->fornecedorId)
        ->call('editarSolucao', $this->fornecedorId, DB::table('solucoes')->where('nome', 'XDR')->value('id'))
        ->set('solucaoNome', 'edr avançado')
        ->call('salvarSolucao')
        ->assertSee("Este fornecedor já possui a solução 'edr avançado'.");
});
