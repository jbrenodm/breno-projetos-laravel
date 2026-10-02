<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

/** RN-19 */
enum TipoAtividade: string
{
    case MAPEAMENTO = 'Mapeamento';
    case HOMOLOGACAO = 'Homologação';
    case IMPLANTACAO = 'Implantação';
    case COMERCIAL = 'Comercial';
}
