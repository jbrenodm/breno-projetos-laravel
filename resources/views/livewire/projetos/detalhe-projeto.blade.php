@inject('papeis', \App\Support\NomesDePapeis::class)
@php
    $AM = \Src\Identidade\Domain\Papel::ACCOUNT_MANAGER;
    $PV = \Src\Identidade\Domain\Papel::PRE_VENDAS;
@endphp
@php
    $cancelado = $projeto['status'] === 'Cancelado';
    $editando = $atividadeEditandoId !== null;
    $fmt = fn (?string $d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
@endphp
<div>
    <a href="{{ route('projetos.index') }}" class="small text-decoration-none" wire:navigate><i class="bi bi-arrow-left"></i> Projetos</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-2 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $projeto['cliente'] }} <x-status-badge :status="$projeto['status']" class="fs-6 align-middle" />
                @if (! $cancelado && ! $trocandoCliente)
                    <button class="btn btn-sm btn-link text-decoration-none align-middle" wire:click="abrirTrocaDeCliente"><i class="bi bi-pencil"></i> Trocar cliente</button>
                @endif
            </h1>
            @if ($trocandoCliente)
                <form class="d-flex flex-wrap gap-2 align-items-start my-2" wire:submit="salvarCliente">
                    <div>
                        <select class="form-select form-select-sm @error('novoClienteId') is-invalid @enderror" wire:model="novoClienteId">
                            <option value="">Selecione…</option>
                            @foreach ($clientes as $c) <option value="{{ $c['id'] }}">{{ $c['nome'] }}</option> @endforeach
                        </select>
                        @error('novoClienteId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-sm btn-primary">Salvar</button>
                    <button type="button" class="btn btn-sm btn-light" wire:click="cancelarTrocaDeCliente">Cancelar</button>
                </form>
            @endif
            <div class="text-muted small">
                Oportunidade: <strong>{{ $projeto['codigo_oportunidade'] ?? '—' }}</strong>
                · ID interno: <code>{{ $projeto['id'] }}</code>
            </div>
            <div class="mt-2">
                @foreach ($projeto['fornecedores'] as $f)
                    <span class="badge rounded-pill text-bg-light border me-1">
                        {{ $f['fornecedor'] }}@if($f['solucao']) · {{ $f['solucao'] }}@endif
                    </span>
                @endforeach
            </div>
        </div>
        <div class="d-flex gap-2">
            @unless ($cancelado)
                @unless ($mostrarFormulario)
                    <button class="btn btn-primary" wire:click="abrirFormulario"><i class="bi bi-plus-lg me-1"></i>Nova atividade</button>
                @endunless
                <button class="btn btn-outline-danger" wire:click="cancelarProjeto"
                        wire:confirm="Cancelar este projeto? Ele não poderá mais receber atividades.">Cancelar projeto</button>
            @endunless
        </div>
    </div>

    @if (session('sucesso'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sucesso') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror

    @if ($mostrarFormulario)
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary-subtle fw-semibold">{{ $editando ? 'Editar atividade' : 'Nova atividade' }}</div>
            <form class="card-body" wire:submit="{{ $editando ? 'salvarEdicao' : 'registrarAtividade' }}">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('descricao') is-invalid @enderror" rows="2" wire:model="descricao"></textarea>
                        @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select @error('tipoId') is-invalid @enderror" wire:model="tipoId">
                            @foreach ($tipos as $t) <option value="{{ $t['id'] }}">{{ $t['nome'] }}</option> @endforeach
                        </select>
                        @error('tipoId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" wire:model.live="status" @disabled($editando)>
                            @foreach ($statusPossiveis as $s) <option value="{{ $s->value }}">{{ $s->value }}</option> @endforeach
                        </select>
                        @if ($editando) <div class="form-text">Use os botões de status da atividade.</div> @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ $papeis->nome($AM) }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('accountManagerId') is-invalid @enderror" wire:model="accountManagerId">
                            <option value="">Selecione…</option>
                            @foreach ($accountManagers as $u) <option value="{{ $u['id'] }}">{{ $u['nome'] }}</option> @endforeach
                        </select>
                        @error('accountManagerId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ $papeis->nome($PV) }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('preVendasId') is-invalid @enderror" wire:model="preVendasId">
                            <option value="">Selecione…</option>
                            @foreach ($preVendas as $u) <option value="{{ $u['id'] }}">{{ $u['nome'] }}</option> @endforeach
                        </select>
                        @error('preVendasId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 small text-muted mt-0">
                        @if ($editando)
                            <i class="bi bi-info-circle"></i> Alterações em atividades concluídas corrigem o histórico.
                        @elseif ($primeiraAtividade)
                            <i class="bi bi-info-circle"></i> Primeira atividade do projeto: informe {{ $papeis->nome($AM) }} e {{ $papeis->nome($PV) }}.
                        @else
                            <i class="bi bi-info-circle"></i> {{ $papeis->sigla($AM) }} e {{ $papeis->sigla($PV) }} pré-preenchidos com os da última atividade — altere se necessário.
                        @endif
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Data de entrada <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('dataEntrada') is-invalid @enderror" wire:model="dataEntrada">
                        @error('dataEntrada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data limite <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('dataLimite') is-invalid @enderror" wire:model="dataLimite">
                        @error('dataLimite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @if ($status !== 'Não Iniciada')
                        <div class="col-md-3">
                            <label class="form-label">Data de início</label>
                            <input type="date" class="form-control @error('dataInicio') is-invalid @enderror" wire:model="dataInicio">
                            <div class="form-text">Vazio = data de entrada.</div>
                        </div>
                    @endif
                    @if ($status === 'Concluída')
                        <div class="col-md-3">
                            <label class="form-label">Data de término</label>
                            <input type="date" class="form-control @error('dataTermino') is-invalid @enderror" wire:model="dataTermino">
                            <div class="form-text">Vazio = hoje.</div>
                        </div>
                    @endif

                    <div class="col-12">
                        <label class="form-label">Observação <small class="text-muted">(opcional)</small></label>
                        <textarea class="form-control" rows="2" wire:model="observacao"></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-light" wire:click="fecharFormulario">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading wire:target="registrarAtividade,salvarEdicao" class="spinner-border spinner-border-sm me-1"></span>{{ $editando ? 'Salvar alterações' : 'Registrar atividade' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    @error('status') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Atividades <span class="badge text-bg-secondary">{{ count($projeto['atividades']) }}</span></div>
        <div class="list-group list-group-flush">
            @forelse ($projeto['atividades'] as $a)
                <div class="list-group-item" wire:key="atividade-{{ $a['id'] }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                <span class="text-muted">#{{ $a['sequencia'] }}</span> {{ $a['descricao'] }}
                            </div>
                            <div class="small text-muted mt-1">
                                <span class="badge text-bg-light border">{{ $a['tipo'] }}</span>
                                <i class="bi bi-person ms-2"></i> {{ $papeis->sigla($AM) }}: {{ $a['account_manager'] }}
                                · {{ $papeis->sigla($PV) }}: {{ $a['pre_vendas'] }}
                            </div>
                            <div class="small mt-1">
                                Entrada {{ $fmt($a['data_entrada']) }}
                                · Limite <span @class(['text-danger fw-semibold' => $a['atrasada']])>{{ $fmt($a['data_limite']) }}@if($a['atrasada']) (atrasada)@endif</span>
                                · Início {{ $fmt($a['data_inicio']) }}
                                · Término {{ $fmt($a['data_termino']) }}
                            </div>
                            @if ($a['observacao'])
                                <div class="small fst-italic text-body-secondary mt-1"><i class="bi bi-chat-left-text"></i> {{ $a['observacao'] }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <x-status-badge :status="$a['status']" />
                            @if (! $cancelado && $atividadeEditandoId !== $a['id'])
                                <button class="btn btn-sm btn-link text-decoration-none p-0 ms-2" wire:click="editarAtividade('{{ $a['id'] }}')" title="Editar atividade">
                                    <i class="bi bi-pencil"></i> Editar
                                </button>
                            @endif
                            @if (! $cancelado && $a['proximos_status'] && $atividadeEmEdicao !== $a['id'])
                                <div class="btn-group btn-group-sm mt-2 d-flex">
                                    @foreach ($a['proximos_status'] as $proximo)
                                        <button class="btn btn-outline-secondary" wire:click="prepararMudancaDeStatus('{{ $a['id'] }}', '{{ $proximo }}')">
                                            {{ match($proximo) { 'Em Andamento' => $a['status'] === 'Parada' ? 'Retomar' : 'Iniciar', 'Parada' => 'Parar', 'Concluída' => 'Concluir', default => $proximo } }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($atividadeEmEdicao === $a['id'])
                        <form class="row g-2 align-items-end mt-2 p-2 bg-body-tertiary rounded" wire:submit="confirmarMudancaDeStatus">
                            <div class="col-auto">Mudar para <strong>{{ $novoStatus }}</strong></div>
                            @if (in_array($novoStatus, ['Em Andamento', 'Concluída'], true) && ($novoStatus === 'Concluída' || ! $a['data_inicio']))
                                <div class="col-auto">
                                    <label class="form-label small mb-0">{{ $novoStatus === 'Concluída' ? 'Data de término' : 'Data de início' }}</label>
                                    <input type="date" class="form-control form-control-sm" wire:model="dataDoStatus">
                                </div>
                            @endif
                            <div class="col-auto">
                                <button class="btn btn-sm btn-primary">Confirmar</button>
                                <button type="button" class="btn btn-sm btn-light" wire:click="cancelarMudancaDeStatus">Cancelar</button>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">Nenhuma atividade registrada.</div>
            @endforelse
        </div>
    </div>
</div>
