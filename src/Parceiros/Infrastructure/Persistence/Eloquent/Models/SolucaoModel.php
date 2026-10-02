<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class SolucaoModel extends Model
{
    use HasUuids;

    protected $table = 'solucoes';

    protected $fillable = ['id', 'fornecedor_id', 'nome', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
