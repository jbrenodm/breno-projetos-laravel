<?php

declare(strict_types=1);

use App\Livewire\Auth\EsqueciSenha;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RedefinirSenha;
use App\Livewire\Auth\TrocarSenhaObrigatoria;
use App\Livewire\Conta\MinhaConta;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->ana = User::factory()->create([
        'name' => 'Ana', 'email' => 'ana@breno.local', 'password' => 'Senha123',
    ]);
});

it('RN-34: telas redirecionam para o login e a API responde 401 sem token', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/dashboards/operacional')->assertRedirect('/login');
    $this->getJson('/api/v1/projetos')->assertUnauthorized();
    $this->get('/api/v1/projetos')->assertUnauthorized()->assertJsonStructure(['message']); // JSON mesmo sem Accept
    $this->get('/login')->assertOk()->assertSee('Entrar');
});

it('RN-33: entra com e-mail e senha (e-mail sem diferenciar maiúsculas) e volta à página pedida', function () {
    $this->get('/dashboards/prazos'); // guarda a URL pretendida

    Livewire::test(Login::class)
        ->set('email', 'ANA@Breno.Local')
        ->set('senha', 'Senha123')
        ->call('entrar')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboards/prazos');

    $this->assertAuthenticatedAs($this->ana);
});

it('RN-33: senha errada e usuário inativo recebem a mesma mensagem genérica', function () {
    Livewire::test(Login::class)->set('email', 'ana@breno.local')->set('senha', 'errada123')->call('entrar')
        ->assertHasErrors(['email'])->assertSee('E-mail ou senha inválidos.');

    $this->ana->update(['ativo' => false]);
    Livewire::test(Login::class)->set('email', 'ana@breno.local')->set('senha', 'Senha123')->call('entrar')
        ->assertSee('E-mail ou senha inválidos.');

    Livewire::test(Login::class)->set('email', 'ninguem@breno.local')->set('senha', 'Senha123')->call('entrar')
        ->assertSee('E-mail ou senha inválidos.');

    $this->assertGuest();
});

it('RN-33: bloqueia após 5 tentativas erradas por minuto', function () {
    foreach (range(1, 5) as $_) {
        Livewire::test(Login::class)->set('email', 'ana@breno.local')->set('senha', 'errada123')->call('entrar');
    }

    Livewire::test(Login::class)->set('email', 'ana@breno.local')->set('senha', 'Senha123')->call('entrar')
        ->assertSee('Muitas tentativas');
    $this->assertGuest();
});

it('RN-33: sair encerra a sessão', function () {
    $this->actingAs($this->ana)->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
    $this->get('/')->assertRedirect('/login');
});

it('RN-33: usuário inativado com a sessão aberta é desconectado na próxima requisição', function () {
    $this->actingAs($this->ana)->get('/')->assertOk();

    $this->ana->update(['ativo' => false]);

    $this->get('/clientes')->assertRedirect('/login');
    $this->assertGuest();
});

it('RN-36: com senha temporária, só acessa a troca de senha (telas e API)', function () {
    $this->ana->update(['deve_trocar_senha' => true]);
    $this->actingAs($this->ana);

    $this->get('/')->assertRedirect('/trocar-senha');
    $this->get('/clientes')->assertRedirect('/trocar-senha');
    $this->get('/trocar-senha')->assertOk()->assertSee('Crie a sua senha');
    $this->getJson('/api/v1/projetos', ['Authorization' => 'Bearer '.$this->ana->createToken('x')->plainTextToken])->assertForbidden();

    Livewire::test(TrocarSenhaObrigatoria::class)
        ->set('senhaAtual', 'Senha123')->set('novaSenha', 'Senha123')->set('novaSenha_confirmation', 'Senha123')
        ->call('salvar')->assertSee('A nova senha deve ser diferente da atual.');

    Livewire::test(TrocarSenhaObrigatoria::class)
        ->set('senhaAtual', 'Senha123')->set('novaSenha', 'NovaSenha456')->set('novaSenha_confirmation', 'NovaSenha456')
        ->call('salvar')->assertHasNoErrors()->assertRedirect('/');

    expect($this->ana->fresh()->deve_trocar_senha)->toBeFalse()
        ->and(Hash::check('NovaSenha456', $this->ana->fresh()->password))->toBeTrue();
    $this->actingAs($this->ana->fresh())->get('/')->assertOk(); // numa requisição real o usuário é relido do banco
});

it('RN-37: esqueci minha senha envia link só para usuário ativo e não revela se o e-mail existe', function () {
    Notification::fake();
    $inativo = User::factory()->create(['email' => 'inativo@breno.local', 'ativo' => false]);

    foreach (['ana@breno.local', 'inativo@breno.local', 'ninguem@breno.local'] as $email) {
        Livewire::test(EsqueciSenha::class)->set('email', $email)->call('enviar')
            ->assertHasNoErrors()->assertSee('Se o e-mail estiver cadastrado e ativo');
    }

    Notification::assertSentTo($this->ana, ResetPassword::class);
    Notification::assertNotSentTo($inativo, ResetPassword::class);
    Notification::assertCount(1);
});

it('RN-37/RN-38: redefine a senha pelo link; link inválido e senha fraca são rejeitados', function () {
    $this->ana->update(['deve_trocar_senha' => true]);
    $token = Password::createToken($this->ana);

    Livewire::test(RedefinirSenha::class, ['token' => 'token-errado'])
        ->set('email', 'ana@breno.local')->set('senha', 'NovaSenha456')->set('senha_confirmation', 'NovaSenha456')
        ->call('salvar')->assertSee('Link inválido ou expirado');

    Livewire::test(RedefinirSenha::class, ['token' => $token])
        ->set('email', 'ana@breno.local')->set('senha', 'fraca')->set('senha_confirmation', 'fraca')
        ->call('salvar')->assertSee('mínimo 8 caracteres');

    Livewire::withQueryParams(['email' => 'ana@breno.local'])
        ->test(RedefinirSenha::class, ['token' => $token])
        ->assertSet('email', 'ana@breno.local')
        ->set('senha', 'NovaSenha456')->set('senha_confirmation', 'NovaSenha456')
        ->call('salvar')->assertHasNoErrors()->assertRedirect('/login');

    expect(Hash::check('NovaSenha456', $this->ana->fresh()->password))->toBeTrue()
        ->and($this->ana->fresh()->deve_trocar_senha)->toBeFalse();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'ana@breno.local']); // link de uso único
});

it('RN-34/RN-40: gera token de API, usa na API e revoga', function () {
    $this->actingAs($this->ana);

    $tela = Livewire::test(MinhaConta::class)->set('nomeDoToken', 'Integração CRM')->call('gerarToken')->assertHasNoErrors();
    $token = $tela->get('tokenGerado');
    expect($token)->toBeString()->not->toBeEmpty();
    $tela->assertSee(['Copie agora', 'Integração CRM', 'nunca usado']);

    auth()->forgetGuards(); // a partir daqui, só o token autentica
    $this->getJson('/api/v1/projetos', ['Authorization' => "Bearer {$token}"])->assertOk();

    $this->actingAs($this->ana);
    $tokenId = (string) DB::table('personal_access_tokens')->value('id');
    Livewire::test(MinhaConta::class)->call('revogarToken', $tokenId)->assertHasNoErrors()->assertSee('Nenhum token criado.');

    auth()->forgetGuards();
    $this->getJson('/api/v1/projetos', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
});

it('RN-27/RN-40: não revoga token de outro usuário', function () {
    $outro = User::factory()->create();
    $outro->createToken('dele');
    $tokenId = (string) DB::table('personal_access_tokens')->value('id');

    $this->actingAs($this->ana);
    Livewire::test(MinhaConta::class)->call('revogarToken', $tokenId)->assertSee('Registro não encontrado.');
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('RN-40: troca a própria senha em Minha conta exigindo a senha atual', function () {
    $this->actingAs($this->ana);

    Livewire::test(MinhaConta::class)
        ->set('senhaAtual', 'errada123')->set('novaSenha', 'NovaSenha456')->set('novaSenha_confirmation', 'NovaSenha456')
        ->call('salvarSenha')->assertSee('A senha atual não confere.');

    Livewire::test(MinhaConta::class)
        ->set('senhaAtual', 'Senha123')->set('novaSenha', 'NovaSenha456')->set('novaSenha_confirmation', 'Outra456')
        ->call('salvarSenha')->assertHasErrors(['novaSenha']);

    Livewire::test(MinhaConta::class)
        ->set('senhaAtual', 'Senha123')->set('novaSenha', 'NovaSenha456')->set('novaSenha_confirmation', 'NovaSenha456')
        ->call('salvarSenha')->assertHasNoErrors()->assertSee('Senha alterada.');

    expect(Hash::check('NovaSenha456', $this->ana->fresh()->password))->toBeTrue();
});
