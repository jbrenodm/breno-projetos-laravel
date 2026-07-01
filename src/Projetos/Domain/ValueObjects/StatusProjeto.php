<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

enum StatusProjeto: string
{
    case NAO_INICIADO = 'Não Iniciado';
    case EM_ANDAMENTO = 'Em Andamento';
    case PARADO = 'Parado';
    case CONCLUIDO = 'Concluído';
    case CANCELADO = 'Cancelado';
}