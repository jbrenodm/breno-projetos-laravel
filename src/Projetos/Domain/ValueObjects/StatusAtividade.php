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

    /** RN-16: qualquer outro status (inclusive reabrir a concluída e voltar para não iniciada). @return list<self> */
    public function destinosPermitidos(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s !== $this));
    }

    public function estaAberta(): bool
    {
        return $this !== self::CONCLUIDA;
    }
}
