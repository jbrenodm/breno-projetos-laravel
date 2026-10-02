<?php

declare(strict_types=1);

namespace Src\Projetos\Application\Queries;

/** Campos permitidos para ordenar a listagem de atividades (lista fechada: nada vem direto da URL para o SQL). */
enum OrdenacaoAtividades: string
{
    case DATA_ENTRADA = 'data_entrada';
    case DATA_LIMITE = 'data_limite';
    case CLIENTE = 'cliente';
    case STATUS = 'status';
    case TIPO = 'tipo';

    public function rotulo(): string
    {
        return match ($this) {
            self::DATA_ENTRADA => 'Data de entrada',
            self::DATA_LIMITE => 'Data limite',
            self::CLIENTE => 'Cliente',
            self::STATUS => 'Status',
            self::TIPO => 'Tipo',
        };
    }
}
