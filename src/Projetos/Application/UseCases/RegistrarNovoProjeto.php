<?php

declare(strict_types=1);

namespace Src\Projetos\Application\UseCases;

use Src\Projetos\Application\DTOs\RegistrarProjetoInput;
use Src\Projetos\Application\Ports\VerificadorDeParceiros;
use Src\Projetos\Domain\Entities\Projeto;
use Src\Projetos\Domain\Exceptions\FornecedorObrigatorioException;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Repositories\ProjetoRepositoryInterface;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Src\Shared\Application\Ports\GeradorDeId;

/** RN-01..RN-07 */
final readonly class RegistrarNovoProjeto
{
    public function __construct(
        private ProjetoRepositoryInterface $projetos,
        private VerificadorDeParceiros $parceiros,
        private GeradorDeId $geradorDeId,
    ) {}

    public function execute(RegistrarProjetoInput $input): string
    {
        if (! $this->parceiros->clienteEstaAtivo($input->clienteId)) {
            throw new RegraDeProjetoException('O cliente informado não existe ou está inativo.');
        }

        $vinculos = array_map(fn (array $item) => $this->criarVinculo($item), $input->fornecedores);

        $projeto = Projeto::criar(
            ProjetoId::fromString($this->geradorDeId->gerar()),
            $input->clienteId,
            $vinculos,
            CodigoOportunidade::opcional($input->codigoOportunidade),
        );

        $this->projetos->salvar($projeto);

        return $projeto->getId();
    }

    /** @param array{fornecedor_id?: ?string, solucao_id?: ?string} $item */
    private function criarVinculo(array $item): VinculoFornecedor
    {
        $vinculo = new VinculoFornecedor((string) ($item['fornecedor_id'] ?? ''), $item['solucao_id'] ?? null);

        if (! $this->parceiros->fornecedorEstaAtivo($vinculo->fornecedorId)) {
            throw new FornecedorObrigatorioException('O fornecedor informado não existe ou está inativo.');
        }

        if ($vinculo->solucaoId !== null
            && ! $this->parceiros->solucaoAtivaPertenceAoFornecedor($vinculo->solucaoId, $vinculo->fornecedorId)) {
            throw new FornecedorObrigatorioException('A solução informada não pertence ao fornecedor ou está inativa.');
        }

        return $vinculo;
    }
}
