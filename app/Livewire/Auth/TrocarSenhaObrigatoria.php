<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Concerns\TrocaSenha;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Identidade\Application\UseCases\TrocarSenha;

/** RN-36: com senha temporária, o usuário precisa criar a própria senha antes de usar o sistema. */
#[Layout('layouts.visitante')]
#[Title('Crie a sua senha')]
final class TrocarSenhaObrigatoria extends Component
{
    use TrocaSenha;

    public function mount(): void
    {
        if (! auth()->user()->deve_trocar_senha) {
            $this->redirectRoute('projetos.index');
        }
    }

    public function salvar(TrocarSenha $useCase): void
    {
        if ($this->trocarPropriaSenha($useCase)) {
            session()->flash('sucesso', 'Senha criada. Bem-vindo!');
            $this->redirectRoute('projetos.index');
        }
    }

    public function render(): View
    {
        return view('livewire.auth.trocar-senha-obrigatoria');
    }
}
