<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Ports;

/**
 * Anti-corruption layer: o contexto Projetos pergunta ao contexto Parceiros apenas o que precisa (RN-05, RN-06).
 */
interface VerificadorDeParceiros
{
    public function clienteEstaAtivo(string $clienteId): bool;

    public function fornecedorEstaAtivo(string $fornecedorId): bool;

    public function solucaoAtivaPertenceAoFornecedor(string $solucaoId, string $fornecedorId): bool;
}
