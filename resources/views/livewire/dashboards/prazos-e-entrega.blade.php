@php
    use App\Livewire\Dashboards\PrazosEEntrega;
    $fmt = fn (string $d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
    $n = fn (?float $v) => PrazosEEntrega::numero($v);
    $indicadores = [
        ['rotulo' => 'Concluídas no período', 'valor' => (string) $dados['concluidas'], 'icone' => 'bi-check2-all', 'cor' => null,
            'nota' => $dados['com_atraso'].' com atraso'],
        ['rotulo' => 'Entregues no prazo', 'valor' => $dados['percentual_no_prazo'] === null ? '—' : $n($dados['percentual_no_prazo']).'%',
            'icone' => 'bi-bullseye', 'cor' => null, 'nota' => $dados['no_prazo'].' de '.$dados['concluidas']],
        ['rotulo' => 'Tempo médio de execução', 'valor' => $dados['execucao_media_dias'] === null ? '—' : $n($dados['execucao_media_dias']).' dias',
            'icone' => 'bi-hourglass-split', 'cor' => null, 'nota' => 'do início ao término'],
        ['rotulo' => 'Atraso médio', 'valor' => $dados['atraso_medio_dias'] === null ? '—' : $n($dados['atraso_medio_dias']).' dias',
            'icone' => 'bi-exclamation-octagon-fill', 'cor' => '#d03b3b', 'nota' => 'além da data limite, nas entregues com atraso'],
    ];
@endphp
<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="small text-muted">Dashboards</div>
            <h1 class="h3 mb-0">Prazos e entrega</h1>
            <div class="small text-muted">Atividades concluídas de {{ $fmt($dados['inicio']) }} a {{ $fmt($dados['fim']) }} · projetos cancelados não entram</div>
        </div>
        <div class="btn-group" role="group" aria-label="Período">
            @foreach ($periodos as $p)
                <button type="button" wire:click="$set('meses', {{ $p }})" aria-pressed="{{ $meses === $p ? 'true' : 'false' }}"
                        @class(['btn', 'btn-primary' => $meses === $p, 'btn-outline-primary' => $meses !== $p])>{{ $p }} meses</button>
            @endforeach
        </div>
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
                        <div class="fs-2 fw-semibold lh-sm mt-1">{{ $i['valor'] }}</div>
                        <div class="small text-muted">{{ $i['nota'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mb-3">
        <x-grafico-linha titulo="Cumprimento de prazo por mês" subtitulo="% das atividades concluídas no mês que foram entregues até a data limite"
                         :pontos="$pontos" vazio="Nenhuma atividade concluída no período." />
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-grafico-barras titulo="Tempo médio de execução por tipo" subtitulo="Dias do início ao término"
                              :itens="PrazosEEntrega::barrasDeDias($dados['execucao_por_tipo'])" vazio="Nenhuma atividade concluída com data de início." />
        </div>
        <div class="col-lg-4">
            <x-grafico-barras titulo="Atraso médio por tipo" subtitulo="Dias além da data limite"
                              :itens="PrazosEEntrega::barrasDeDias($dados['atraso_por_tipo'])" vazio="Nenhuma entrega com atraso no período." />
        </div>
        <div class="col-lg-4">
            <x-grafico-barras titulo="Atraso médio por Account Manager" subtitulo="Dias além da data limite"
                              :itens="PrazosEEntrega::barrasDeDias($dados['atraso_por_am'])" vazio="Nenhuma entrega com atraso no período." />
        </div>
    </div>
</div>
