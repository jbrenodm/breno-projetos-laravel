<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FornecedorModel extends Model
{
    use HasUuids;

    protected $table = 'fornecedores';

    protected $fillable = ['id', 'razao_social', 'nome_fantasia', 'cnpj', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function solucoes(): HasMany
    {
        return $this->hasMany(SolucaoModel::class, 'fornecedor_id')->orderBy('nome');
    }
}
