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
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Projetos\Application\DTOs\AlterarClienteDoProjetoInput;
use Src\Projetos\Application\DTOs\AlterarStatusAtividadeInput;
use Src\Projetos\Application\DTOs\CancelarProjetoInput;
use Src\Projetos\Application\DTOs\EditarAtividadeInput;
use Src\Projetos\Application\DTOs\RegistrarAtividadeInput;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Application\UseCases\AlterarClienteDoProjeto;
use Src\Projetos\Application\UseCases\AlterarStatusAtividade;
use Src\Projetos\Application\UseCases\CancelarProjeto;
use Src\Projetos\Application\UseCases\EditarAtividade;
use Src\Projetos\Application\UseCases\RegistrarNovaAtividade;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\TipoAtividade;

final class DetalheProjeto extends Component
{
    use ExecutaCasosDeUso;

    #[Locked]
    public string $projetoId;

    public bool $mostrarFormulario = false;

    /** Preenchido quando o formulário está editando uma atividade existente (RN-28). */
    #[Locked]
    public ?string $atividadeEditandoId = null;

    // Formulário de nova atividade / edição (REQUISITOS.md §4.3)
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

    // Troca de cliente do projeto (RN-29)
    public bool $trocandoCliente = false;

    public string $novoClienteId = '';

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
            'atividadeEditandoId' => null,
            'descricao' => '', 'observacao' => '',
            'tipo' => TipoAtividade::MAPEAMENTO->value,
            'status' => StatusAtividade::NAO_INICIADA->value,
            'dataEntrada' => now()->toDateString(),
            'dataLimite' => '', 'dataInicio' => '', 'dataTermino' => '',
            'accountManagerId' => $sugeridos['account_manager_id'] ?? '',
            'preVendasId' => $sugeridos['pre_vendas_id'] ?? '',
        ]);
    }

    /** RN-28: abre o formulário com os dados atuais da atividade. */
    public function editarAtividade(string $atividadeId, ProjetoQuery $projetos): void
    {
        $atividade = collect($projetos->detalhar($this->projetoId)['atividades'] ?? [])->firstWhere('id', $atividadeId);

        if ($atividade === null) {
            $this->addError('geral', 'Registro não encontrado.');

            return;
        }

        $this->resetErrorBag();
        $this->cancelarMudancaDeStatus();
        $this->fill([
            'mostrarFormulario' => true,
            'atividadeEditandoId' => $atividade['id'],
            'descricao' => $atividade['descricao'],
            'tipo' => $atividade['tipo'],
            'status' => $atividade['status'],
            'dataEntrada' => $atividade['data_entrada'],
            'dataLimite' => $atividade['data_limite'],
            'dataInicio' => $atividade['data_inicio'] ?? '',
            'dataTermino' => $atividade['data_termino'] ?? '',
            'accountManagerId' => $atividade['account_manager_id'],
            'preVendasId' => $atividade['pre_vendas_id'],
            'observacao' => $atividade['observacao'] ?? '',
        ]);
    }

    public function fecharFormulario(): void
    {
        $this->mostrarFormulario = false;
        $this->atividadeEditandoId = null;
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
        $this->validarFormularioDeAtividade();

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

    /** RN-28: o status não é alterado aqui, só pelas transições (RN-16). */
    public function salvarEdicao(EditarAtividade $useCase): void
    {
        if ($this->atividadeEditandoId === null) {
            return;
        }

        $this->validarFormularioDeAtividade();

        $ok = $this->executar(fn () => $useCase->execute(new EditarAtividadeInput(
            projetoId: $this->projetoId,
            atividadeId: $this->atividadeEditandoId,
            descricao: $this->descricao,
            tipo: $this->tipo,
            dataEntrada: new DateTimeImmutable($this->dataEntrada),
            dataLimite: new DateTimeImmutable($this->dataLimite),
            accountManagerId: $this->accountManagerId,
            preVendasId: $this->preVendasId,
            dataInicio: self::data($this->dataInicio),
            dataTermino: self::data($this->dataTermino),
            observacao: $this->observacao ?: null,
            usuarioExecutorId: auth()->id(),
        )));

        if ($ok) {
            $this->fecharFormulario();
            session()->flash('sucesso', 'Atividade atualizada.');
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

    public function abrirTrocaDeCliente(ProjetoQuery $projetos): void
    {
        $this->resetErrorBag();
        $this->trocandoCliente = true;
        $this->novoClienteId = $projetos->detalhar($this->projetoId)['cliente_id'] ?? '';
    }

    public function cancelarTrocaDeCliente(): void
    {
        $this->reset(['trocandoCliente', 'novoClienteId']);
        $this->resetErrorBag('novoClienteId');
    }

    /** RN-29 */
    public function salvarCliente(AlterarClienteDoProjeto $useCase): void
    {
        $this->validate(['novoClienteId' => ['required', 'uuid']], attributes: ['novoClienteId' => 'cliente']);

        $ok = $this->executar(fn () => $useCase->execute(new AlterarClienteDoProjetoInput(
            projetoId: $this->projetoId,
            clienteId: $this->novoClienteId,
            usuarioExecutorId: auth()->id(),
        )), 'novoClienteId');

        if ($ok) {
            $this->cancelarTrocaDeCliente();
            session()->flash('sucesso', 'Cliente do projeto alterado.');
        }
    }

    public function render(ProjetoQuery $projetos, UsuariosQuery $usuarios, ParceirosQuery $parceiros): View
    {
        $projeto = $projetos->detalhar($this->projetoId) ?? abort(404);

        return view('livewire.projetos.detalhe-projeto', [
            'projeto' => $projeto,
            'tipos' => TipoAtividade::cases(),
            'statusPossiveis' => StatusAtividade::cases(),
            'accountManagers' => $this->mostrarFormulario ? $usuarios->listarAtivosPorPapel(Papel::ACCOUNT_MANAGER) : [],
            'preVendas' => $this->mostrarFormulario ? $usuarios->listarAtivosPorPapel(Papel::PRE_VENDAS) : [],
            'primeiraAtividade' => $projeto['atividades'] === [],
            'clientes' => $this->trocandoCliente ? $parceiros->listarClientes(somenteAtivos: true) : [],
        ])->title('Projeto · '.$projeto['cliente']);
    }

    private function validarFormularioDeAtividade(): void
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
    }

    private static function data(string $valor): ?DateTimeImmutable
    {
        return $valor === '' ? null : new DateTimeImmutable($valor);
    }
}
