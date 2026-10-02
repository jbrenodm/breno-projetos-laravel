<?php

declare(strict_types=1);

namespace App\Livewire\Parceiros;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Parceiros\Application\DTOs\CadastrarClienteInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\CadastrarCliente;

#[Title('Clientes')]
final class Clientes extends Component
{
    use ExecutaCasosDeUso;

    public string $razaoSocial = '';

    public string $nomeFantasia = '';

    public string $cnpj = '';

    public function salvar(CadastrarCliente $useCase): void
    {
        $this->validate([
            'razaoSocial' => ['required', 'string', 'max:255'],
            'nomeFantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
        ], attributes: ['razaoSocial' => 'razão social', 'nomeFantasia' => 'nome fantasia', 'cnpj' => 'CNPJ']);

        $ok = $this->executar(fn () => $useCase->execute(
            new CadastrarClienteInput($this->razaoSocial, $this->nomeFantasia ?: null, $this->cnpj ?: null)
        ));

        if ($ok) {
            $this->reset(['razaoSocial', 'nomeFantasia', 'cnpj']);
            session()->flash('sucesso', 'Cliente cadastrado.');
        }
    }

    public function render(ParceirosQuery $parceiros): View
    {
        return view('livewire.parceiros.clientes', ['clientes' => $parceiros->listarClientes()]);
    }
}
