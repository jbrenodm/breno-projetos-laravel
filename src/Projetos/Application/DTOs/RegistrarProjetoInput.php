<?php

declare(strict_types=1);

namespace Src\Projetos\Application\DTOs;

final readonly class RegistrarProjetoInput
{
    /**
     * @param  list<array{fornecedor_id: string, solucao_id?: ?string}>  $fornecedores
     */
    public function __construct(
        public string $clienteId,
        public array $fornecedores,
        public ?string $codigoOportunidade = null,
        public ?string $usuarioExecutorId = null,
    ) {}
}
