<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\Exceptions\AtividadeNaoEncontradaException;
use InvalidArgumentException;
use Illuminate\Support\Str;

final readonly class RegistrarNovaAtividade
{
    // O Laravel vai injetar automaticamente o repositório concreto aqui através da Interface
    public function __construct(
        private ProjetoRepositoryInterface $projetoRepository
    ) {}

    public function execute(RegistrarAtividadeInput $input): void
    {
        // 1. Busca o Agregado Root
        $projeto = $this->projetoRepository->findById($input->projetoId);

        if ($projeto === null) {
            throw new InvalidArgumentException("Projeto com ID {$input->projetoId} não foi encontrado.");
        }

        // 2. Cria os Value Objects temporais baseados no input
        $periodo = new PeriodoAtividade(
            dataEntrada: $input->dataEntrada,
            deadline: $input->deadline
        );

        // 3. Delega para o Agregado executar as suas regras de negócio e validações
        $projeto->adicionarAtividade(
            idAtividade: Str::uuid()->toString(), // Gera o ID na aplicação (UUIDv4)
            descricao: $input->descricao,
            statusAtividade: StatusAtividade::from($input->statusActivity ?? $input->statusAtividade),
            periodo: $periodo,
            accountManagerId: $input->accountManagerId,
            preVendasId: $input->preVendasId
        );

        // 4. Salva o estado atualizado do Agregado de forma atómica
        $this->projetoRepository->save($projeto);
    }
}