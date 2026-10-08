<?php

declare(strict_types=1);

use App\Livewire\Admin\TiposDeAtividade;
use App\Livewire\Dashboards\TodasAsAtividades;
use App\Livewire\Projetos\DetalheProjeto;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Projetos\Application\DTOs\CadastrarTipoAtividadeInput;
use Src\Projetos\Application\UseCases\CadastrarTipoAtividade;
use Src\Responsaveis\Domain\Funcao;
use Src\Shared\Application\AcessoNegadoException;

// Feature/Telas: logado como Admin Geral do Sistema ($this->usuarioLogado — ver tests/Pest.php).

beforeEach(function () {
    $cliente = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Banco S.A.'));
    $fornecedor = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Forn Ltda'));
    $this->am = responsavel('Ana', Funcao::ACCOUNT_MANAGER);
    $this->pv = responsavel('Paulo', Funcao::PRE_VENDAS);

    Sanctum::actingAs($this->usuarioLogado);
    $this->projetoId = $this->postJson('/api/v1/projetos', ['cliente_id' => $cliente, 'fornecedores' => [['fornecedor_id' => $fornecedor]]])->json('id');
    $this->atividade = fn (array $dados) => $this->postJson("/api/v1/projetos/{$this->projetoId}/atividades", $dados + [
        'descricao' => 'Levantamento', 'status' => 'Não Iniciada', 'data_entrada' => '2026-10-01', 'data_limite' => '2026-10-15',
        'account_manager_id' => $this->am->id, 'pre_vendas_id' => $this->pv->id,
    ]);
});

it('RN-43: só o Admin Geral do Sistema acessa o cadastro de tipos (rota, menu e caso de uso)', function () {
    $comum = User::factory()->create();

    $this->get('/tipos-de-atividade')->assertOk()->assertSeeInOrder(['Comercial', 'Homologação', 'Implantação', 'Mapeamento']);
    $this->get('/')->assertSee('href="'.route('tipos-atividade.index').'"', false);

    $this->actingAs($comum)->get('/tipos-de-atividade')->assertForbidden();
    $this->actingAs($comum)->get('/')->assertDontSee('href="'.route('tipos-atividade.index').'"', false);
    expect(fn () => app(CadastrarTipoAtividade::class)->execute(new CadastrarTipoAtividadeInput($comum->id, 'Treinamento')))
        ->toThrow(AcessoNegadoException::class);
    $this->assertDatabaseMissing('tipos_atividade', ['nome' => 'Treinamento']);
});

it('RN-43: adiciona tipo com nome único (sem diferenciar maiúsculas) e ele aparece para novas atividades', function () {
    Livewire::test(TiposDeAtividade::class)
        ->set('nome', 'Treinamento')->call('salvar')->assertHasNoErrors()->assertSee('Tipo de atividade cadastrado.');
    Livewire::test(TiposDeAtividade::class)
        ->set('nome', 'COMERCIAL')->call('salvar')->assertSee("Já existe um tipo de atividade chamado 'COMERCIAL'.");
    Livewire::test(TiposDeAtividade::class)->set('nome', '')->call('salvar')->assertHasErrors(['nome']);

    Livewire::test(DetalheProjeto::class, ['projetoId' => $this->projetoId])->call('abrirFormulario')->assertSee('Treinamento');
    ($this->atividade)(['tipo' => 'treinamento'])->assertCreated(); // API: nome sem diferenciar maiúsculas
    $this->assertDatabaseCount('tipos_atividade', 5);
});

it('RN-43: renomear vale em todo o sistema, inclusive nas atividades existentes e na API', function () {
    ($this->atividade)(['tipo' => 'Comercial'])->assertCreated();

    Livewire::test(TiposDeAtividade::class)
        ->call('editar', tipoAtividadeId('Comercial'))->assertSet('nome', 'Comercial')
        ->set('nome', 'Vendas')->call('salvar')->assertHasNoErrors()->assertSee('Tipo renomeado');

    Livewire::test(DetalheProjeto::class, ['projetoId' => $this->projetoId])->assertSee('Vendas')->assertDontSee('Comercial');
    Livewire::test(TodasAsAtividades::class)->assertSee('Vendas')->assertDontSee('Comercial');
    $this->getJson("/api/v1/projetos/{$this->projetoId}")->assertJsonPath('data.atividades.0.tipo', 'Vendas');

    ($this->atividade)(['tipo' => 'Vendas'])->assertCreated();
    ($this->atividade)(['tipo' => 'Comercial'])->assertUnprocessable()->assertJsonValidationErrors(['tipo' => 'Tipo de atividade inválido: Comercial.']);
});

it('RN-43: inativo não é escolhido para novas atividades nem na troca, mas pode ser mantido na edição', function () {
    $mapeamento = tipoAtividadeId('Mapeamento');
    $atividadeId = ($this->atividade)(['tipo_id' => $mapeamento])->assertCreated()->json('id');

    Livewire::test(TiposDeAtividade::class)->call('alterarSituacao', $mapeamento, false)->assertSee('Tipo inativado.');

    ($this->atividade)(['tipo' => 'Mapeamento'])->assertUnprocessable()
        ->assertJsonPath('error', 'O tipo de atividade informado não existe ou está inativo.');
    $edicao = fn (string $tipoId) => $this->putJson("/api/v1/projetos/{$this->projetoId}/atividades/{$atividadeId}", [
        'descricao' => 'Corrigida', 'tipo_id' => $tipoId, 'data_entrada' => '2026-10-01', 'data_limite' => '2026-10-15',
        'account_manager_id' => $this->am->id, 'pre_vendas_id' => $this->pv->id,
    ]);
    $edicao($mapeamento)->assertOk(); // mantém o tipo inativo
    $edicao(tipoAtividadeId('Comercial'))->assertOk();
    $edicao($mapeamento)->assertUnprocessable(); // não volta para o inativo

    Livewire::test(DetalheProjeto::class, ['projetoId' => $this->projetoId])
        ->call('abrirFormulario')->assertViewHas('tipos', fn (array $tipos) => ! in_array($mapeamento, array_column($tipos, 'id'), true));
    Livewire::test(TodasAsAtividades::class)->assertSee('Mapeamento'); // o filtro ainda oferece o inativo

    Livewire::test(TiposDeAtividade::class)->call('alterarSituacao', $mapeamento, true)->assertSee('Tipo reativado.');
    ($this->atividade)(['tipo' => 'Mapeamento'])->assertCreated();
});

it('RN-43: sempre resta pelo menos um tipo ativo', function () {
    foreach (['Homologação', 'Implantação', 'Mapeamento'] as $nome) {
        Livewire::test(TiposDeAtividade::class)->call('alterarSituacao', tipoAtividadeId($nome), false)->assertHasNoErrors();
    }

    Livewire::test(TiposDeAtividade::class)->call('alterarSituacao', tipoAtividadeId('Comercial'), false)
        ->assertSee('É preciso manter pelo menos um tipo de atividade ativo.');
    $this->assertDatabaseHas('tipos_atividade', ['nome' => 'Comercial', 'ativo' => true]);
});
