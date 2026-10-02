<?php

declare(strict_types=1);

namespace App\Livewire\Projetos;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Application\UseCases\RegistrarNovoProjeto;

#[Title('Projetos')]
final class PainelProjetos extends Component
{
    use ExecutaCasosDeUso;

    public bool $mostrarFormulario = false;

    public string $clienteId = '';

    public string $codigoOportunidade = '';

    /** @var list<array{fornecedor_id: string, solucao_id: string}> RN-04: mínimo 1 */
    public array $vinculos = [['fornecedor_id' => '', 'solucao_id' => '']];

    public function abrirFormulario(): void
    {
        $this->resetErrorBag();
        $this->mostrarFormulario = true;
    }

    public function cancelarFormulario(): void
    {
        $this->reset(['mostrarFormulario', 'clienteId', 'codigoOportunidade', 'vinculos']);
        $this->resetErrorBag();
    }

    public function adicionarVinculo(): void
    {
        $this->vinculos[] = ['fornecedor_id' => '', 'solucao_id' => ''];
    }

    public function removerVinculo(int $indice): void
    {
        if (count($this->vinculos) > 1) {
            unset($this->vinculos[$indice]);
            $this->vinculos = array_values($this->vinculos);
        }
    }

    /** Ao trocar o fornecedor, a solução escolhida deixa de valer. */
    public function updatedVinculos(mixed $valor, string $chave): void
    {
        if (str_ends_with($chave, '.fornecedor_id')) {
            $indice = (int) explode('.', $chave)[0];
            $this->vinculos[$indice]['solucao_id'] = '';
        }
    }

    public function salvar(RegistrarNovoProjeto $registrarNovoProjeto): void
    {
        $this->validate([
            'clienteId' => ['required', 'uuid'],
            'codigoOportunidade' => ['nullable', 'string', 'max:50'],
            'vinculos' => ['required', 'array', 'min:1'],
            'vinculos.*.fornecedor_id' => ['required', 'uuid'],
            'vinculos.*.solucao_id' => ['nullable', 'uuid'],
        ], attributes: [
            'clienteId' => 'cliente',
            'vinculos.*.fornecedor_id' => 'fornecedor',
            'vinculos.*.solucao_id' => 'solução',
        ]);

        $ok = $this->executar(fn () => $registrarNovoProjeto->execute(new RegistrarProjetoInput(
            clienteId: $this->clienteId,
            fornecedores: array_map(fn (array $v) => [
                'fornecedor_id' => $v['fornecedor_id'],
                'solucao_id' => $v['solucao_id'] ?: null,
            ], $this->vinculos),
            codigoOportunidade: $this->codigoOportunidade ?: null,
            usuarioExecutorId: auth()->id(),
        )));

        if ($ok) {
            $this->cancelarFormulario();
            session()->flash('sucesso', 'Projeto criado com sucesso.');
        }
    }

    public function render(ProjetoQuery $projetos, ParceirosQuery $parceiros): View
    {
        $fornecedores = $this->mostrarFormulario ? $parceiros->listarFornecedores(somenteAtivos: true) : [];

        return view('livewire.projetos.painel-projetos', [
            'projetos' => $projetos->listar(),
            'clientes' => $this->mostrarFormulario ? $parceiros->listarClientes(somenteAtivos: true) : [],
            'fornecedores' => $fornecedores,
            'solucoesPorFornecedor' => array_column(array_map(
                fn ($f) => ['id' => $f['id'], 'solucoes' => $f['solucoes']], $fornecedores
            ), 'solucoes', 'id'),
        ]);
    }
}
