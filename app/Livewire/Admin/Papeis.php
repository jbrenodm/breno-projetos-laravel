<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Identidade\Application\DTOs\RenomearPapelInput;
use Src\Identidade\Application\Queries\PapeisQuery;
use Src\Identidade\Application\UseCases\RenomearPapel;
use Src\Identidade\Domain\Papel;

/** RN-41: renomear papéis (só Admin Geral do Sistema — a rota usa can:admin-geral e o caso de uso verifica de novo). */
#[Title('Papéis')]
final class Papeis extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public ?string $editando = null;

    public string $nome = '';

    public string $sigla = '';

    public function editar(string $papel, PapeisQuery $papeis): void
    {
        $definicao = $papeis->nomes()[$papel] ?? null;

        if ($definicao === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->fill(['editando' => $papel, 'nome' => $definicao['nome'], 'sigla' => $definicao['sigla'] ?? '']);
    }

    public function cancelar(): void
    {
        $this->reset(['editando', 'nome', 'sigla']);
        $this->resetErrorBag();
    }

    public function salvar(RenomearPapel $useCase): void
    {
        $this->validate([
            'editando' => ['required', Rule::enum(Papel::class)],
            'nome' => ['required', 'string', 'max:60'],
            'sigla' => ['nullable', 'string', 'max:10'],
        ], attributes: ['nome' => 'nome', 'sigla' => 'sigla']);

        $ok = $this->executar(fn () => $useCase->execute(
            new RenomearPapelInput((string) auth()->id(), $this->editando, $this->nome, $this->sigla ?: null),
        ), 'nome');

        if ($ok) {
            session()->flash('sucesso', 'Papel renomeado. O novo nome já vale em todo o sistema.');
            $this->cancelar();
        }
    }

    public function render(PapeisQuery $papeis): View
    {
        return view('livewire.admin.papeis', ['papeis' => $papeis->listar()]);
    }
}
