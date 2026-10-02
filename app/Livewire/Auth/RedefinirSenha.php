<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Identidade\Application\DTOs\RedefinirSenhaPorLinkInput;
use Src\Identidade\Application\UseCases\RedefinirSenhaPorLink;

/** RN-37/RN-38: o broker do Laravel valida o token do link; a nova senha é gravada pelo caso de uso. */
#[Layout('layouts.visitante')]
#[Title('Redefinir senha')]
final class RedefinirSenha extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $senha = '';

    public string $senha_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function salvar(RedefinirSenhaPorLink $useCase): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'senha' => ['required', 'string', 'confirmed', 'max:255'],
        ], attributes: ['email' => 'e-mail', 'senha' => 'nova senha']);

        $status = null;
        $ok = $this->executar(function () use ($useCase, &$status): void {
            $status = Password::reset(
                ['email' => Str::lower(trim($this->email)), 'ativo' => true, 'token' => $this->token,
                    'password' => $this->senha, 'password_confirmation' => $this->senha_confirmation],
                fn (User $user, string $senha) => $useCase->execute(new RedefinirSenhaPorLinkInput($user->id, $senha)),
            );
        }, 'senha');

        if (! $ok) {
            return;
        }

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', 'Link inválido ou expirado. Peça um novo em "Esqueci minha senha".');

            return;
        }

        session()->flash('sucesso', 'Senha redefinida. Entre com a nova senha.');
        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.auth.redefinir-senha');
    }
}
