<?php

declare(strict_types=1);

namespace Src\Fornecedores\Application\DTOs;

final readonly class CadastrarFornecedorInput
{
    /**
     * @param string[] $solucoes
     */
    public function __construct(
        public string $nomeFantasia,
        public array $solucoes = []
    ) {}
}