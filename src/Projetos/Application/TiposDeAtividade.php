<?php

declare(strict_types=1);

namespace Src\Projetos\Application;

use Src\Projetos\Application\Ports\PermissaoDeAdmin;
use Src\Projetos\Domain\Entities\TipoAtividade;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Repositories\TipoAtividadeRepositoryInterface;
use Src\Shared\Application\AcessoNegadoException;
use Src\Shared\Application\RecursoNaoEncontradoException;

/** RN-19/RN-27/RN-43: regras compartilhadas pelos casos de uso que lidam com tipos de atividade. */
final readonly class TiposDeAtividade
{
    public function __construct(
        private TipoAtividadeRepositoryInterface $tipos,
        private PermissaoDeAdmin $permissao,
    ) {}

    public function garantirAdminGeral(?string $usuarioExecutorId): void
    {
        if (! $this->permissao->ehAdminGeralAtivo($usuarioExecutorId)) {
            throw AcessoNegadoException::somenteAdminGeral();
        }
    }

    public function buscar(string $tipoId): TipoAtividade
    {
        return $this->tipos->buscarPorId($tipoId)
            ?? throw RecursoNaoEncontradoException::para('Tipo de atividade', $tipoId);
    }

    public function garantirNomeLivre(TipoAtividade $tipo, ?string $ignorarId = null): void
    {
        if ($this->tipos->nomeEmUso($tipo->getNome(), $ignorarId)) {
            throw new RegraDeProjetoException("Já existe um tipo de atividade chamado '{$tipo->getNome()}'.");
        }
    }

    /** RN-19: só um tipo ativo pode ser escolhido para uma atividade (nova ou com o tipo trocado). */
    public function garantirAtivoParaEscolha(string $tipoId): void
    {
        $tipo = $this->tipos->buscarPorId($tipoId);

        if ($tipo === null || ! $tipo->isAtivo()) {
            throw new RegraDeProjetoException('O tipo de atividade informado não existe ou está inativo.');
        }
    }

    /** RN-43: sempre há pelo menos um tipo ativo. */
    public function garantirQueRestaOutroAtivo(TipoAtividade $tipo): void
    {
        if ($tipo->isAtivo() && $this->tipos->contarAtivos(ignorarId: $tipo->getId()) === 0) {
            throw new RegraDeProjetoException('É preciso manter pelo menos um tipo de atividade ativo.');
        }
    }
}
