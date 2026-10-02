<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\ValueObjects;

use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;

/** RN-21: razão social obrigatória; nome fantasia e CNPJ opcionais. Comum a Cliente e Fornecedor. */
final readonly class DadosCadastrais
{
    public string $razaoSocial;

    public ?string $nomeFantasia;

    public function __construct(string $razaoSocial, ?string $nomeFantasia = null, public ?Cnpj $cnpj = null)
    {
        $razao = trim(strip_tags($razaoSocial));
        if ($razao === '' || mb_strlen($razao) > 255) {
            throw new RegraDeParceiroException('A razão social é obrigatória (máximo 255 caracteres).');
        }

        $fantasia = $nomeFantasia === null ? null : trim(strip_tags($nomeFantasia));
        if ($fantasia !== null && mb_strlen($fantasia) > 255) {
            throw new RegraDeParceiroException('O nome fantasia deve ter no máximo 255 caracteres.');
        }

        $this->razaoSocial = $razao;
        $this->nomeFantasia = $fantasia === '' ? null : $fantasia;
    }

    /** Nome para exibição: fantasia quando houver, senão razão social. */
    public function nomeExibicao(): string
    {
        return $this->nomeFantasia ?? $this->razaoSocial;
    }
}
