<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjetoFornecedorEloquentModel extends Model
{
    protected $table = 'projeto_fornecedores';
    protected $guarded = [];
}