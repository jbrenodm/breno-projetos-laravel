<?php

declare(strict_types=1);

namespace App\Livewire\Projetos;

use Livewire\Component;
use Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel;
use Illuminate\View\View;

final class Detalhes extends Component
{
    public string $projetoId;

    // Propriedades alinhadas com a análise de requisitos
    public string $titulo = '';
    public string $descricao = '';
    public string $tipo = 'Mapeamento'; // Valor padrão inicial
    public int $ordem = 1;

    public function mount(string $id): void
    {
        $this->projetoId = $id;
        
        // Auto-incrementa a ordem sugerida com base nas atividades existentes
        $this->sugerirProximaOrdem();
    }

    public function adicionarAtividade(): void
    {
        $this->validate([
            'titulo' => 'required|string|min:3|max:255',
            'descricao' => 'nullable|string',
            'tipo' => 'required|string',
            'ordem' => 'required|integer|min:1',
        ]);

        $projeto = ProjetoEloquentModel::findOrFail($this->projetoId);

        // Salva com todos os campos reais do banco de dados
        $projeto->atividades()->create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'tipo' => $this->tipo,
            'ordem' => $this->ordem,
            'status' => 'Pendente'
        ]);

        // Limpa o formulário e calcula a próxima ordem automaticamente
        $this->reset(['titulo', 'descricao', 'tipo']);
        $this->sugerirProximaOrdem();
    }

    public function alternarStatusAtividade(string $atividadeId): void
    {
        $projeto = ProjetoEloquentModel::with('atividades')->findOrFail($this->projetoId);
        $atividade = $projeto->atividades->firstWhere('id', $atividadeId);

        if ($atividade) {
            $novoStatus = $atividade->status === 'Concluída' ? 'Pendente' : 'Concluída';
            
            $projeto->atividades()->where('id', $atividadeId)->update([
                'status' => $novoStatus
            ]);
        }
    }

    private function sugerirProximaOrdem(): void
    {
        $ultimaOrdem = \DB::table('atividades')
            ->where('projeto_id', $this->projetoId)
            ->max('ordem');

        $this->ordem = $ultimaOrdem ? (int)$ultimaOrdem + 1 : 1;
    }

    public function render(): View
    {
        // Ordena as atividades pelo campo 'ordem' para fazer sentido na tela
        $projeto = ProjetoEloquentModel::with(['atividades' => function ($query) {
            $query->orderBy('ordem', 'asc');
        }])->findOrFail($this->projetoId);

        return view('components.projetos.⚡detalhes', [
            'projeto' => $projeto
        ]);
    }
}