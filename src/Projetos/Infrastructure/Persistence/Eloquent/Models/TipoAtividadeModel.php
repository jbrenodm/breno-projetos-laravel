<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Apenas esquema de tabela — sem regra de negócio. */
final class TipoAtividadeModel extends Model
{
    use HasUuids;

    protected $table = 'tipos_atividade';

    protected $fillable = ['id', 'nome', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
