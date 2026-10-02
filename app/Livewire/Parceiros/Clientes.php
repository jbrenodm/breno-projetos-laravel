<?php

declare(strict_types=1);

namespace App\Livewire\Parceiros;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Parceiros\Application\DTOs\AlterarSituacaoInput;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\DTOs\EditarClienteInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\AlterarSituacaoCliente;
use Src\Parceiros\Application\UseCases\CadastrarCliente;
use Src\Parceiros\Application\UseCases\EditarCliente;

#[Title('Clientes')]
final class Clientes extends Component
{
    use ExecutaCasosDeUso;

    /** Preenchido quando o formulário está editando um cliente existente (RN-30). */
    #[Locked]
    public ?string $editandoId = null;

    public string $razaoSocial = '';

    public string $nomeFantasia = '';

    public string $cnpj = '';

    public function salvar(CadastrarCliente $cadastrar, EditarCliente $editar): void
    {
        $this->validate([
            'razaoSocial' => ['required', 'string', 'max:255'],
            'nomeFantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
        ], attributes: ['razaoSocial' => 'razão social', 'nomeFantasia' => 'nome fantasia', 'cnpj' => 'CNPJ']);

        $editando = $this->editandoId !== null;

        $ok = $this->executar(fn () => $editando
            ? $editar->execute(new EditarClienteInput($this->editandoId, $this->razaoSocial, $this->nomeFantasia ?: null, $this->cnpj ?: null))
            : $cadastrar->execute(new CadastrarClienteInput($this->razaoSocial, $this->nomeFantasia ?: null, $this->cnpj ?: null)));

        if ($ok) {
            $this->cancelarEdicao();
            session()->flash('sucesso', $editando ? 'Cliente atualizado.' : 'Cliente cadastrado.');
        }
    }

    public function editar(string $clienteId, ParceirosQuery $parceiros): void
    {
        $cliente = collect($parceiros->listarClientes())->firstWhere('id', $clienteId);

        if ($cliente === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->fill([
            'editandoId' => $cliente['id'],
            'razaoSocial' => $cliente['razao_social'],
            'nomeFantasia' => $cliente['nome_fantasia'] ?? '',
            'cnpj' => $cliente['cnpj'] ?? '',
        ]);
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'razaoSocial', 'nomeFantasia', 'cnpj']);
        $this->resetErrorBag();
    }

    /** RN-31 */
    public function alterarSituacao(string $clienteId, bool $ativo, AlterarSituacaoCliente $useCase): void
    {
        if ($this->executar(fn () => $useCase->execute(new AlterarSituacaoInput($clienteId, $ativo)))) {
            session()->flash('sucesso', $ativo ? 'Cliente reativado.' : 'Cliente inativado.');
        }
    }

    public function render(ParceirosQuery $parceiros): View
    {
        return view('livewire.parceiros.clientes', ['clientes' => $parceiros->listarClientes()]);
    }
}
