<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** RN-33: login por e-mail e senha; inativo não entra; 5 tentativas por minuto por e-mail + IP. */
#[Layout('layouts.visitante')]
#[Title('Entrar')]
final class Login extends Component
{
    private const TENTATIVAS_POR_MINUTO = 5;

    public string $email = '';

    public string $senha = '';

    public bool $lembrar = false;

    public function entrar(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'senha' => ['required', 'string', 'max:255'],
        ], attributes: ['email' => 'e-mail', 'senha' => 'senha']);

        $email = Str::lower(trim($this->email));
        $chave = 'login:'.$email.'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($chave, self::TENTATIVAS_POR_MINUTO)) {
            $this->addError('email', 'Muitas tentativas. Tente de novo em '.RateLimiter::availableIn($chave).' segundos.');

            return;
        }

        if (! Auth::attempt(['email' => $email, 'password' => $this->senha, 'ativo' => true], $this->lembrar)) {
            RateLimiter::hit($chave, 60);
            $this->reset('senha');
            $this->addError('email', 'E-mail ou senha inválidos.'); // genérica: não revela se o e-mail existe

            return;
        }

        RateLimiter::clear($chave);
        session()->regenerate(); // evita fixação de sessão

        $this->redirectIntended(route('projetos.index'));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
