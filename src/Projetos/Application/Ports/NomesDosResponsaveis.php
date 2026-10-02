<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Ports;

/** RN-41: nomes atuais dos papéis de AM e PV, para as mensagens ao usuário (vindos do contexto Identidade). */
interface NomesDosResponsaveis
{
    public function accountManager(): string;

    public function preVendas(): string;
}
