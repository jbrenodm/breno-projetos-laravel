<?php

declare(strict_types=1);

use App\Livewire\Admin\Papeis;
use App\Livewire\Dashboards\TodasAsAtividades;
use App\Livewire\Projetos\DetalheProjeto;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Src\Identidade\Application\DTOs\RenomearPapelInput;
use Src\Identidade\Application\UseCases\RenomearPapel;
use Src\Identidade\Domain\Papel;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Responsaveis\Domain\Funcao;
use Src\Shared\Application\AcessoNegadoException;

beforeEach(function () {
    $this->seed(PapeisSeeder::class); // como numa instalação real: os três papéis existem em "roles"
    $this->admin = User::factory()->comPapel(Papel::ADMIN_GERAL)->create(['name' => 'Admin']);
    $this->ana = responsavel('Ana', Funcao::ACCOUNT_MANAGER);
    $this->paulo = responsavel('Paulo', Funcao::PRE_VENDAS);
    $this->comum = User::factory()->create(['name' => 'Comum']);
});

function renomear(string $papel, string $nome, ?string $sigla): void
{
    Livewire::test(Papeis::class)->call('editar', $papel)->set('nome', $nome)->set('sigla', (string) $sigla)
        ->call('salvar')->assertHasNoErrors();
}

it('RN-41: só o Admin Geral do Sistema acessa e renomeia papéis', function () {
    $this->actingAs($this->comum)->get('/papeis')->assertForbidden();
    expect(fn () => app(RenomearPapel::class)->execute(new RenomearPapelInput($this->comum->id, 'pre_vendas', 'X')))
        ->toThrow(AcessoNegadoException::class);

    $this->actingAs($this->admin)->get('/papeis')->assertOk()
        ->assertSeeInOrder(['Account Manager', 'AM', 'account_manager'])
        ->assertSee(['Pré-vendas', 'Admin Geral do Sistema', 'href="'.route('papeis.index').'"'], false);
});

it('RN-41: o novo nome e a nova sigla valem em todas as telas, filtros, gráficos e mensagens', function () {
    $this->actingAs($this->admin);
    renomear('account_manager', 'Gerente de Contas', 'GC');

    // dados para as telas
    $cliente = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Banco S.A.'));
    $fornecedor = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Forn Ltda'));
    Sanctum::actingAs($this->admin);
    $projeto = $this->postJson('/api/v1/projetos', ['cliente_id' => $cliente, 'fornecedores' => [['fornecedor_id' => $fornecedor]]])->json('id');

    // mensagens da API usam o nome atual (RN-41)
    $this->postJson("/api/v1/projetos/{$projeto}/atividades", ['descricao' => 'X', 'tipo' => 'Comercial', 'status' => 'Não Iniciada',
        'data_entrada' => '2020-01-01', 'data_limite' => '2020-01-02'])
        ->assertUnprocessable()->assertJsonPath('error', 'Para registrar a primeira atividade do projeto é obrigatório informar Gerente de Contas e Pré-vendas.');
    $this->postJson("/api/v1/projetos/{$projeto}/atividades", ['descricao' => 'X', 'tipo' => 'Comercial', 'status' => 'Não Iniciada',
        'data_entrada' => '2020-01-01', 'data_limite' => '2020-01-02', 'account_manager_id' => $this->paulo->id, 'pre_vendas_id' => $this->paulo->id])
        ->assertUnprocessable()->assertJsonPath('error', 'O Gerente de Contas informado não existe, está inativo ou não possui essa função.');
    $this->postJson("/api/v1/projetos/{$projeto}/atividades", ['descricao' => 'Levantamento', 'tipo' => 'Comercial', 'status' => 'Não Iniciada',
        'data_entrada' => '2020-01-01', 'data_limite' => '2020-01-02', 'account_manager_id' => $this->ana->id, 'pre_vendas_id' => $this->paulo->id])
        ->assertCreated();

    $this->actingAs($this->admin);
    Livewire::test(DetalheProjeto::class, ['projetoId' => $projeto])
        ->assertSee(['GC: Ana', 'PV: Paulo'])
        ->call('abrirFormulario')
        ->assertSee(['Gerente de Contas', 'GC e PV pré-preenchidos'])
        ->set('accountManagerId', '')->set('descricao', 'Y')->set('dataLimite', '2030-01-01')
        ->call('registrarAtividade')
        ->assertSee('O campo Gerente de Contas é obrigatório.');

    Livewire::test(TodasAsAtividades::class)->assertSee(['Todos os GCs', 'Todos os PVs', 'GC: Ana']);
    $this->get('/dashboards/operacional')->assertSeeInOrder(['Atividades atrasadas por Gerente de Contas', 'Ana'])->assertDontSee('Account Manager');
    $this->get('/dashboards/prazos')->assertSee('Atraso médio por Gerente de Contas');
    $this->get('/responsaveis')->assertSee('Gerente de Contas')->assertDontSee('Account Manager');
});

it('RN-41: papel sem sigla usa o nome; nome e sigla não se repetem entre papéis', function () {
    $this->actingAs($this->admin);
    renomear('pre_vendas', 'Pré-venda Técnica', '');
    Livewire::test(TodasAsAtividades::class)->assertSee('Todos (Pré-venda Técnica)');

    Livewire::test(Papeis::class)->call('editar', 'pre_vendas')->set('nome', 'ACCOUNT MANAGER')->call('salvar')
        ->assertSee("Já existe um papel chamado 'ACCOUNT MANAGER'.");
    Livewire::test(Papeis::class)->call('editar', 'pre_vendas')->set('nome', 'Pré-vendas')->set('sigla', 'am')->call('salvar')
        ->assertSee("A sigla 'am' já é usada por outro papel.");
    Livewire::test(Papeis::class)->call('editar', 'account_manager')->set('nome', 'account manager')->set('sigla', 'AM')->call('salvar')
        ->assertHasNoErrors(); // o próprio papel pode mudar só a caixa

    $this->assertDatabaseHas('roles', ['nome' => 'pre_vendas', 'descricao' => 'Pré-venda Técnica', 'sigla' => null]);
});

it('RN-41: rodar o seeder de novo não desfaz um papel renomeado', function () {
    $this->actingAs($this->admin);
    renomear('account_manager', 'Gerente de Contas', 'GC');

    $this->seed(PapeisSeeder::class);

    $this->assertDatabaseHas('roles', ['nome' => 'account_manager', 'descricao' => 'Gerente de Contas', 'sigla' => 'GC']);
});
