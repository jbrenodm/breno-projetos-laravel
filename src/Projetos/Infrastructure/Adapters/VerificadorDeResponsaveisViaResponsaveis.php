<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Adapters;

use Src\Projetos\Application\Ports\VerificadorDeResponsaveis;
use Src\Responsaveis\Application\Queries\ResponsaveisQuery;
use Src\Responsaveis\Domain\Funcao;

final readonly class VerificadorDeResponsaveisViaResponsaveis implements VerificadorDeResponsaveis
{
    public function __construct(private ResponsaveisQuery $responsaveis) {}

    public function ehAccountManagerAtivo(string $responsavelId): bool
    {
        return $this->responsaveis->possuiFuncaoAtiva($responsavelId, Funcao::ACCOUNT_MANAGER);
    }

    public function ehPreVendasAtivo(string $responsavelId): bool
    {
        return $this->responsaveis->possuiFuncaoAtiva($responsavelId, Funcao::PRE_VENDAS);
    }
}
