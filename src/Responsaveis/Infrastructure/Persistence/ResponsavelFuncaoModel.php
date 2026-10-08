<?php

declare(strict_types=1);

namespace Src\Responsaveis\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/** Linha de "responsavel_funcoes" (pk composta: responsavel_id + funcao). */
final class ResponsavelFuncaoModel extends Model
{
    protected $table = 'responsavel_funcoes';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['responsavel_id', 'funcao'];
}
