<?php

declare(strict_types=1);

namespace App\Livewire\Parceiros;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Parceiros\Application\DTOs\AdicionarSolucaoInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\AdicionarSolucao;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;

#[Title('Fornecedores')]
final class Fornecedores extends Component
{
    use ExecutaCasosDeUso;

    public string $razaoSocial = '';

    public string $nomeFantasia = '';

    public string $cnpj = '';

    /** Uma solução por linha (opcional). */
    public string $solucoes = '';

    /** @var array<string, string> fornecedorId => nome da nova solução */
    public array $novaSolucao = [];

    public function salvar(CadastrarFornecedor $useCase): void
    {
        $this->validate([
            'razaoSocial' => ['required', 'string', 'max:255'],
            'nomeFantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'solucoes' => ['nullable', 'string', 'max:5000'],
        ], attributes: ['razaoSocial' => 'razão social', 'nomeFantasia' => 'nome fantasia', 'cnpj' => 'CNPJ']);

        $nomes = array_values(array_filter(array_map('trim', preg_split('/\R/', $this->solucoes) ?: [])));

        $ok = $this->executar(fn () => $useCase->execute(
            new CadastrarFornecedorInput($this->razaoSocial, $this->nomeFantasia ?: null, $this->cnpj ?: null, $nomes)
        ));

        if ($ok) {
            $this->reset(['razaoSocial', 'nomeFantasia', 'cnpj', 'solucoes']);
            session()->flash('sucesso', 'Fornecedor cadastrado.');
        }
    }

    public function adicionarSolucao(string $fornecedorId, AdicionarSolucao $useCase): void
    {
        $nome = trim($this->novaSolucao[$fornecedorId] ?? '');
        if ($nome === '') {
            return;
        }

        if ($this->executar(fn () => $useCase->execute(new AdicionarSolucaoInput($fornecedorId, $nome)), "solucao.$fornecedorId")) {
            unset($this->novaSolucao[$fornecedorId]);
        }
    }

    public function render(ParceirosQuery $parceiros): View
    {
        return view('livewire.parceiros.fornecedores', ['fornecedores' => $parceiros->listarFornecedores()]);
    }
}
