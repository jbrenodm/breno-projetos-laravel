<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjetoFornecedorModel extends Model
{
    protected $table = 'projeto_fornecedores';

    protected $fillable = ['projeto_id', 'fornecedor_id', 'solucao_id'];
}
