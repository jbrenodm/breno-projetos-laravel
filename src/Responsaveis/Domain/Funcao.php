<?php

declare(strict_types=1);

namespace Src\Responsaveis\Domain;

/**
 * RN-42: funções de um Responsável. Os valores são os mesmos identificadores dos papéis (roles), de onde vêm
 * o nome exibido e a sigla editáveis (RN-41).
 */
enum Funcao: string
{
    case ACCOUNT_MANAGER = 'account_manager';
    case PRE_VENDAS = 'pre_vendas';
}
