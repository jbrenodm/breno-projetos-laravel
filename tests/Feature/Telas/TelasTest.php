<?php

declare(strict_types=1);

use App\Livewire\Dashboards\PainelOperacional;
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
