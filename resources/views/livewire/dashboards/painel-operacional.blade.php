@inject('papeis', \App\Support\NomesDePapeis::class)
@php
    $AM = \Src\Identidade\Domain\Papel::ACCOUNT_MANAGER;
    $PV = \Src\Identidade\Domain\Papel::PRE_VENDAS;
@endphp
@php
    $fmt = fn (string $d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
    $prazo = fn (int $dias) => match ($dias) { 0 => 'vence hoje', 1 => 'vence amanhã', default => "em {$dias} dias" };
    $indicadores = [
        ['rotulo' => 'Atividades abertas', 'valor' => $painel['abertas'], 'icone' => 'bi-list-task', 'cor' => null,
            'nota' => 'não concluídas'],
        ['rotulo' => 'Atrasadas', 'valor' => $painel['atrasadas'], 'icone' => 'bi-exclamation-octagon-fill', 'cor' => '#d03b3b',
            'nota' => 'abertas com data limite vencida'],
        ['rotulo' => 'Vencem em 7 dias', 'valor' => $painel['vencem_em_7_dias'], 'icone' => 'bi-clock-fill', 'cor' => '#fab219',
            'nota' => 'até '.$fmt($hoje->modify('+7 days')->format('Y-m-d'))],
        ['rotulo' => 'Concluídas no mês', 'valor' => $painel['concluidas_no_mes'], 'icone' => 'bi-check-circle-fill', 'cor' => '#0ca30c',
            'nota' => ucfirst(\Illuminate\Support\Carbon::instance($hoje)->locale('pt_BR')->translatedFormat('F \d\e Y'))],
    ];
@endphp
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="small text-muted">Dashboards</div>
            <h1 class="h3 mb-0">Painel operacional</h1>
            <div class="small text-muted">O que precisa de atenção agora · {{ $fmt($hoje->format('Y-m-d')) }} · projetos cancelados não entram</div>
        </div>
        <a href="{{ route('dashboards.atividades') }}" class="btn btn-outline-primary" wire:navigate>
            <i class="bi bi-list-check me-1"></i>Todas as atividades</a>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($indicadores as $i)
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-body-secondary d-flex align-items-center gap-1">
                            <i class="bi {{ $i['icone'] }}" @if($i['cor']) style="color: {{ $i['cor'] }}" @endif aria-hidden="true"></i>
                            {{ $i['rotulo'] }}
                        </div>
                        <div class="fs-2 fw-semibold lh-sm mt-1">{{ number_format($i['valor'], 0, ',', '.') }}</div>
                        <div class="small text-muted">{{ $i['nota'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <x-grafico-barras :titulo="'Atividades atrasadas por '.$papeis->nome($AM)" subtitulo="Abertas com data limite vencida"
                              :itens="$painel['atrasadas_por_am']" vazio="Nenhuma atividade atrasada." />
        </div>
        <div class="col-lg-6">
            <x-grafico-barras titulo="Atividades atrasadas por Cliente" subtitulo="Abertas com data limite vencida · clique para ver as atividades do cliente"
                              :itens="array_map(fn ($c) => $c + ['url' => route('dashboards.atividades', ['cliente' => $c['id']])], $painel['atrasadas_por_cliente'])"
                              vazio="Nenhuma atividade atrasada." />
        </div>
        <div class="col-12">
            <x-grafico-barras titulo="Atividades atrasadas por Projeto" subtitulo="Abertas com data limite vencida · clique para abrir o projeto"
                              :itens="array_map(fn ($p) => $p + ['url' => route('projetos.show', $p['id'])], $painel['atrasadas_por_projeto'])"
                              vazio="Nenhuma atividade atrasada." />
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Próximos vencimentos <span class="small text-muted fw-normal">— abertas que vencem em até 7 dias</span></div>
        <div class="list-group list-group-flush">
            @forelse ($painel['proximos_vencimentos'] as $a)
                <a href="{{ route('projetos.show', $a['projeto_id']) }}" class="list-group-item list-group-item-action" wire:navigate wire:key="venc-{{ $a['id'] }}">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">{{ $a['cliente'] }}</div>
                            <div>{{ $a['descricao'] }}</div>
                            <div class="small text-muted">
                                <span class="badge text-bg-light border">{{ $a['tipo'] }}</span>
                                {{ $papeis->sigla($AM) }}: {{ $a['account_manager'] }} · {{ $papeis->sigla($PV) }}: {{ $a['pre_vendas'] }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ $fmt($a['data_limite']) }}</div>
                            <div class="small text-muted"><i class="bi bi-clock" aria-hidden="true"></i> {{ $prazo($a['dias_restantes']) }}</div>
                            <x-status-badge :status="$a['status']" class="mt-1" />
                        </div>
                    </div>
                </a>
            @empty
                <div class="list-group-item text-center text-muted py-4">Nenhuma atividade vence nos próximos 7 dias.</div>
            @endforelse
        </div>
    </div>
</div>
