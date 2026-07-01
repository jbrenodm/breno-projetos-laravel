<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Illuminate\Support\Str;

final readonly class RegistrarNovoProjeto
{
    public function __construct(
        private ProjetoRepositoryInterface $projetoRepository
    ) {}

    public function execute(RegistrarProjetoInput $input): string
    {
        // 1. Gera uma nova identidade única (UUIDv4) para o Projeto
        $projetoId = ProjetoId::fromString(Str::uuid()->toString());

        // 2. Mapeia os arrays brutos do DTO para Value Objects de domínio
        $fornecedoresDoDominio = array_map(function (array $item) {
            return new VinculoFornecedor(
                fornecedorId: $item['fornecedor_id'],
                solucaoId: $item['solucao_id'] ?? null
            );
        }, $input->fornecedores);

        $codigoOportunidade = $input->codigoOportunidade 
            ? new CodigoOportunidade($input->codigoOportunidade) 
            : null;

        // 3. Cria a entidade rica. As invariantes de negócio são disparadas no construtor
        $projeto = new Projeto(
            id: $projetoId,
            clienteId: $input->clienteId,
            fornecedores: $fornecedoresDoDominio,
            status: StatusProjeto::NAO_INICIADO,
            codigoOportunidade: $codigoOportunidade
        );

        // 4. Persiste o agregado completo no PostgreSQL através da abstração
        $this->projetoRepository->save($projeto);

        // Retorna o ID gerado para que a API possa informar ao cliente HTTP
        return $projeto->getId();
    }
}