<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Projetos\Application\DTOs\AlterarSituacaoTipoAtividadeInput;
use Src\Projetos\Application\DTOs\CadastrarTipoAtividadeInput;
use Src\Projetos\Application\DTOs\RenomearTipoAtividadeInput;
use Src\Projetos\Application\Queries\TiposAtividadeQuery;
use Src\Projetos\Application\UseCases\AlterarSituacaoTipoAtividade;
use Src\Projetos\Application\UseCases\CadastrarTipoAtividade;
use Src\Projetos\Application\UseCases\RenomearTipoAtividade;
use Src\Projetos\Domain\Entities\TipoAtividade;

/** RN-43: tipos de atividade (só Admin Geral do Sistema — a rota usa can:admin-geral e os casos de uso verificam de novo). */
#[Title('Tipos de atividade')]
final class TiposDeAtividade extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public ?string $editandoId = null;

    public string $nome = '';

    public function salvar(CadastrarTipoAtividade $cadastrar, RenomearTipoAtividade $renomear): void
    {
        $this->validate(['nome' => ['required', 'string', 'max:'.TipoAtividade::TAMANHO_MAXIMO_NOME]], attributes: ['nome' => 'nome']);

        $editando = $this->editandoId !== null;
        $executor = (string) auth()->id();

        $ok = $this->executar(fn () => $editando
            ? $renomear->execute(new RenomearTipoAtividadeInput($executor, $this->editandoId, $this->nome))
            : $cadastrar->execute(new CadastrarTipoAtividadeInput($executor, $this->nome)), 'nome');

        if ($ok) {
            session()->flash('sucesso', $editando ? 'Tipo renomeado: o novo nome já vale em todas as atividades.' : 'Tipo de atividade cadastrado.');
            $this->cancelarEdicao();
        }
    }

    public function editar(string $tipoId, TiposAtividadeQuery $tipos): void
    {
        $tipo = collect($tipos->listarTodos())->firstWhere('id', $tipoId);

        if ($tipo === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->fill(['editandoId' => $tipo['id'], 'nome' => $tipo['nome']]);
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'nome']);
        $this->resetErrorBag();
    }

    public function alterarSituacao(string $tipoId, bool $ativo, AlterarSituacaoTipoAtividade $useCase): void
    {
        if ($this->executar(fn () => $useCase->execute(new AlterarSituacaoTipoAtividadeInput((string) auth()->id(), $tipoId, $ativo)))) {
            session()->flash('sucesso', $ativo ? 'Tipo reativado.' : 'Tipo inativado.');
        }
    }

    public function render(TiposAtividadeQuery $tipos): View
    {
        return view('livewire.admin.tipos-de-atividade', ['tipos' => $tipos->listarTodos()]);
    }
}
