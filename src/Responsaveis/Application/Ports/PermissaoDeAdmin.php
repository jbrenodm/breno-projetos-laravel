<?php

declare(strict_types=1);

namespace Src\Responsaveis\Application\Ports;

/** RN-27/RN-42: pergunta ao contexto Identidade se o usuário autenticado é Admin Geral do Sistema ativo. */
interface PermissaoDeAdmin
{
    public function ehAdminGeralAtivo(?string $usuarioId): bool;
}
