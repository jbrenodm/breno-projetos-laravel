<div x-data="{ modalAberto: false }" @projeto-salvo.window="modalAberto = false">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Painel de Projetos</h2>
            <p class="text-muted mb-0">Gerencie e acompanhe o ciclo de vida e as atividades dos projetos.</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" @click="modalAberto = true">
            <i class="bi bi-plus-lg"></i> Novo Projeto
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total de Projetos</h6>
                        <span class="fs-3 fw-bold text-dark">{{ $projetos->count() }}</span>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="bi bi-folder fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-warning border-4 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Em Andamento</h6>
                        <span class="fs-3 fw-bold text-dark">{{ $projetos->where('status', 'Em Andamento')->count() }}</span>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="bi bi-gear-wide-connected fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm border-start border-success border-4 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Concluídos</h6>
                        <span class="fs-3 fw-bold text-dark">{{ $projetos->where('status', 'Concluído')->count() }}</span>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="bi bi-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted uppercase font-monospace small">
                        <tr>
                            <th class="ps-4 py-3">ID do Projeto</th>
                            <th class="py-3">Status do Macro-Projeto</th>
                            <th class="py-3 text-center">Atividades Concluídas</th>
                            <th class="pe-4 py-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projetos as $projeto)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">#{{ substr($projeto->id, 0, 8) }}...</span>
                                        <small class="text-muted">Cliente: {{ substr($projeto->cliente_id, 0, 8) }}...</small>
                                    </div>
                                </td>
                                <td>
                                    @if($projeto->status === 'Concluído')
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-check2-all me-1"></i> Concluído
                                        </span>
                                    @elseif($projeto->status === 'Em Andamento')
                                        <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-play-fill me-1"></i> Em Andamento
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-folder-symlink me-1"></i> Não Iniciado
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $total = $projeto->atividades->count();
                                        $concluidas = $projeto->atividades->where('status', 'Concluída')->count();
                                    @endphp
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="fw-semibold text-dark">{{ $concluidas }} / {{ $total }}</span>
                                        <div class="progress w-50 mt-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: {{ $total > 0 ? ($concluidas / $total) * 100 : 0 }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="/projetos/{{ $projeto->id }}" class="btn btn-sm btn-outline-secondary px-3 shadow-sm">
                                        <i class="bi bi-eye"></i> Gerenciar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-opacity-50 text-secondary"></i>
                                    Nenhum projeto encontrado no banco de dados local.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="modalAberto" x-transition style="display: none;">
        
        <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2 text-success"></i>Abrir Novo Projeto</h5>
                        <button type="button" class="btn-close btn-close-white" @click="modalAberto = false"></button>
                    </div>
                    
                    <form wire:submit="salvar">
                        <div class="modal-body text-start">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">Código da Oportunidade (Opcional)</label>
                                <input type="text" class="form-select @error('codigoOportunidade') is-invalid @enderror" 
                                    wire:model="codigoOportunidade" placeholder="Ex: OP-2026-XYZ">
                                @error('codigoOportunidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">ID do Cliente</label>
                                <select class="form-select @error('clienteId') is-invalid @enderror" wire:model="clienteId" required>
                                    <option value="">Selecione um cliente fictício...</option>
                                    <option value="da7bc111-e123-4444-9999-000000000001">Banco Regional S/A</option>
                                    <option value="da7bc222-e123-4444-9999-000000000002">Logística Avançada Ltda</option>
                                </select>
                                @error('clienteId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">Fornecedor Estratégico</label>
                                <select class="form-select @error('fornecedorId') is-invalid @enderror" wire:model.live="fornecedorId" required>
                                    <option value="">Selecione um fornecedor homologado...</option>
                                    @foreach($fornecedores as $forn)
                                        <option value="{{ $forn->id }}">{{ $forn->nome_fantasia }}</option>
                                    @endforeach
                                </select>
                                @error('fornecedorId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">Soluções Homologadas do Catálogo</label>
                                @if($fornecedorId)
                                    @php $fornSelecionado = $fornecedores->firstWhere('id', $fornecedorId); @endphp
                                    @if($fornSelecionado && $fornSelecionado->solucoes->isNotEmpty())
                                        <div class="p-3 bg-light rounded border @error('solucoesSelecionadas') border-danger @enderror" style="max-height: 150px; overflow-y: auto;">
                                            @foreach($fornSelecionado->solucoes as $sol)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" value="{{ $sol->id }}" id="sol_{{ $sol->id }}" wire:model="solucoesSelecionadas">
                                                    <label class="form-check-label text-dark small" for="sol_{{ $sol->id }}">{{ $sol->nome }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning py-2 mb-0 small">Este fornecedor não possui soluções ativas.</div>
                                    @endif
                                @else
                                    <div class="alert alert-secondary py-2 mb-0 small text-center text-muted">Selecione um fornecedor para ver as soluções.</div>
                                @endif
                                @error('solucoesSelecionadas') <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                            </div>

                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary px-4" @click="modalAberto = false">Cancelar</button>
                            <button type="submit" class="btn btn-success px-4 shadow-sm"><i class="bi bi-check-lg me-1"></i> Confirmar Abertura</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>