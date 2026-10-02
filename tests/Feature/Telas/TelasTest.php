<?php

declare(strict_types=1);

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
