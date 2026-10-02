<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Src\Identidade\Application\DTOs\CadastrarUsuarioInput;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Application\UseCases\CadastrarUsuario;
use Src\Identidade\Domain\Papel;
use Src\Shared\Application\AcessoNegadoException;

beforeEach(function () {
    $this->admin = User::factory()->comPapel(Papel::ADMIN_GERAL)->create(['name' => 'Admin']);
    $this->ana = User::factory()->comPapel(Papel::ACCOUNT_MANAGER)->create(['name' => 'Ana', 'email' => 'ana@breno.local']);
});

it('RN-35: só o Admin Geral do Sistema acessa a gestão de usuários (rota, menu e caso de uso)', function () {
    $this->actingAs($this->ana)->get('/usuarios')->assertForbidden();
    $this->actingAs($this->ana)->get('/')->assertDontSee('href="'.route('usuarios.index').'"', false);

    expect(fn () => app(CadastrarUsuario::class)->execute(
        new CadastrarUsuarioInput($this->ana->id, 'X', 'x@x.com', ['pre_vendas'], 'Senha123')
    ))->toThrow(AcessoNegadoException::class);

    $this->actingAs($this->admin)->get('/usuarios')->assertOk()->assertSee(['Usuários', 'Ana']);
    $this->actingAs($this->admin)->get('/')->assertSee('href="'.route('usuarios.index').'"', false);
});

it('RN-35/RN-36: Admin cadastra usuário com senha temporária; e-mail é único', function () {
    $this->actingAs($this->admin);

    Livewire::test(Usuarios::class)
        ->set('nome', 'Paula PV')->set('email', 'Paula@Breno.Local')
        ->set('papeis', ['pre_vendas', 'account_manager'])->set('senhaTemporaria', 'Temp1234')
        ->call('salvar')->assertHasNoErrors()->assertSee(['Paula PV', 'paula@breno.local', 'Senha temporária']);

    $paula = User::query()->where('email', 'paula@breno.local')->firstOrFail();
    expect($paula->deve_trocar_senha)->toBeTrue()
        ->and(Hash::check('Temp1234', $paula->password))->toBeTrue()
        ->and($paula->roles->pluck('nome')->sort()->values()->all())->toBe(['account_manager', 'pre_vendas']);

    Livewire::test(Usuarios::class)
        ->set('nome', 'Outra')->set('email', 'ANA@breno.local')->set('papeis', ['pre_vendas'])->set('senhaTemporaria', 'Temp1234')
        ->call('salvar')->assertSee('Já existe um usuário com este e-mail.');

    Livewire::test(Usuarios::class)
        ->set('nome', 'Fraca')->set('email', 'fraca@breno.local')->set('papeis', ['pre_vendas'])->set('senhaTemporaria', 'abc')
        ->call('salvar')->assertSee('mínimo 8 caracteres');

    Livewire::test(Usuarios::class)
        ->set('nome', 'Sem papel')->set('email', 'sem@breno.local')->set('senhaTemporaria', 'Temp1234')
        ->call('salvar')->assertHasErrors(['papeis']);
});

it('RN-35: Admin edita nome, e-mail e papéis', function () {
    $this->actingAs($this->admin);

    Livewire::test(Usuarios::class)
        ->call('editar', $this->ana->id)
        ->assertSet('email', 'ana@breno.local')->assertSet('papeis', ['account_manager'])
        ->set('nome', 'Ana Souza')->set('papeis', ['account_manager', 'pre_vendas'])
        ->call('salvar')->assertHasNoErrors()->assertSee('Usuário atualizado.');

    expect($this->ana->fresh()->name)->toBe('Ana Souza')
        ->and($this->ana->fresh()->roles->pluck('nome')->sort()->values()->all())->toBe(['account_manager', 'pre_vendas']);
});

it('RN-35/RN-34: inativar desconecta e revoga tokens; usuário inativo some das listas de AM', function () {
    $this->ana->createToken('integração');
    DB::table('sessions')->insert(['id' => 'sessao-ana', 'user_id' => $this->ana->id, 'payload' => '', 'last_activity' => time()]);
    $this->actingAs($this->admin);

    Livewire::test(Usuarios::class)->call('alterarSituacao', $this->ana->id, false)->assertSee('Usuário inativado');

    expect($this->ana->fresh()->ativo)->toBeFalse();
    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->assertDatabaseMissing('sessions', ['id' => 'sessao-ana']);
    expect(app(UsuariosQuery::class)->listarAtivosPorPapel(Papel::ACCOUNT_MANAGER))->toBe([]);

    Livewire::test(Usuarios::class)->call('alterarSituacao', $this->ana->id, true);
    expect($this->ana->fresh()->ativo)->toBeTrue();
});

it('RN-39: não deixa o sistema sem Admin Geral do Sistema ativo', function () {
    $this->actingAs($this->admin);

    Livewire::test(Usuarios::class)->call('alterarSituacao', $this->admin->id, false)
        ->assertSee('O sistema precisa ter pelo menos um Admin Geral do Sistema ativo.');
    Livewire::test(Usuarios::class)->call('editar', $this->admin->id)->set('papeis', ['pre_vendas'])->call('salvar')
        ->assertSee('O sistema precisa ter pelo menos um Admin Geral do Sistema ativo.');
    expect($this->admin->fresh()->ehAdminGeral())->toBeTrue();

    // com um segundo Admin ativo, a operação é permitida
    User::factory()->comPapel(Papel::ADMIN_GERAL)->create();
    Livewire::test(Usuarios::class)->call('editar', $this->admin->id)->set('papeis', ['pre_vendas'])->call('salvar')->assertHasNoErrors();
    expect($this->admin->fresh()->ehAdminGeral())->toBeFalse();
});

it('RN-36: Admin redefine senha temporária e o usuário precisa trocá-la', function () {
    $this->actingAs($this->admin);

    Livewire::test(Usuarios::class)
        ->call('prepararRedefinicaoDeSenha', $this->ana->id)
        ->call('gerarSenha', 'novaSenhaTemporaria')
        ->tap(fn ($t) => expect($t->get('novaSenhaTemporaria'))->toHaveLength(12))
        ->set('novaSenhaTemporaria', 'Reset1234')
        ->call('redefinirSenha')->assertHasNoErrors()->assertSee('Senha temporária definida');

    expect($this->ana->fresh()->deve_trocar_senha)->toBeTrue()
        ->and(Hash::check('Reset1234', $this->ana->fresh()->password))->toBeTrue();
});

it('RN-27: ações de Admin pela tela são bloqueadas para quem não é Admin', function () {
    $this->actingAs($this->ana);

    Livewire::test(Usuarios::class)->call('alterarSituacao', $this->admin->id, false)
        ->assertSee('Somente o Admin Geral do Sistema pode realizar esta operação.');
    expect($this->admin->fresh()->ativo)->toBeTrue();
});

it('instalação: usuarios:criar-admin cria o primeiro Admin só quando não há nenhum Admin ativo', function () {
    $this->artisan('usuarios:criar-admin', ['email' => 'chefe@breno.local', 'nome' => 'Chefe'])
        ->expectsQuestion('Senha (mínimo 8 caracteres, com letras e números)', 'Chefe1234')
        ->expectsQuestion('Confirme a senha', 'Chefe1234')
        ->expectsOutputToContain('Já existe um Admin Geral do Sistema ativo')
        ->assertFailed();

    $this->admin->update(['ativo' => false]);

    $this->artisan('usuarios:criar-admin', ['email' => 'chefe@breno.local', 'nome' => 'Chefe'])
        ->expectsQuestion('Senha (mínimo 8 caracteres, com letras e números)', 'Chefe1234')
        ->expectsQuestion('Confirme a senha', 'Chefe1234')
        ->expectsOutputToContain('Admin Geral do Sistema criado')
        ->assertSuccessful();

    $chefe = User::query()->where('email', 'chefe@breno.local')->firstOrFail();
    expect($chefe->ehAdminGeral())->toBeTrue()->and($chefe->deve_trocar_senha)->toBeFalse();
});
