<?php

declare(strict_types=1);

namespace Src\Identidade\Application\UseCases;

use Src\Identidade\Application\AutorizacaoDeUsuarios;
use Src\Identidade\Application\DTOs\RenomearPapelInput;
use Src\Identidade\Domain\Exceptions\RegraDeIdentidadeException;
use Src\Identidade\Domain\Papel;
use Src\Identidade\Domain\Repositories\DefinicaoDePapelRepositoryInterface;

/** RN-41 (só Admin Geral do Sistema) */
final readonly class RenomearPapel
{
    public function __construct(
        private DefinicaoDePapelRepositoryInterface $papeis,
        private AutorizacaoDeUsuarios $autorizacao,
    ) {}

    public function execute(RenomearPapelInput $input): void
    {
        $this->autorizacao->garantirAdminGeral($input->usuarioExecutorId);

        $papel = Papel::tryFrom($input->papel) ?? throw new RegraDeIdentidadeException("Papel inválido: {$input->papel}.");
        $definicao = $this->papeis->buscar($papel);
        $definicao->renomear($input->nome, $input->sigla);

        if ($this->papeis->nomeEmUso($definicao->getNome(), ignorar: $papel)) {
            throw new RegraDeIdentidadeException("Já existe um papel chamado '{$definicao->getNome()}'.");
        }

        if ($definicao->getSigla() !== null && $this->papeis->siglaEmUso($definicao->getSigla(), ignorar: $papel)) {
            throw new RegraDeIdentidadeException("A sigla '{$definicao->getSigla()}' já é usada por outro papel.");
        }

        $this->papeis->salvar($definicao);
    }
}
