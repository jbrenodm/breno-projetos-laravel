<?php

declare(strict_types=1);

namespace App\Livewire\Conta;

use App\Livewire\Concerns\TrocaSenha;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Identidade\Application\DTOs\TokenDeApiInput;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Application\UseCases\GerarTokenDeApi;
use Src\Identidade\Application\UseCases\RevogarTokenDeApi;
use Src\Identidade\Application\UseCases\TrocarSenha;

/** RN-34/RN-40: troca da própria senha e tokens de API. */
#[Title('Minha conta')]
final class MinhaConta extends Component
{
    use TrocaSenha;

    public string $nomeDoToken = '';

    /** Exibido uma única vez, logo após gerar. */
    public ?string $tokenGerado = null;

    public function salvarSenha(TrocarSenha $useCase): void
    {
        if ($this->trocarPropriaSenha($useCase)) {
            session()->flash('sucesso', 'Senha alterada.');
        }
    }

    public function gerarToken(GerarTokenDeApi $useCase): void
    {
        $this->validate(['nomeDoToken' => ['required', 'string', 'max:100']], attributes: ['nomeDoToken' => 'nome do token']);

        $this->executar(function () use ($useCase): void {
            $this->tokenGerado = $useCase->execute(new TokenDeApiInput((string) auth()->id(), $this->nomeDoToken));
            $this->reset('nomeDoToken');
        }, 'nomeDoToken');
    }

    public function revogarToken(string $tokenId, RevogarTokenDeApi $useCase): void
    {
        if ($this->executar(fn () => $useCase->execute(new TokenDeApiInput((string) auth()->id(), $tokenId)), 'tokens')) {
            $this->tokenGerado = null;
            session()->flash('sucesso', 'Token revogado.');
        }
    }

    public function render(UsuariosQuery $usuarios): View
    {
        return view('livewire.conta.minha-conta', ['tokens' => $usuarios->tokensDe((string) auth()->id())]);
    }
}
