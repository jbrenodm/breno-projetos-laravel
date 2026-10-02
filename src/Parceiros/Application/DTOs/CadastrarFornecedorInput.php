<?php

declare(strict_types=1);

namespace Src\Parceiros\Application\DTOs;

final readonly class CadastrarFornecedorInput
{
    /** @param list<string> $solucoes nomes das soluções iniciais (opcional) */
    public function __construct(
        public string $razaoSocial,
        public ?string $nomeFantasia = null,
        public ?string $cnpj = null,
        public array $solucoes = [],
    ) {}
}
