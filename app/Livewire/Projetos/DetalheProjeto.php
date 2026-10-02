<?php

declare(strict_types=1);

namespace App\Livewire\Projetos;

use App\Livewire\Concerns\ExecutaCasosDeUso;
use DateTimeImmutable;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Domain\Papel;
use Src\Projetos\Application\DTOs\AlterarStatusAtividadeInput;
use Src\Projetos\Application\DTOs\CancelarProjetoInput;
use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Application\UseCases\AlterarStatusAtividade;
use Src\Projetos\Application\UseCases\CancelarProjeto;
use Src\Projetos\Application\UseCases\RegistrarNovaAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\TipoAtividade;

final class DetalheProjeto extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public string $projetoId;

    public bool $mostrarFormulario = false;

    // Formulário de nova atividade (REQUISITOS.md §4.3)
    public string $descricao = '';

    public string $tipo = '';

    public string $status = '';

    public string $dataEntrada = '';

    public string $dataLimite = '';

    public string $dataInicio = '';

    public string $dataTermino = '';

    public string $accountManagerId = '';

    public string $preVendasId = '';

    public string $observacao = '';

    // Mudança de status de uma atividade existente
    public ?string $atividadeEmEdicao = null;

    public string $novoStatus = '';

    public string $dataDoStatus = '';

    public function mount(string $projetoId, ProjetoQuery $projetos): void
    {
        abort_if($projetos->detalhar($projetoId) === null, 404);
        $this->projetoId = $projetoId;
    }

    /** RN-14: pré-preenche AM/PV com os da última atividade. */
    public function abrirFormulario(ProjetoQuery $projetos): void
    {
        $sugeridos = $projetos->responsaveisSugeridos($this->projetoId);

        $this->resetErrorBag();
        $this->fill([
            'mostrarFormulario' => true,
            'descricao' => '', 'observacao' => '',
            'tipo' => TipoAtividade::MAPEAMENTO->value,
            'status' => StatusAtividade::NAO_INICIADA->value,
            'dataEntrada' => now()->toDateString(),
            'dataLimite' => '', 'dataInicio' => '', 'dataTermino' => '',
            'accountManagerId' => $sugeridos['account_manager_id'] ?? '',
            'preVendasId' => $sugeridos['pre_vendas_id'] ?? '',
        ]);
    }

    public function fecharFormulario(): void
    {
        $this->mostrarFormulario = false;
        $this->resetErrorBag();
    }

    /** Campos de data que não se aplicam ao status são limpos (RN-18). */
    public function updatedStatus(): void
    {
        if ($this->status === StatusAtividade::NAO_INICIADA->value) {
            $this->dataInicio = '';
        }
        if ($this->status !== StatusAtividade::CONCLUIDA->value) {
            $this->dataTermino = '';
        }
    }

    public function registrarAtividade(RegistrarNovaAtividade $useCase): void
    {
        $this->validate([
            'descricao' => ['required', 'string', 'max:2000'],
            'tipo' => ['required', Rule::enum(TipoAtividade::class)],
            'status' => ['required', Rule::enum(StatusAtividade::class)],
            'dataEntrada' => ['required', 'date_format:Y-m-d'],
            'dataLimite' => ['required', 'date_format:Y-m-d', 'after_or_equal:dataEntrada'],
            'dataInicio' => ['nullable', 'date_format:Y-m-d'],
            'dataTermino' => ['nullable', 'date_format:Y-m-d'],
            'accountManagerId' => ['required', 'uuid'],
            'preVendasId' => ['required', 'uuid'],
            'observacao' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'descricao' => 'descrição', 'dataEntrada' => 'data de entrada', 'dataLimite' => 'data limite',
            'dataInicio' => 'data de início', 'dataTermino' => 'data de término',
            'accountManagerId' => 'Account Manager', 'preVendasId' => 'Pré-vendas', 'observacao' => 'observação',
        ]);

        $ok = $this->executar(fn () => $useCase->execute(new RegistrarAtividadeInput(
            projetoId: $this->projetoId,
            descricao: $this->descricao,
            tipo: $this->tipo,
            status: $this->status,
            dataEntrada: new DateTimeImmutable($this->dataEntrada),
            dataLimite: new DateTimeImmutable($this->dataLimite),
            dataInicio: self::data($this->dataInicio),
            dataTermino: self::data($this->dataTermino),
            accountManagerId: $this->accountManagerId,
            preVendasId: $this->preVendasId,
            observacao: $this->observacao ?: null,
            usuarioExecutorId: auth()->id(),
        )));

        if ($ok) {
            $this->mostrarFormulario = false;
            session()->flash('sucesso', 'Atividade registrada.');
        }
    }

    public function prepararMudancaDeStatus(string $atividadeId, string $novoStatus): void
    {
        $this->resetErrorBag();
        $this->atividadeEmEdicao = $atividadeId;
        $this->novoStatus = $novoStatus;
        $this->dataDoStatus = now()->toDateString();
    }

    public function cancelarMudancaDeStatus(): void
    {
        $this->reset(['atividadeEmEdicao', 'novoStatus', 'dataDoStatus']);
    }

    /** RN-16 / RN-18: a data informada vira início (Em Andamento) ou término (Concluída). */
    public function confirmarMudancaDeStatus(AlterarStatusAtividade $useCase): void
    {
        $this->validate([
            'atividadeEmEdicao' => ['required', 'uuid'],
            'novoStatus' => ['required', Rule::enum(StatusAtividade::class)],
            'dataDoStatus' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $ok = $this->executar(fn () => $useCase->execute(new AlterarStatusAtividadeInput(
            projetoId: $this->projetoId,
            atividadeId: $this->atividadeEmEdicao,
            novoStatus: $this->novoStatus,
            data: self::data($this->dataDoStatus),
            usuarioExecutorId: auth()->id(),
        )), 'status');

        if ($ok) {
            $this->cancelarMudancaDeStatus();
        }
    }

    public function cancelarProjeto(CancelarProjeto $useCase): void
    {
        $this->executar(fn () => $useCase->execute(new CancelarProjetoInput($this->projetoId, auth()->id())));
    }

    public function render(ProjetoQuery $projetos, UsuariosQuery $usuarios): View
    {
        $projeto = $projetos->detalhar($this->projetoId) ?? abort(404);

        return view('livewire.projetos.detalhe-projeto', [
            'projeto' => $projeto,
            'tipos' => TipoAtividade::cases(),
            'statusPossiveis' => StatusAtividade::cases(),
            'accountManagers' => $this->mostrarFormulario ? $usuarios->listarAtivosPorPapel(Papel::ACCOUNT_MANAGER) : [],
            'preVendas' => $this->mostrarFormulario ? $usuarios->listarAtivosPorPapel(Papel::PRE_VENDAS) : [],
            'primeiraAtividade' => $projeto['atividades'] === [],
        ])->title('Projeto · '.$projeto['cliente']);
    }

    private static function data(string $valor): ?DateTimeImmutable
    {
        return $valor === '' ? null : new DateTimeImmutable($valor);
    }
}
