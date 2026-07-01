<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class AtividadeEloquentModel extends Model
{
    protected $table = 'atividades';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];
    
    // Convertemos automaticamente as colunas de data em objetos Carbon/DateTime nativos do Laravel
    protected $casts = [
        'data_entrada' => 'datetime',
        'deadline' => 'datetime',
        'data_termino' => 'datetime',
    ];
}