<?php

declare(strict_types=1);

namespace Src\Fornecedores\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class SolucaoEloquentModel extends Model
{
    protected $table = 'solucoes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'fornecedor_id', 'nome', 'ativa'];

    protected $casts = [
        'ativa' => 'boolean',
    ];
}