@php
    $fmt = fn (?string $d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
@endphp
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="small text-muted">Dashboards</div>
            <h1 class="h3 mb-0">Todas as atividades <span class="badge text-bg-secondary fs-6 align-middle">{{ count($atividades) }}</span></h1>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <label class="small text-muted" for="ordenarPor">Ordenar por</label>
            <select id="ordenarPor" class="form-select w-auto" wire:model.live="ordenarPor">
                @foreach ($ordenacoes as $o) <option value="{{ $o->value }}">{{ $o->rotulo() }}</option> @endforeach
            </select>
            <button class="btn btn-outline-secondary" wire:click="inverterSentido"
                    title="{{ $sentido === 'desc' ? 'Decrescente — clique para crescente' : 'Crescente — clique para decrescente' }}">
                <i class="bi {{ $sentido === 'desc' ? 'bi-sort-down' : 'bi-sort-up' }}"></i>
                {{ $sentido === 'desc' ? 'Decrescente' : 'Crescente' }}
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-funnel me-1"></i>Filtros
                @if ($filtrosAtivos) <span class="badge text-bg-primary">{{ $filtrosAtivos }}</span> @endif
            </span>
            @if ($filtrosAtivos)
                <button class="btn btn-sm btn-link text-decoration-none" wire:click="limparFiltros">Limpar filtros</button>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-lg-6">
                    <input type="search" class="form-control" placeholder="Buscar cliente ou atividade…"
                           wire:model.live.debounce.400ms="busca" maxlength="100" aria-label="Busca">
                </div>
                <div class="col-6 col-lg-3">
                    <select class="form-select" wire:model.live="status" aria-label="Status">
                        <option value="">Todos os status</option>
                        @foreach ($statusPossiveis as $s) <option value="{{ $s->value }}">{{ $s->value }}</option> @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <select class="form-select" wire:model.live="tipo" aria-label="Tipo">
                        <option value="">Todos os tipos</option>
                        @foreach ($tipos as $t) <option value="{{ $t->value }}">{{ $t->value }}</option> @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <select class="form-select" wire:model.live="clienteId" aria-label="Cliente">
                        <option value="">Todos os clientes</option>
                        @foreach ($clientes as $c) <option value="{{ $c['id'] }}">{{ $c['nome'] }}</option> @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <select class="form-select" wire:model.live="fornecedorId" aria-label="Fornecedor">
                        <option value="">Todos os fornecedores</option>
                        @foreach ($fornecedores as $f) <option value="{{ $f['id'] }}">{{ $f['nome'] }}</option> @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <select class="form-select" wire:model.live="accountManagerId" aria-label="Account Manager">
                        <option value="">Todos os AMs</option>
                        @foreach ($accountManagers as $u) <option value="{{ $u['id'] }}">{{ $u['nome'] }}</option> @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <select class="form-select" wire:model.live="preVendasId" aria-label="Pré-vendas">
                        <option value="">Todos os PVs</option>
                        @foreach ($preVendas as $u) <option value="{{ $u['id'] }}">{{ $u['nome'] }}</option> @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                    <span class="small text-muted text-nowrap">Entrada de</span>
                    <input type="date" class="form-control" wire:model.live="entradaDe" aria-label="Entrada a partir de">
                    <span class="small text-muted">até</span>
                    <input type="date" class="form-control" wire:model.live="entradaAte" aria-label="Entrada até">
                </div>
                <div class="col-12 col-md-auto d-flex align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="somenteAtrasadas" wire:model.live="somenteAtrasadas">
                        <label class="form-check-label" for="somenteAtrasadas">Somente atrasadas</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="list-group list-group-flush">
            @forelse ($atividades as $a)
                <div class="list-group-item py-3" wire:key="atividade-{{ $a['id'] }}">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <div class="fs-5 fw-bold text-primary-emphasis">
                                {{ $a['cliente'] }}
                                @if ($a['projeto_status'] === 'Cancelado')
                                    <x-status-badge status="Cancelado" class="fs-6 align-middle fw-normal" />
                                @endif
                            </div>
                            <div class="fw-semibold mt-1">{{ $a['descricao'] }}</div>
                            <div class="small text-muted mt-1">
                                <span class="badge text-bg-light border">{{ $a['tipo'] }}</span>
                                <i class="bi bi-person ms-2"></i> AM: {{ $a['account_manager'] }} · PV: {{ $a['pre_vendas'] }}
                                @if ($a['fornecedores']) · <i class="bi bi-building"></i> {{ implode(', ', $a['fornecedores']) }} @endif
                                @if ($a['codigo_oportunidade']) · {{ $a['codigo_oportunidade'] }} @endif
                            </div>
                            <div class="small mt-1">
                                Entrada {{ $fmt($a['data_entrada']) }}
                                · Limite <span @class(['text-danger fw-semibold' => $a['atrasada']])>{{ $fmt($a['data_limite']) }}@if($a['atrasada']) (atrasada)@endif</span>
                                · Início {{ $fmt($a['data_inicio']) }}
                                · Término {{ $fmt($a['data_termino']) }}
                            </div>
                        </div>
                        <div class="text-end">
                            <x-status-badge :status="$a['status']" />
                            <div class="mt-2">
                                <a href="{{ route('projetos.show', $a['projeto_id']) }}" class="btn btn-sm btn-outline-primary" wire:navigate>Abrir projeto</a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    {{ $filtrosAtivos ? 'Nenhuma atividade encontrada com esses filtros.' : 'Nenhuma atividade registrada.' }}
                </div>
            @endforelse
        </div>
    </div>
</div>
