<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Identidade\Application\DTOs\AlterarSituacaoUsuarioInput;
use Src\Identidade\Application\DTOs\CadastrarUsuarioInput;
use Src\Identidade\Application\DTOs\EditarUsuarioInput;
use Src\Identidade\Application\DTOs\RedefinirSenhaTemporariaInput;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Application\UseCases\AlterarSituacaoUsuario;
use Src\Identidade\Application\UseCases\CadastrarUsuario;
use Src\Identidade\Application\UseCases\EditarUsuario;
use Src\Identidade\Application\UseCases\RedefinirSenhaTemporaria;
use Src\Identidade\Domain\Papel;

/** RN-35/RN-36/RN-39: gestão de usuários (só Admin Geral do Sistema — a rota usa can:admin-geral e os casos de uso verificam de novo). */
#[Title('Usuários')]
final class Usuarios extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public ?string $editandoId = null;

    public string $nome = '';

    public string $email = '';

    /** @var list<string> */
    public array $papeis = [];

    public string $senhaTemporaria = '';

    // Redefinição de senha temporária de um usuário existente
    #[Locked]
    public ?string $redefinindoSenhaDe = null;

    public string $novaSenhaTemporaria = '';

    public function salvar(CadastrarUsuario $cadastrar, EditarUsuario $editar): void
    {
        $editando = $this->editandoId !== null;

        $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'papeis' => ['required', 'array', 'min:1'],
            'papeis.*' => [Rule::enum(Papel::class)],
            'senhaTemporaria' => $editando ? ['nullable'] : ['required', 'string', 'max:255'],
        ], attributes: ['nome' => 'nome', 'email' => 'e-mail', 'papeis' => 'papéis', 'senhaTemporaria' => 'senha temporária']);

        $executor = (string) auth()->id();
        $ok = $this->executar(fn () => $editando
            ? $editar->execute(new EditarUsuarioInput($executor, $this->editandoId, $this->nome, $this->email, array_values($this->papeis)))
            : $cadastrar->execute(new CadastrarUsuarioInput($executor, $this->nome, $this->email, array_values($this->papeis), $this->senhaTemporaria)));

        if ($ok) {
            session()->flash('sucesso', $editando
                ? 'Usuário atualizado.'
                : "Usuário cadastrado. Informe a senha temporária a {$this->nome}: ela deverá ser trocada no primeiro acesso.");
            $this->cancelarEdicao();
        }
    }

    public function editar(string $usuarioId, UsuariosQuery $usuarios): void
    {
        $usuario = collect($usuarios->listarTodos())->firstWhere('id', $usuarioId);

        if ($usuario === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->cancelarRedefinicaoDeSenha();
        $this->fill([
            'editandoId' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'papeis' => $usuario['papeis'],
            'senhaTemporaria' => '',
        ]);
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'nome', 'email', 'papeis', 'senhaTemporaria']);
        $this->resetErrorBag();
    }

    /** Sugere uma senha temporária que atende à RN-38. */
    public function gerarSenha(string $campo = 'senhaTemporaria'): void
    {
        if (in_array($campo, ['senhaTemporaria', 'novaSenhaTemporaria'], true)) {
            $this->{$campo} = Str::password(12, symbols: false);
        }
    }

    public function alterarSituacao(string $usuarioId, bool $ativo, AlterarSituacaoUsuario $useCase): void
    {
        if ($this->executar(fn () => $useCase->execute(new AlterarSituacaoUsuarioInput((string) auth()->id(), $usuarioId, $ativo)))) {
            session()->flash('sucesso', $ativo ? 'Usuário reativado.' : 'Usuário inativado: sessões e tokens dele foram encerrados.');
        }
    }

    public function prepararRedefinicaoDeSenha(string $usuarioId): void
    {
        $this->resetErrorBag();
        $this->redefinindoSenhaDe = $usuarioId;
        $this->novaSenhaTemporaria = '';
    }

    public function cancelarRedefinicaoDeSenha(): void
    {
        $this->reset(['redefinindoSenhaDe', 'novaSenhaTemporaria']);
    }

    public function redefinirSenha(RedefinirSenhaTemporaria $useCase): void
    {
        if ($this->redefinindoSenhaDe === null) {
            return;
        }

        $this->validate(['novaSenhaTemporaria' => ['required', 'string', 'max:255']], attributes: ['novaSenhaTemporaria' => 'senha temporária']);

        $ok = $this->executar(fn () => $useCase->execute(new RedefinirSenhaTemporariaInput(
            (string) auth()->id(), $this->redefinindoSenhaDe, $this->novaSenhaTemporaria,
        )), 'novaSenhaTemporaria');

        if ($ok) {
            session()->flash('sucesso', 'Senha temporária definida: o usuário deverá trocá-la no próximo acesso.');
            $this->cancelarRedefinicaoDeSenha();
        }
    }

    public function render(UsuariosQuery $usuarios): View
    {
        return view('livewire.admin.usuarios', [
            'usuarios' => $usuarios->listarTodos(),
            'todosPapeis' => Papel::cases(),
        ]);
    }
}
