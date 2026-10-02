<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Projetos</h1>
        @unless($mostrarFormulario)
            <button type="button" class="btn btn-primary" wire:click="abrirFormulario">
                <i class="bi bi-plus-lg me-1"></i>Novo projeto
            </button>
        @endunless
    </div>

    @if (session('sucesso'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('sucesso') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if ($mostrarFormulario)
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-primary-subtle fw-semibold">Novo projeto</div>
            <form class="card-body" wire:submit="salvar">
                @error('geral') <div class="alert alert-danger">{{ $message }}</div> @enderror

                @if (empty($clientes) || empty($fornecedores))
                    <div class="alert alert-warning">
                        Para abrir um projeto é preciso ter ao menos um
                        <a href="{{ route('clientes.index') }}" wire:navigate>cliente</a> e um
                        <a href="{{ route('fornecedores.index') }}" wire:navigate>fornecedor</a> ativos.
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Cliente <span class="text-danger">*</span></label>
                        <select class="form-select @error('clienteId') is-invalid @enderror" wire:model="clienteId">
                            <option value="">Selecione…</option>
                            @foreach ($clientes as $c)
                                <option value="{{ $c['id'] }}">{{ $c['nome'] }}</option>
                            @endforeach
                        </select>
                        @error('clienteId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Código da oportunidade <small class="text-muted">(opcional)</small></label>
                        <input type="text" class="form-control @error('codigoOportunidade') is-invalid @enderror"
                               wire:model="codigoOportunidade" maxlength="50" placeholder="OPP-2026-0001">
                        @error('codigoOportunidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h6 class="mt-4">Fornecedores e soluções <small class="text-muted fw-normal">— mínimo 1 fornecedor; solução opcional</small></h6>
                @foreach ($vinculos as $i => $vinculo)
                    <div class="row g-2 align-items-start mb-2" wire:key="vinculo-{{ $i }}">
                        <div class="col-md-5">
                            <select class="form-select @error("vinculos.$i.fornecedor_id") is-invalid @enderror"
                                    wire:model.live="vinculos.{{ $i }}.fornecedor_id">
                                <option value="">Fornecedor…</option>
                                @foreach ($fornecedores as $f)
                                    <option value="{{ $f['id'] }}">{{ $f['nome'] }}</option>
                                @endforeach
                            </select>
                            @error("vinculos.$i.fornecedor_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            @php($solucoes = $solucoesPorFornecedor[$vinculo['fornecedor_id']] ?? [])
                            <select class="form-select" wire:model="vinculos.{{ $i }}.solucao_id" @disabled(empty($solucoes))>
                                <option value="">{{ empty($solucoes) ? 'Sem soluções cadastradas' : 'Sem solução específica' }}</option>
                                @foreach ($solucoes as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['nome'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            @if (count($vinculos) > 1)
                                <button type="button" class="btn btn-outline-danger" wire:click="removerVinculo({{ $i }})" title="Remover">
                                    <i class="bi bi-trash"></i></button>
                            @endif
                        </div>
                    </div>
                @endforeach
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="adicionarVinculo">
                    <i class="bi bi-plus"></i> Adicionar fornecedor/solução
                </button>

                <div class="mt-4 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-light" wire:click="cancelarFormulario">Cancelar</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading wire:target="salvar" class="spinner-border spinner-border-sm me-1"></span>Criar projeto
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Cliente</th><th>Oportunidade</th><th>Fornecedores</th><th>Status</th><th style="width: 22%">Atividades</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($projetos as $p)
                        @php($pct = $p['total_atividades'] ? intdiv($p['atividades_concluidas'] * 100, $p['total_atividades']) : 0)
                        <tr wire:key="projeto-{{ $p['id'] }}">
                            <td class="fw-semibold">{{ $p['cliente'] }}</td>
                            <td>{{ $p['codigo_oportunidade'] ?? '—' }}</td>
                            <td class="small">{{ implode(', ', $p['fornecedores']) }}</td>
                            <td><x-status-badge :status="$p['status']" /></td>
                            <td>
                                <div class="small text-muted mb-1">{{ $p['atividades_concluidas'] }}/{{ $p['total_atividades'] }} concluídas</div>
                                <div class="progress" style="height: 6px"><div class="progress-bar bg-success" style="width: {{ $pct }}%"></div></div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('projetos.show', $p['id']) }}" class="btn btn-sm btn-outline-primary" wire:navigate>Abrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Nenhum projeto cadastrado ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
