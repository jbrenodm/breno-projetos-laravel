<?php

declare(strict_types=1);

namespace Src\Identidade\Domain;

/** RN-25. O identificador (value) nunca muda; nome exibido e sigla são editáveis (RN-41) — aqui ficam só os padrões. */
enum Papel: string
{
    case ACCOUNT_MANAGER = 'account_manager';
    case PRE_VENDAS = 'pre_vendas';
    case ADMIN_GERAL = 'admin_geral';

    public function nomePadrao(): string
    {
        return match ($this) {
            self::ACCOUNT_MANAGER => 'Account Manager',
            self::PRE_VENDAS => 'Pré-vendas',
            self::ADMIN_GERAL => 'Admin Geral do Sistema',
        };
    }

    public function siglaPadrao(): ?string
    {
        return match ($this) {
            self::ACCOUNT_MANAGER => 'AM',
            self::PRE_VENDAS => 'PV',
            self::ADMIN_GERAL => null,
        };
    }
}
