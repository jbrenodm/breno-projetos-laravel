<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Responsaveis\Application\DTOs\AlterarSituacaoResponsavelInput;
use Src\Responsaveis\Application\DTOs\CadastrarResponsavelInput;
use Src\Responsaveis\Application\DTOs\EditarResponsavelInput;
use Src\Responsaveis\Application\Queries\ResponsaveisQuery;
use Src\Responsaveis\Application\UseCases\AlterarSituacaoResponsavel;
use Src\Responsaveis\Application\UseCases\CadastrarResponsavel;
use Src\Responsaveis\Application\UseCases\EditarResponsavel;
use Src\Responsaveis\Domain\Funcao;

/** RN-42: AM e PV (só Admin Geral do Sistema — a rota usa can:admin-geral e os casos de uso verificam de novo). */
#[Title('Responsáveis')]
final class Responsaveis extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public ?string $editandoId = null;

    public string $nome = '';

    public string $email = '';

    /** @var list<string> */
    public array $funcoes = [];

    public function salvar(CadastrarResponsavel $cadastrar, EditarResponsavel $editar): void
    {
        $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'funcoes' => ['required', 'array', 'min:1'],
            'funcoes.*' => [Rule::enum(Funcao::class)],
        ], attributes: ['nome' => 'nome', 'email' => 'e-mail', 'funcoes' => 'funções']);

        $editando = $this->editandoId !== null;
        $executor = (string) auth()->id();
        $email = $this->email ?: null;

        $ok = $this->executar(fn () => $editando
            ? $editar->execute(new EditarResponsavelInput($executor, $this->editandoId, $this->nome, $email, array_values($this->funcoes)))
            : $cadastrar->execute(new CadastrarResponsavelInput($executor, $this->nome, $email, array_values($this->funcoes))));

        if ($ok) {
            session()->flash('sucesso', $editando ? 'Responsável atualizado.' : 'Responsável cadastrado.');
            $this->cancelarEdicao();
        }
    }

    public function editar(string $responsavelId, ResponsaveisQuery $responsaveis): void
    {
        $responsavel = collect($responsaveis->listarTodos())->firstWhere('id', $responsavelId);

        if ($responsavel === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->fill([
            'editandoId' => $responsavel['id'],
            'nome' => $responsavel['nome'],
            'email' => $responsavel['email'] ?? '',
            'funcoes' => $responsavel['funcoes'],
        ]);
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'nome', 'email', 'funcoes']);
        $this->resetErrorBag();
    }

    public function alterarSituacao(string $responsavelId, bool $ativo, AlterarSituacaoResponsavel $useCase): void
    {
        if ($this->executar(fn () => $useCase->execute(new AlterarSituacaoResponsavelInput((string) auth()->id(), $responsavelId, $ativo)))) {
            session()->flash('sucesso', $ativo ? 'Responsável reativado.' : 'Responsável inativado.');
        }
    }

    public function render(ResponsaveisQuery $responsaveis): View
    {
        return view('livewire.admin.responsaveis', [
            'responsaveis' => $responsaveis->listarTodos(),
            'todasFuncoes' => Funcao::cases(),
        ]);
    }
}
