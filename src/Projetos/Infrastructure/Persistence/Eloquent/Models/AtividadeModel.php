<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class AtividadeModel extends Model
{
    protected $table = 'atividades';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'projeto_id', 'sequencia', 'descricao', 'tipo_id', 'status',
        'data_entrada', 'data_limite', 'data_inicio', 'data_termino',
        'account_manager_id', 'pre_vendas_id', 'observacao', 'observacao_autor_id',
    ];

    protected $casts = [
        'data_entrada' => 'immutable_date',
        'data_limite' => 'immutable_date',
        'data_inicio' => 'immutable_date',
        'data_termino' => 'immutable_date',
        'sequencia' => 'integer',
    ];
}
