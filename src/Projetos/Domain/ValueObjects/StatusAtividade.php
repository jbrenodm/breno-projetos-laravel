<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

/** RN-15 / RN-16 */
enum StatusAtividade: string
{
    case NAO_INICIADA = 'Não Iniciada';
    case EM_ANDAMENTO = 'Em Andamento';
    case PARADA = 'Parada';
    case CONCLUIDA = 'Concluída';

    public function podeTransicionarPara(self $destino): bool
    {
        return in_array($destino, $this->destinosPermitidos(), true);
    }

    /** @return list<self> */
    public function destinosPermitidos(): array
    {
        return match ($this) {
            self::NAO_INICIADA => [self::EM_ANDAMENTO, self::PARADA, self::CONCLUIDA],
            self::EM_ANDAMENTO => [self::PARADA, self::CONCLUIDA],
            self::PARADA => [self::EM_ANDAMENTO, self::CONCLUIDA],
            self::CONCLUIDA => [],
        };
    }

    public function estaAberta(): bool
    {
        return $this !== self::CONCLUIDA;
    }
}
