<?php

declare(strict_types=1);

namespace Src\Parceiros\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Apenas esquema de tabela — sem regra de negócio. */
final class ClienteModel extends Model
{
    use HasUuids;

    protected $table = 'clientes';

    protected $fillable = ['id', 'razao_social', 'nome_fantasia', 'cnpj', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
