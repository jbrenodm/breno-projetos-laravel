<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use Src\Projetos\Domain\Exceptions\FornecedorObrigatorioException;
use Src\Shared\Domain\Uuid;

/** RN-04: fornecedor obrigatório, solução opcional. */
final readonly class VinculoFornecedor
{
    public function __construct(
        public string $fornecedorId,
        public ?string $solucaoId = null,
    ) {
        if (! Uuid::ehValido($this->fornecedorId)) {
            throw new FornecedorObrigatorioException('O identificador do fornecedor é obrigatório e deve ser um UUID válido.');
        }

        if ($this->solucaoId !== null && ! Uuid::ehValido($this->solucaoId)) {
            throw new FornecedorObrigatorioException('O identificador da solução deve ser um UUID válido.');
        }
    }

    public function equals(self $outro): bool
    {
        return $this->fornecedorId === $outro->fornecedorId && $this->solucaoId === $outro->solucaoId;
    }
}
