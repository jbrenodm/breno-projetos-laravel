<?php

declare(strict_types=1);

use App\Livewire\Admin\Responsaveis;
use App\Livewire\Projetos\DetalheProjeto;
use App\Models\User;
use Livewire\Livewire;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Src\Projetos\Application\UseCases\RegistrarNovaAtividade;
use Src\Projetos\Application\UseCases\RegistrarNovoProjeto;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Responsaveis\Application\DTOs\CadastrarResponsavelInput;
use Src\Responsaveis\Application\UseCases\CadastrarResponsavel;
use Src\Responsaveis\Domain\Funcao;
use Src\Responsaveis\Infrastructure\Persistence\ResponsavelModel;
use Src\Shared\Application\AcessoNegadoException;

// Feature/Telas: logado como Admin Geral do Sistema ($this->usuarioLogado — ver tests/Pest.php).

it('RN-42: só o Admin Geral do Sistema acessa o cadastro de responsáveis (rota, menu e caso de uso)', function () {
    $comum = User::factory()->create(['name' => 'Comum']);

    $this->get('/responsaveis')->assertOk()->assertSee('Responsáveis');
    $this->get('/')->assertSee('href="'.route('responsaveis.index').'"', false);

    $this->actingAs($comum)->get('/responsaveis')->assertForbidden();
    $this->actingAs($comum)->get('/')->assertDontSee('href="'.route('responsaveis.index').'"', false);
    expect(fn () => app(CadastrarResponsavel::class)->execute(new CadastrarResponsavelInput($comum->id, 'Ana', null, ['account_manager'])))
        ->toThrow(AcessoNegadoException::class);
    $this->assertDatabaseCount('responsaveis', 0);
});

it('RN-42: cadastra sem e-mail (vários), com e-mail único sem diferenciar maiúsculas e exige função', function () {
    Livewire::test(Responsaveis::class)
        ->set('nome', 'Ana')->set('funcoes', ['account_manager'])
        ->call('salvar')->assertHasNoErrors()->assertSee(['Ana', 'Account Manager', 'Responsável cadastrado.']);
    Livewire::test(Responsaveis::class)
        ->set('nome', 'Bruno')->set('funcoes', ['account_manager', 'pre_vendas'])
        ->call('salvar')->assertHasNoErrors();
    Livewire::test(Responsaveis::class)
        ->set('nome', 'Paula')->set('email', 'Paula@Empresa.com')->set('funcoes', ['pre_vendas'])
        ->call('salvar')->assertHasNoErrors()->assertSee('paula@empresa.com');

    $this->assertDatabaseCount('responsaveis', 3);
    $this->assertDatabaseHas('responsaveis', ['nome' => 'Ana', 'email' => null]);
    expect(ResponsavelModel::query()->where('nome', 'Bruno')->first()->funcoes->pluck('funcao')->sort()->values()->all())
        ->toBe(['account_manager', 'pre_vendas']);

    Livewire::test(Responsaveis::class)
        ->set('nome', 'Outra')->set('email', 'PAULA@empresa.com')->set('funcoes', ['pre_vendas'])
        ->call('salvar')->assertSee('Já existe um responsável com este e-mail.');
    Livewire::test(Responsaveis::class)
        ->set('nome', 'Sem função')->call('salvar')->assertHasErrors(['funcoes']);
    Livewire::test(Responsaveis::class)
        ->set('nome', 'E-mail ruim')->set('email', 'x@')->set('funcoes', ['pre_vendas'])->call('salvar')->assertHasErrors(['email']);
    $this->assertDatabaseCount('responsaveis', 3);
});

it('RN-42: edita nome, e-mail e funções (o e-mail pode ser removido)', function () {
    $ana = responsavel('Ana', Funcao::ACCOUNT_MANAGER);
    $ana->update(['email' => 'ana@empresa.com']);

    Livewire::test(Responsaveis::class)
        ->call('editar', $ana->id)
        ->assertSet('email', 'ana@empresa.com')->assertSet('funcoes', ['account_manager'])
        ->set('nome', 'Ana Souza')->set('email', '')->set('funcoes', ['pre_vendas'])
        ->call('salvar')->assertHasNoErrors()->assertSee('Responsável atualizado.');

    $ana->refresh();
    expect($ana->nome)->toBe('Ana Souza')
        ->and($ana->email)->toBeNull()
        ->and($ana->funcoes->pluck('funcao')->all())->toBe(['pre_vendas']);
});

it('RN-42: inativo some das listas de AM/PV e não pode ser escolhido; atividades existentes não mudam', function () {
    $clienteId = app(CadastrarCliente::class)->execute(new CadastrarClienteInput('Banco S.A.'));
    $fornecedorId = app(CadastrarFornecedor::class)->execute(new CadastrarFornecedorInput('Forn Ltda'));
    $projetoId = app(RegistrarNovoProjeto::class)->execute(new RegistrarProjetoInput($clienteId, [['fornecedor_id' => $fornecedorId]]));
    $ana = responsavel('Ana Inativa', Funcao::ACCOUNT_MANAGER);
    $pv = responsavel('Paulo', Funcao::PRE_VENDAS);
    $novaAtividade = fn () => app(RegistrarNovaAtividade::class)->execute(new RegistrarAtividadeInput(
        $projetoId, 'Levantamento', tipoAtividadeId('Comercial'), 'Não Iniciada', dia('2026-10-01'), dia('2026-10-15'),
        accountManagerId: $ana->id, preVendasId: $pv->id,
    ));
    $novaAtividade();

    Livewire::test(Responsaveis::class)->call('alterarSituacao', $ana->id, false)->assertSee('Responsável inativado.');

    expect($novaAtividade)->toThrow(ResponsavelObrigatorioException::class, 'O Account Manager informado não existe, está inativo ou não possui essa função.');
    Livewire::test(DetalheProjeto::class, ['projetoId' => $projetoId])
        ->assertSee('AM: Ana Inativa') // a atividade existente mantém o responsável
        ->call('abrirFormulario')
        ->assertViewHas('accountManagers', []);

    Livewire::test(Responsaveis::class)->call('alterarSituacao', $ana->id, true)->assertSee('Responsável reativado.');
    expect(ResponsavelModel::query()->find($ana->id)->ativo)->toBeTrue();
});
