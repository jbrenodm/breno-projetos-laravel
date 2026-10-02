<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Adapters;

use Src\Identidade\Application\Queries\PapeisQuery;
use Src\Identidade\Domain\Papel;
use Src\Projetos\Application\Ports\NomesDosResponsaveis;

final readonly class NomesDosResponsaveisViaIdentidade implements NomesDosResponsaveis
{
    public function __construct(private PapeisQuery $papeis) {}

    public function accountManager(): string
    {
        return $this->papeis->nomes()[Papel::ACCOUNT_MANAGER->value]['nome'];
    }

    public function preVendas(): string
    {
        return $this->papeis->nomes()[Papel::PRE_VENDAS->value]['nome'];
    }
}
