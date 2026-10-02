<?php

declare(strict_types=1);

namespace App\Livewire\Parceiros;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Parceiros\Application\DTOs\AdicionarSolucaoInput;
use Src\Parceiros\Application\DTOs\AlterarSituacaoInput;
use Src\Parceiros\Application\DTOs\AlterarSituacaoSolucaoInput;
use Src\Parceiros\Application\DTOs\CadastrarFornecedorInput;
use Src\Parceiros\Application\DTOs\EditarFornecedorInput;
use Src\Parceiros\Application\DTOs\EditarSolucaoInput;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Parceiros\Application\UseCases\AdicionarSolucao;
use Src\Parceiros\Application\UseCases\AlterarSituacaoFornecedor;
use Src\Parceiros\Application\UseCases\AlterarSituacaoSolucao;
use Src\Parceiros\Application\UseCases\CadastrarFornecedor;
use Src\Parceiros\Application\UseCases\EditarFornecedor;
use Src\Parceiros\Application\UseCases\EditarSolucao;

#[Title('Fornecedores')]
final class Fornecedores extends Component
{
    use ExecutaCasosDeUso;

    /** Preenchido quando o formulário está editando um fornecedor existente (RN-30). */
    #[Locked]
    public ?string $editandoId = null;

    public string $razaoSocial = '';

    public string $nomeFantasia = '';

    public string $cnpj = '';

    /** Uma solução por linha (opcional, só no cadastro). */
    public string $solucoes = '';

    /** @var array<string, string> fornecedorId => nome da nova solução */
    public array $novaSolucao = [];

    // Edição inline de uma solução (RN-32)
    #[Locked]
    public ?string $solucaoEditandoId = null;

    #[Locked]
    public ?string $fornecedorDaSolucaoId = null;

    public string $solucaoNome = '';

    public string $solucaoDescricao = '';

    public function salvar(CadastrarFornecedor $cadastrar, EditarFornecedor $editar): void
    {
        $this->validate([
            'razaoSocial' => ['required', 'string', 'max:255'],
            'nomeFantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'solucoes' => ['nullable', 'string', 'max:5000'],
        ], attributes: ['razaoSocial' => 'razão social', 'nomeFantasia' => 'nome fantasia', 'cnpj' => 'CNPJ']);

        $editando = $this->editandoId !== null;
        $nomes = array_values(array_filter(array_map('trim', preg_split('/\R/', $this->solucoes) ?: [])));

        $ok = $this->executar(fn () => $editando
            ? $editar->execute(new EditarFornecedorInput($this->editandoId, $this->razaoSocial, $this->nomeFantasia ?: null, $this->cnpj ?: null))
            : $cadastrar->execute(new CadastrarFornecedorInput($this->razaoSocial, $this->nomeFantasia ?: null, $this->cnpj ?: null, $nomes)));

        if ($ok) {
            $this->cancelarEdicao();
            session()->flash('sucesso', $editando ? 'Fornecedor atualizado.' : 'Fornecedor cadastrado.');
        }
    }

    public function editar(string $fornecedorId, ParceirosQuery $parceiros): void
    {
        $fornecedor = collect($parceiros->listarFornecedores())->firstWhere('id', $fornecedorId);

        if ($fornecedor === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->fill([
            'editandoId' => $fornecedor['id'],
            'razaoSocial' => $fornecedor['razao_social'],
            'nomeFantasia' => $fornecedor['nome_fantasia'] ?? '',
            'cnpj' => $fornecedor['cnpj'] ?? '',
            'solucoes' => '',
        ]);
    }

    public function cancelarEdicao(): void
    {
        $this->reset(['editandoId', 'razaoSocial', 'nomeFantasia', 'cnpj', 'solucoes']);
        $this->resetErrorBag();
    }

    /** RN-31 */
    public function alterarSituacao(string $fornecedorId, bool $ativo, AlterarSituacaoFornecedor $useCase): void
    {
        if ($this->executar(fn () => $useCase->execute(new AlterarSituacaoInput($fornecedorId, $ativo)))) {
            session()->flash('sucesso', $ativo ? 'Fornecedor reativado.' : 'Fornecedor inativado.');
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

    public function editarSolucao(string $fornecedorId, string $solucaoId, ParceirosQuery $parceiros): void
    {
        $fornecedor = collect($parceiros->listarFornecedores())->firstWhere('id', $fornecedorId);
        $solucao = collect($fornecedor['solucoes'] ?? [])->firstWhere('id', $solucaoId);

        if ($solucao === null) {
            $this->addError("solucao.$fornecedorId", 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->fill([
            'fornecedorDaSolucaoId' => $fornecedorId,
            'solucaoEditandoId' => $solucaoId,
            'solucaoNome' => $solucao['nome'],
            'solucaoDescricao' => $solucao['descricao'] ?? '',
        ]);
    }

    public function cancelarEdicaoDeSolucao(): void
    {
        $this->reset(['fornecedorDaSolucaoId', 'solucaoEditandoId', 'solucaoNome', 'solucaoDescricao']);
    }

    /** RN-32 */
    public function salvarSolucao(EditarSolucao $useCase): void
    {
        if ($this->solucaoEditandoId === null) {
            return;
        }

        $this->validate([
            'solucaoNome' => ['required', 'string', 'max:255'],
            'solucaoDescricao' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['solucaoNome' => 'nome da solução', 'solucaoDescricao' => 'descrição']);

        $ok = $this->executar(fn () => $useCase->execute(new EditarSolucaoInput(
            $this->fornecedorDaSolucaoId, $this->solucaoEditandoId, $this->solucaoNome, $this->solucaoDescricao ?: null,
        )), "solucao.{$this->fornecedorDaSolucaoId}");

        if ($ok) {
            $this->cancelarEdicaoDeSolucao();
        }
    }

    /** RN-31 */
    public function alterarSituacaoSolucao(string $fornecedorId, string $solucaoId, bool $ativa, AlterarSituacaoSolucao $useCase): void
    {
        $this->executar(
            fn () => $useCase->execute(new AlterarSituacaoSolucaoInput($fornecedorId, $solucaoId, $ativa)),
            "solucao.$fornecedorId",
        );
    }

    public function render(ParceirosQuery $parceiros): View
    {
        return view('livewire.parceiros.fornecedores', ['fornecedores' => $parceiros->listarFornecedores()]);
    }
}
