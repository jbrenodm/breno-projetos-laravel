<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\FornecedorEloquentModel $resource
 */
final class FornecedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nome_fantasia' => $this->resource->nome_fantasia,
            'ativo' => $this->resource->ativo,
            'solucoes' => SolucaoResource::collection($this->resource->solucoes),
        ];
    }
}