<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** RN-37: envia o link de redefinição; a resposta é sempre a mesma (não revela se o e-mail existe). */
#[Layout('layouts.visitante')]
#[Title('Esqueci minha senha')]
final class EsqueciSenha extends Component
{
    public string $email = '';

    public bool $enviado = false;

    public function enviar(): void
    {
        $this->validate(['email' => ['required', 'string', 'email', 'max:255']], attributes: ['email' => 'e-mail']);

        $chave = 'esqueci-senha:'.request()->ip();
        if (RateLimiter::tooManyAttempts($chave, 3)) {
            $this->addError('email', 'Muitas solicitações. Tente de novo em '.RateLimiter::availableIn($chave).' segundos.');

            return;
        }
        RateLimiter::hit($chave, 60);

        // Só usuários ativos recebem o link; o resultado é ignorado de propósito.
        Password::sendResetLink(['email' => Str::lower(trim($this->email)), 'ativo' => true]);

        $this->enviado = true;
    }

    public function render(): View
    {
        return view('livewire.auth.esqueci-senha');
    }
}
