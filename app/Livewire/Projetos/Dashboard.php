<?php

declare(strict_types=1);

namespace App\Livewire\Projetos;

use Livewire\Component;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\FornecedorEloquentModel;
use Src\Projetos\Application\UseCases\RegistrarNovoProjeto;
use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Illuminate\View\View;

final class Dashboard extends Component
{
    // Propriedades unidas ao formulário da tela
    public string $clienteId = '';
    public string $fornecedorId = '';
    public array $solucoesSelecionadas = [];
    public string $codigoOportunidade = ''; 

    /**
     * Processa a criação do projeto invocando a Clean Architecture correta
     */
    public function salvar(RegistrarNovoProjeto $registrarNovoProjeto): void
    {
        $this->validate([
            'clienteId' => 'required|uuid',
            'fornecedorId' => 'required|uuid',
            'solucoesSelecionadas' => 'nullable|array', // Garantimos que pode ser nulo ou vazio
        ]);

        /**
         * Intercepta a regra de negócio:
         * Se nenhuma solução foi marcada, criamos o vínculo estruturado contendo 
         * apenas o fornecedor_id e passamos a solucao_id como null.
         */
        if (empty($this->solucoesSelecionadas)) {
            $fornecedoresFormatados = [
                [
                    'fornecedor_id' => $this->fornecedorId,
                    'solucao_id' => null
                ]
            ];
        } else {
            // Se existirem soluções, faz o mapeamento normal de cada uma delas
            $fornecedoresFormatados = array_map(function ($solucaoId) {
                return [
                    'fornecedor_id' => $this->fornecedorId,
                    'solucao_id' => $solucaoId
                ];
            }, $this->solucoesSelecionadas);
        }

        // Monta o DTO de Entrada agora blindado com o fornecedor garantido
        $input = new RegistrarProjetoInput(
            clienteId: $this->clienteId,
            fornecedores: $fornecedoresFormatados,
            codigoOportunidade: $this->codigoOportunidade ?: null
        );

        // Executa a regra de negócio do caso de uso real
        $registrarNovoProjeto->execute($input);

        // Limpa o estado e fecha o modal
        $this->reset(['clienteId', 'fornecedorId', 'solucoesSelecionadas', 'codigoOportunidade']);
        $this->dispatch('projeto-salvo');
    }

    public function render(): View
    {
        return view('components.projetos.⚡dashboard', [
            'projetos' => ProjetoEloquentModel::with('atividades')->get(),
            'fornecedores' => FornecedorEloquentModel::with('solucoes')
                ->where('ativo', true)
                ->orderBy('nome_fantasia')
                ->get()
        ]);
    }
}