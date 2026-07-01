<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Entities;

use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\Exceptions\AtividadeNaoEncontradaException;
use InvalidArgumentException;
use DateTimeImmutable;

final class Projeto
{
    /**
     * @param VinculoFornecedor[] $fornecedores
     * @param Atividade[] $atividades
     */
    
    public function __construct(
        private readonly ProjetoId $id,
        private readonly string $clienteId,
        private readonly array $fornecedores,
        private StatusProjeto $status,
        private ?CodigoOportunidade $codigoOportunidade = null,
        private array $atividades = []
    ) {
        if (empty($this->clienteId)) {
            throw new InvalidArgumentException("O identificador do cliente é obrigatório.");
        }

        if (empty($this->fornecedores)) {
            throw new InvalidArgumentException("Todo projeto deve ter no mínimo um fornecedor vinculado.");
        }
    }

    /**
     * Regra de Negócio Central: Adiciona uma nova atividade aplicando a herança temporal de AM/PV.
     */
    public function adicionarAtividade(
        string $idAtividade,
        string $descricao,
        StatusAtividade $statusAtividade,
        PeriodoAtividade $periodo,
        ?string $accountManagerId = null,
        ?string $preVendasId = null
    ): void {
        
        // Algoritmo de Fallback Temporal: se faltar um dos dois, procura no histórico
        if ($accountManagerId === null || $preVendasId === null) {
            $ultimaAtividade = end($this->atividades);

            if (!$ultimaAtividade) {
                throw new InvalidArgumentException(
                    "Não é possível aplicar o fallback temporal: a primeira atividade do projeto exige a definição explícita do AM e do Pre-Vendas."
                );
            }

            $accountManagerId = $accountManagerId ?? $ultimaAtividade->getAccountManagerId();
            $preVendasId = $preVendasId ?? $ultimaAtividade->getPreVendasId();
        }

        $this->atividades[] = new Atividade(
            id: $idAtividade,
            descricao: $descricao,
            status: $statusAtividade,
            periodo: $periodo,
            accountManagerId: $accountManagerId,
            preVendasId: $preVendasId
        );

        // Dispara a automação de estado mestre
        $this->recalcularStatusMestre();
    }

    /**
     * Regra de Negócio: Conclui uma atividade específica dentro do grafo concorrente.
     */
    public function concluirAtividade(string $atividadeId, DateTimeImmutable $dataTermino): void
    {
        $atividade = collect($this->atividades)->first(fn ($a) => $a->getId() === $atividadeId);

        if ($atividade === null) {
            throw new InvalidArgumentException("Atividade com ID {$atividadeId} não pertence a este projeto.");
        }

        $atividade->concluir($dataTermino);

        // Dispara a automação de estado mestre
        $this->recalcularStatusMestre();
    }

    private function recalcularStatusMestre(): void
    {
        if (empty($this->atividades)) {
            $this->status = StatusProjeto::NAO_INICIADO;
            return;
        }

        $totalAtividades = count($this->atividades);
        $totalConcluidas = collect($this->atividades)->filter(fn ($a) => $a->getStatus() === StatusAtividade::CONCLUIDA)->count();

        // Invariante 1: Se TODAS as atividades concorrentes estão concluídas, o projeto fecha automaticamente
        if ($totalConcluidas === $totalAtividades) {
            $this->status = StatusProjeto::CONCLUIDO;
            return;
        }

        // Invariante 2: Se pelo menos uma atividade foi aberta ou iniciada, o projeto entra Em Andamento
        $this->status = StatusProjeto::EM_ANDAMENTO;
    }

    private function getUltimaAtividadeCronologica(): ?Atividade
    {
        if (empty($this->atividades)) {
            return null;
        }

        return $this->atividades[count($this->atividades) - 1];
    }

    // Getters de encapsulamento
    public function getId(): string 
    {
        return $this->id->toString();
    }

    public function getClienteId(): string 
    {
        return $this->clienteId;
    }

    public function getStatus(): StatusProjeto 
    {
        return $this->status;
    }

    public function getCodigoOportunidade(): ?CodigoOportunidade 
    {
        return $this->codigoOportunidade;
    }

    /** @return Atividade[] */
    public function getAtividades(): array 
    {
        return $this->atividades;
    }

    /** @return VinculoFornecedor[] */
    public function getFornecedores(): array 
    {
        return $this->fornecedores;
    }
}