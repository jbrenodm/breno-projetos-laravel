<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \Src\Projetos\Infrastructure\Persistence\Eloquent\Models\AtividadeEloquentModel $resource
 */
final class AtividadeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'descricao' => $this->resource->descricao,
            'status' => $this->resource->status,
            'responsaveis' => [
                'account_manager_id' => $this->resource->account_manager_id,
                'pre_vendas_id' => $this->resource->pre_vendas_id,
            ],
            'prazos' => [
                'data_entrada' => $this->resource->data_entrada->toIso8601String(),
                'deadline' => $this->resource->deadline->toIso8601String(),
                'data_termino' => $this->resource->data_termino?->toIso8601String(),
            ],
            'criado_em' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}