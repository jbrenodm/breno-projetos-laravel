<?php

declare(strict_types=1);

namespace Src\Identidade\Application\Queries;

interface UsuariosQuery
{
    /** Tela de usuários (Admin Geral do Sistema). @return list<array{id: string, nome: string, email: string, ativo: bool, deve_trocar_senha: bool, papeis: list<string>}> */
    public function listarTodos(): array;

    /** RN-34/RN-40 @return list<array{id: string, nome: string, criado_em: string, ultimo_uso: ?string}> */
    public function tokensDe(string $usuarioId): array;
}
