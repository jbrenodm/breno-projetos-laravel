<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \Src\Projetos\Infrastructure\Persistence\Eloquent\Models\ProjetoEloquentModel $resource
 */
final class ProjetoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'cliente_id' => $this->resource->cliente_id,
            'codigo_oportunidade' => $this->resource->codigo_oportunidade,
            'status' => $this->resource->status,
            'fornecedores' => $this->resource->fornecedores->map(fn ($f) => [
                'fornecedor_id' => $f->fornecedor_id,
                'solucao_id' => $f->solucao_id,
            ])->toArray(),
            // Embutindo o histórico de atividades concorrentes ordenadas pela data de entrada
            'atividades' => AtividadeResource::collection(
                $this->resource->atividades->sortBy('data_entrada')
            ),
            'atualizado_em' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}