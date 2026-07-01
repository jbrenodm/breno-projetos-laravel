<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

enum StatusAtividade: string
{
    case NAO_INICIADA = 'Não Iniciada';
    case EM_ANDAMENTO = 'Em Andamento';
    case PARADA = 'Parada';
    case CONCLUIDA = 'Concluída';
}