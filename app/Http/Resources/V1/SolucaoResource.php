<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read \Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models\SolucaoEloquentModel $resource
 */
final class SolucaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'nome' => $this->resource->nome,
            'ativa' => $this->resource->ativa,
        ];
    }
}