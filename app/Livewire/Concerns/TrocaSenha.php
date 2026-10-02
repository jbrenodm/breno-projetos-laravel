<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Src\Identidade\Application\DTOs\TrocarSenhaInput;
use Src\Identidade\Application\UseCases\TrocarSenha;

/** Formulário de troca da própria senha (RN-36/RN-38/RN-40), usado na troca obrigatória e em "Minha conta". */
trait TrocaSenha
{
    use ExecutaCasosDeUso;

    public string $senhaAtual = '';

    public string $novaSenha = '';

    public string $novaSenha_confirmation = '';

    protected function trocarPropriaSenha(TrocarSenha $useCase): bool
    {
        $this->validate([
            'senhaAtual' => ['required', 'string', 'max:255'],
            'novaSenha' => ['required', 'string', 'confirmed', 'max:255'],
        ], attributes: ['senhaAtual' => 'senha atual', 'novaSenha' => 'nova senha']);

        $ok = $this->executar(
            fn () => $useCase->execute(new TrocarSenhaInput((string) auth()->id(), $this->senhaAtual, $this->novaSenha)),
            'novaSenha',
        );

        $this->reset(['senhaAtual', 'novaSenha', 'novaSenha_confirmation']);

        return $ok;
    }
}
