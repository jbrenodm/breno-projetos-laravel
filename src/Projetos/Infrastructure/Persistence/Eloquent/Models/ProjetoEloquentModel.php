<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProjetoEloquentModel extends Model
{
    protected $table = 'projetos';
    protected $keyType = 'string';
    public $incrementing = false;
    
    // Desabilitamos o mass assignment tradicional do Eloquent, pois 
    // a blindagem contra injeção maliciosa é feita na borda pelos DTOs e Mappers.
    protected $guarded = [];

    public function fornecedores(): HasMany
    {
        return $this->hasMany(ProjetoFornecedorEloquentModel::class, 'projeto_id');
    }

    public function atividades(): HasMany
    {
        return $this->hasMany(AtividadeEloquentModel::class, 'projeto_id');
    }
}