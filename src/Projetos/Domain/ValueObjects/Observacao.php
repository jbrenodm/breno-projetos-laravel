<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\ValueObjects;

use Src\Projetos\Domain\Exceptions\ObservacaoNaoPermitidaException;

/**
 * RN-20: texto sanitizado + autor. Só o autor pode substituí-la.
 * A saída para HTML deve continuar escapada pela camada de apresentação (Blade {{ }}).
 */
final readonly class Observacao
{
    private const TAMANHO_MAXIMO = 5000;

    public string $texto;

    public function __construct(string $texto, public ?string $autorId = null)
    {
        $limpo = trim(strip_tags($texto));

        if ($limpo === '') {
            throw new ObservacaoNaoPermitidaException('A observação não pode ser vazia.');
        }

        if (mb_strlen($limpo) > self::TAMANHO_MAXIMO) {
            throw new ObservacaoNaoPermitidaException('A observação deve ter no máximo 5000 caracteres.');
        }

        $this->texto = $limpo;
    }

    public static function opcional(?string $texto, ?string $autorId): ?self
    {
        return ($texto === null || trim(strip_tags($texto)) === '') ? null : new self($texto, $autorId);
    }

    public function podeSerEditadaPor(?string $usuarioId): bool
    {
        return $this->autorId === null || $this->autorId === $usuarioId;
    }
}
