<?php

declare(strict_types=1);

namespace Src\Responsaveis\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Apenas esquema de tabela — sem regra de negócio. */
final class ResponsavelModel extends Model
{
    use HasUuids;

    protected $table = 'responsaveis';

    protected $fillable = ['id', 'nome', 'email', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function funcoes(): HasMany
    {
        return $this->hasMany(ResponsavelFuncaoModel::class, 'responsavel_id');
    }
}
