<?php

declare(strict_types=1);

namespace Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FornecedorEloquentModel extends Model
{
    protected $table = 'fornecedores';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'nome_fantasia', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function solucoes(): HasMany
    {
        return $this->hasMany(SolucaoEloquentModel::class, 'fornecedor_id');
    }
}