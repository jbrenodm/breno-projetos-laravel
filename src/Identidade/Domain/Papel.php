<?php

declare(strict_types=1);

namespace Src\Identidade\Domain;

/** RN-25 */
enum Papel: string
{
    case ACCOUNT_MANAGER = 'account_manager';
    case PRE_VENDAS = 'pre_vendas';
    case ADMIN_GERAL = 'admin_geral';

    public function rotulo(): string
    {
        return match ($this) {
            self::ACCOUNT_MANAGER => 'Account Manager',
            self::PRE_VENDAS => 'Pré-vendas',
            self::ADMIN_GERAL => 'Admin Geral',
        };
    }
}
