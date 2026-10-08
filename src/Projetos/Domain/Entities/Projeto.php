<?php

declare(strict_types=1);

namespace Src\Projetos\Domain\Entities;

use DateTimeImmutable;
use Src\Projetos\Domain\Exceptions\AtividadeNaoEncontradaException;
use Src\Projetos\Domain\Exceptions\FornecedorObrigatorioException;
use Src\Projetos\Domain\Exceptions\ProjetoCanceladoException;
use Src\Projetos\Domain\Exceptions\RegraDeProjetoException;
use Src\Projetos\Domain\Exceptions\ResponsavelObrigatorioException;
use Src\Projetos\Domain\ValueObjects\CodigoOportunidade;
use Src\Projetos\Domain\ValueObjects\Observacao;
use Src\Projetos\Domain\ValueObjects\PeriodoAtividade;
use Src\Projetos\Domain\ValueObjects\ProjetoId;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Projetos\Domain\ValueObjects\VinculoFornecedor;
use Src\Shared\Domain\Uuid;

/**
 * Aggregate Root. Toda alteração em Atividades passa por aqui.
 */
final class Projeto
{
    /**
     * @param  list<VinculoFornecedor>  $fornecedores
     * @param  list<Atividade>  $atividades  em ordem cronológica de criação
     */
    private function __construct(
        private readonly ProjetoId $id,
        private string $clienteId,
        private array $fornecedores,
        private StatusProjeto $status,
        private ?CodigoOportunidade $codigoOportunidade,
        private array $atividades,
    ) {
        if (! Uuid::ehValido($this->clienteId)) {
            throw new RegraDeProjetoException('O cliente do projeto é obrigatório.');
        }

        $this->fornecedores = self::validarFornecedores($fornecedores);
    }

    /**
     * RN-01..RN-07: o projeto nasce sem atividades, sem AM/PV e com status Não Iniciado.
     *
     * @param  list<VinculoFornecedor>  $fornecedores
     */
    public static function criar(
        ProjetoId $id,
        string $clienteId,
        array $fornecedores,
        ?CodigoOportunidade $codigoOportunidade = null,
    ): self {
        return new self($id, $clienteId, $fornecedores, StatusProjeto::NAO_INICIADO, $codigoOportunidade, []);
    }

    /**
     * Reconstrução a partir da persistência.
     *
     * @param  list<VinculoFornecedor>  $fornecedores
     * @param  list<Atividade>  $atividades
     */
    public static function reconstituir(
        ProjetoId $id,
        string $clienteId,
        array $fornecedores,
        StatusProjeto $status,
        ?CodigoOportunidade $codigoOportunidade,
        array $atividades,
    ): self {
        return new self($id, $clienteId, $fornecedores, $status, $codigoOportunidade, array_values($atividades));
    }

    /**
     * RN-12..RN-14, RN-17..RN-20.
     * AM/PV nulos => herda da última atividade criada (fallback temporal).
     */
    public function adicionarAtividade(
        string $atividadeId,
        string $descricao,
        string $tipoId,
        StatusAtividade $status,
        PeriodoAtividade $periodo,
        ?string $accountManagerId,
        ?string $preVendasId,
        ?Observacao $observacao,
        DateTimeImmutable $hoje,
    ): Atividade {
        $this->garantirQueNaoEstaCancelado();

        $ultima = $this->ultimaAtividade();
        $accountManagerId ??= $ultima?->getAccountManagerId();
        $preVendasId ??= $ultima?->getPreVendasId();

        if ($accountManagerId === null || $preVendasId === null) {
            throw new ResponsavelObrigatorioException(
                'Para registrar a primeira atividade do projeto é obrigatório informar o Account Manager e o Pré-vendas.'
            );
        }

        $atividade = Atividade::registrar(
            $atividadeId, $descricao, $tipoId, $status, $periodo,
            $accountManagerId, $preVendasId, $observacao, $hoje,
        );

        $this->atividades[] = $atividade;
        $this->recalcularStatus();

        return $atividade;
    }

    /** RN-16 / RN-18 */
    public function alterarStatusAtividade(
        string $atividadeId,
        StatusAtividade $novoStatus,
        ?DateTimeImmutable $data,
        DateTimeImmutable $hoje,
    ): void {
        $this->garantirQueNaoEstaCancelado();
        $this->buscarAtividade($atividadeId)->alterarStatus($novoStatus, $data, $hoje);
        $this->recalcularStatus();
    }

    /** RN-28 */
    public function editarAtividade(
        string $atividadeId,
        string $descricao,
        string $tipoId,
        PeriodoAtividade $periodo,
        string $accountManagerId,
        string $preVendasId,
        ?string $observacao,
        ?string $usuarioId,
        DateTimeImmutable $hoje,
    ): void {
        $this->garantirQueNaoEstaCancelado();
        $this->buscarAtividade($atividadeId)->editar(
            $descricao, $tipoId, $periodo, $accountManagerId, $preVendasId, $observacao, $usuarioId, $hoje,
        );
    }

    /** RN-29: a verificação de cliente ativo (RN-06) fica no caso de uso. */
    public function alterarCliente(string $clienteId): void
    {
        $this->garantirQueNaoEstaCancelado();

        if (! Uuid::ehValido($clienteId)) {
            throw new RegraDeProjetoException('O cliente do projeto é obrigatório.');
        }

        $this->clienteId = $clienteId;
    }

    /** RN-20 */
    public function registrarObservacao(string $atividadeId, string $texto, ?string $usuarioId): void
    {
        $this->garantirQueNaoEstaCancelado();
        $this->buscarAtividade($atividadeId)->registrarObservacao($texto, $usuarioId);
    }

    /** RN-11 */
    public function cancelar(): void
    {
        $this->garantirQueNaoEstaCancelado();
        $this->status = StatusProjeto::CANCELADO;
    }

    /** RN-02 */
    public function definirCodigoOportunidade(?CodigoOportunidade $codigo): void
    {
        $this->codigoOportunidade = $codigo;
    }

    /** RN-14: responsáveis sugeridos para a próxima atividade. */
    public function responsaveisSugeridos(): array
    {
        $ultima = $this->ultimaAtividade();

        return [
            'account_manager_id' => $ultima?->getAccountManagerId(),
            'pre_vendas_id' => $ultima?->getPreVendasId(),
        ];
    }

    /** RN-08..RN-10 */
    private function recalcularStatus(): void
    {
        if ($this->status === StatusProjeto::CANCELADO) {
            return;
        }

        if ($this->atividades === []) {
            $this->status = StatusProjeto::NAO_INICIADO;

            return;
        }

        foreach ($this->atividades as $atividade) {
            if ($atividade->estaAberta()) {
                $this->status = StatusProjeto::EM_ANDAMENTO;

                return;
            }
        }

        $this->status = StatusProjeto::CONCLUIDO;
    }

    public function buscarAtividade(string $atividadeId): Atividade
    {
        foreach ($this->atividades as $atividade) {
            if ($atividade->getId() === $atividadeId) {
                return $atividade;
            }
        }

        throw new AtividadeNaoEncontradaException('A atividade informada não pertence a este projeto.');
    }

    private function ultimaAtividade(): ?Atividade
    {
        return $this->atividades === [] ? null : $this->atividades[array_key_last($this->atividades)];
    }

    private function garantirQueNaoEstaCancelado(): void
    {
        if ($this->status === StatusProjeto::CANCELADO) {
            throw new ProjetoCanceladoException('Projeto cancelado não pode ser alterado.');
        }
    }

    /**
     * @param  array<mixed>  $fornecedores
     * @return list<VinculoFornecedor>
     */
    private static function validarFornecedores(array $fornecedores): array
    {
        if ($fornecedores === []) {
            throw new FornecedorObrigatorioException('Todo projeto deve ter no mínimo um fornecedor vinculado.');
        }

        $unicos = [];
        foreach ($fornecedores as $vinculo) {
            if (! $vinculo instanceof VinculoFornecedor) {
                throw new FornecedorObrigatorioException('Vínculo de fornecedor inválido.');
            }

            foreach ($unicos as $existente) {
                if ($existente->equals($vinculo)) {
                    continue 2; // ignora duplicados
                }
            }
            $unicos[] = $vinculo;
        }

        return $unicos;
    }

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

    /** @return list<VinculoFornecedor> */
    public function getFornecedores(): array
    {
        return $this->fornecedores;
    }

    /** @return list<Atividade> */
    public function getAtividades(): array
    {
        return $this->atividades;
    }
}
