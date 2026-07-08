<div>
    <div class="mb-4">
        <a href="/" class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" style="width: max-content;">
            <i class="bi bi-arrow-left"></i> Voltar ao Painel
        </a>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <span class="badge bg-secondary mb-2 font-monospace">ID: {{ $projeto->id }}</span>
                <h2 class="fw-bold text-dark mb-1">
                    <i class="bi bi-folder2-open text-primary me-2"></i>Central de Atividades Técnico-Comerciais
                </h2>
                <p class="text-muted mb-0">
                    Cliente ID: <strong class="text-dark">{{ substr($projeto->cliente_id, 0, 8) }}...</strong> 
                    @if($projeto->codigo_oportunidade)
                        | Oportunidade: <span class="badge bg-light text-dark border">{{ $projeto->codigo_oportunidade }}</span>
                    @endif
                </p>
            </div>
            
            @php
                $total = $projeto->atividades->count();
                $concluidas = $projeto->atividades->where('status', 'Concluída')->count();
                $percentual = $total > 0 ? round(($concluidas / $total) * 100) : 0;
            @endphp
            <div class="text-end" style="min-width: 200px;">
                <span class="small text-muted fw-semibold d-block mb-1">Progresso Geral: {{ $percentual }}%</span>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $percentual }}%"></div>
                </div>
                <small class="text-muted font-monospace">{{ $concluidas }} de {{ $total }} concluídas</small>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white fw-bold py-3">
            <i class="bi bi-sliders me-2 text-success"></i> Nova Atividade (Análise de Requisitos)
        </div>
        
        <div class="card-body bg-light border-bottom p-4">
            <form wire:submit="adicionarAtividade">
                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-bold text-secondary">Título da Atividade</label>
                        <input type="text" class="form-control shadow-sm @error('titulo') is-invalid @enderror" 
                               wire:model="titulo" placeholder="Ex: Desenhar topologia de rede...">
                        @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-secondary">Tipo / Etapa</label>
                        <select class="form-select shadow-sm" wire:model="tipo">
                            <option value="Mapeamento">Mapeamento</option>
                            <option value="Homologação">Homologação</option>
                            <option value="Implantação">Implantação</option>
                            <option value="Comercial">Comercial</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-secondary">Sequência (Ordem)</label>
                        <input type="number" class="form-control shadow-sm @error('ordem') is-invalid @enderror" 
                               wire:model="ordem" min="1">
                        @error('ordem') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100 shadow-sm py-2">
                            <i class="bi bi-plus-lg me-1"></i> Inserir
                        </button>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Descrição Detalhada / Playbook Técnico (Opcional)</label>
                        <textarea class="form-control shadow-sm @error('descricao') is-invalid @enderror" 
                                  wire:model="descricao" rows="2" placeholder="Descreva os entregáveis ou observações desta atividade..."></textarea>
                        @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($projeto->atividades as $atividade)
                    <div class="list-group-item d-flex align-items-start justify-content-between py-3 px-4 {{ $atividade->status === 'Concluída' ? 'bg-light bg-opacity-50' : '' }}">
                        <div class="d-flex align-items-start gap-3">
                            <input class="form-check-input border-secondary p-2.5 cursor-pointer mt-1" 
                                   type="checkbox" 
                                   id="atv_{{ $atividade->id }}"
                                   {{ $atividade->status === 'Concluída' ? 'checked' : '' }}
                                   wire:click="alternarStatusAtividade('{{ $atividade->id }}')">
                            
                            <label class="form-check-label cursor-pointer" for="atv_{{ $atividade->id }}">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-secondary font-monospace">#{{ $atividade->ordem }}</span>
                                    
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 small fw-semibold">
                                        {{ $atividade->tipo }}
                                    </span>

                                    <span class="fw-bold {{ $atividade->status === 'Concluída' ? 'text-decoration-line-through text-muted' : 'text-dark' }}">
                                        {{ $atividade->titulo }}
                                    </span>
                                </div>
                                
                                @if($atividade->descricao)
                                    <p class="text-muted small mb-0 mt-1 ps-1 bg-white p-2 rounded border border-light shadow-sm" style="max-width: 700px;">
                                        {{ $atividade->descricao }}
                                    </p>
                                @endif
                            </label>
                        </div>

                        <div>
                            @if($atividade->status === 'Concluída')
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-3 py-1.5 small">
                                    <i class="bi bi-check-circle-fill me-1"></i> Concluída
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1.5 small">
                                    <i class="bi bi-clock-history me-1"></i> Pendente
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-clipboard-x fs-2 d-block mb-2"></i>
                        Nenhuma atividade estruturada para este projeto ainda.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>