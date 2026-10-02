<?php

declare(strict_types=1);

namespace Src\Parceiros\Domain\Entities;

use Src\Parceiros\Domain\Exceptions\RegraDeParceiroException;
use Src\Parceiros\Domain\ValueObjects\DadosCadastrais;
use Src\Shared\Domain\Uuid;

/** Aggregate Root do catálogo: Fornecedor + Soluções (RN-21..RN-24). */
final class Fornecedor
{
    /** @param list<Solucao> $solucoes */
    public function __construct(
        private readonly string $id,
        private DadosCadastrais $dados,
        private bool $ativo = true,
        private array $solucoes = [],
    ) {
        if (! Uuid::ehValido($id)) {
            throw new RegraDeParceiroException('O identificador do fornecedor deve ser um UUID válido.');
        }
    }

    /** RN-23: nome de solução não se repete no mesmo fornecedor. */
    public function adicionarSolucao(string $solucaoId, string $nome, ?string $descricao = null): Solucao
    {
        $solucao = new Solucao($solucaoId, $nome, $descricao);
        $this->garantirNomeUnico($solucao->getNome(), null);

        $this->solucoes[] = $solucao;

        return $solucao;
    }

    /** RN-32 + RN-23 */
    public function editarSolucao(string $solucaoId, string $nome, ?string $descricao): void
    {
        $solucao = $this->buscarSolucao($solucaoId);

        // Valida e sanitiza numa cópia antes de alterar: em caso de erro, nada muda.
        $candidata = new Solucao($solucaoId, $nome, $descricao);
        $this->garantirNomeUnico($candidata->getNome(), $solucaoId);

        $solucao->atualizar($nome, $descricao);
    }

    /** RN-31 */
    public function alterarSituacaoDaSolucao(string $solucaoId, bool $ativa): void
    {
        $solucao = $this->buscarSolucao($solucaoId);
        $ativa ? $solucao->ativar() : $solucao->inativar();
    }

    private function buscarSolucao(string $solucaoId): Solucao
    {
        foreach ($this->solucoes as $solucao) {
            if ($solucao->getId() === $solucaoId) {
                return $solucao;
            }
        }

        throw new RegraDeParceiroException('A solução informada não pertence a este fornecedor.');
    }

    /** RN-23: nome de solução não se repete no mesmo fornecedor (sem diferenciar maiúsculas). */
    private function garantirNomeUnico(string $nome, ?string $ignorarSolucaoId): void
    {
        $chave = mb_strtolower($nome);

        foreach ($this->solucoes as $existente) {
            if ($existente->getId() !== $ignorarSolucaoId && mb_strtolower($existente->getNome()) === $chave) {
                throw new RegraDeParceiroException("Este fornecedor já possui a solução '{$nome}'.");
            }
        }
    }

    public function atualizarDados(DadosCadastrais $dados): void
    {
        $this->dados = $dados;
    }

    public function inativar(): void
    {
        $this->ativo = false;
    }

    public function ativar(): void
    {
        $this->ativo = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDados(): DadosCadastrais
    {
        return $this->dados;
    }

    public function isAtivo(): bool
    {
        return $this->ativo;
    }

    /** @return list<Solucao> */
    public function getSolucoes(): array
    {
        return $this->solucoes;
    }
}
