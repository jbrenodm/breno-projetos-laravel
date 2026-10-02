<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Apenas esquema de tabela — regras ficam no domínio. */
final class ProjetoModel extends Model
{
    protected $table = 'projetos';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'cliente_id', 'codigo_oportunidade', 'status'];

    public function fornecedores(): HasMany
    {
        return $this->hasMany(ProjetoFornecedorModel::class, 'projeto_id')->orderBy('id');
    }

    public function atividades(): HasMany
    {
        return $this->hasMany(AtividadeModel::class, 'projeto_id')->orderBy('sequencia');
    }
}
