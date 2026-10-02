@props(['titulo', 'subtitulo' => null, 'pontos', 'maximo' => 100, 'sufixo' => '%', 'vazio' => 'Nada a exibir.'])
{{--
    Linha de uma série sobre um eixo de 0 a $maximo. Cada ponto: rotulo (eixo X), valor (?float; nulo = sem ponto, a linha quebra)
    e detalhe (texto do tooltip). Hover/foco em cada coluna mostra guia vertical + tooltip. A tabela abaixo é a visão acessível.
--}}
@php
    $altura = 200;
    $n = max(count($pontos), 1);
    $x = fn (int $i) => round(($i + 0.5) / $n * 100, 3).'%';
    $y = fn (float $v) => round($altura - ($v / $maximo) * $altura, 1);
    $marcas = [0, 25, 50, 75, 100];
    $temDados = collect($pontos)->contains(fn ($p) => $p['valor'] !== null);
    $fmt = fn (?float $v) => $v === null ? '—' : number_format($v, $v == (int) $v ? 0 : 1, ',', '.').$sufixo;
@endphp
<div {{ $attributes->class('card shadow-sm h-100 grafico-linha') }}>
    <div class="card-body">
        <h2 class="h6 mb-0">{{ $titulo }}</h2>
        @if ($subtitulo) <div class="small text-muted mb-3">{{ $subtitulo }}</div> @endif

        @if (! $temDados)
            <div class="text-muted small py-5 text-center"><i class="bi bi-graph-up me-1"></i>{{ $vazio }}</div>
        @else
            <div class="gl-area" style="--gl-altura: {{ $altura }}px">
                <div class="gl-eixo-y" aria-hidden="true">
                    @foreach ($marcas as $m)
                        <span style="top: {{ $y($m / 100 * $maximo) }}px">{{ $fmt($m / 100 * $maximo) }}</span>
                    @endforeach
                </div>
                <div class="gl-plot">
                    <svg width="100%" height="{{ $altura }}" aria-hidden="true" focusable="false">
                        @foreach ($marcas as $m)
                            <line x1="0" x2="100%" y1="{{ $y($m / 100 * $maximo) }}" y2="{{ $y($m / 100 * $maximo) }}"
                                  class="{{ $m === 0 ? 'gl-base' : 'gl-grade' }}" />
                        @endforeach
                        @foreach ($pontos as $i => $p)
                            @if ($i > 0 && $p['valor'] !== null && $pontos[$i - 1]['valor'] !== null)
                                <line x1="{{ $x($i - 1) }}" y1="{{ $y($pontos[$i - 1]['valor']) }}" x2="{{ $x($i) }}" y2="{{ $y($p['valor']) }}" class="gl-linha" />
                            @endif
                        @endforeach
                        @foreach ($pontos as $i => $p)
                            @if ($p['valor'] !== null)
                                <circle cx="{{ $x($i) }}" cy="{{ $y($p['valor']) }}" r="4" class="gl-ponto" />
                            @endif
                        @endforeach
                    </svg>
                    <div class="gl-colunas">
                        @foreach ($pontos as $i => $p)
                            <div class="gl-coluna" tabindex="0" aria-label="{{ $p['detalhe'] }}">
                                <span class="gl-guia"></span>
                                <span class="gl-tooltip @if($i >= $n / 2) gl-tooltip-esq @endif"
                                      @if($p['valor'] !== null) style="top: {{ max(0, $y($p['valor']) - 34) }}px" @endif role="tooltip">{{ $p['detalhe'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="gl-eixo-x" aria-hidden="true">
                        @foreach ($pontos as $i => $p)
                            <span @class(['gl-rotulo-oculto' => $n > 6 && $i % 3 !== 0])>{{ $p['rotulo'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <details class="mt-3 small">
                <summary class="text-muted">Ver em tabela</summary>
                <table class="table table-sm mt-2 mb-0">
                    <tbody>
                        @foreach ($pontos as $p)
                            <tr><td>{{ $p['rotulo'] }}</td><td class="text-end" style="font-variant-numeric: tabular-nums">{{ $p['detalhe'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @endif
    </div>
</div>

@once
    <style>
        .grafico-linha { --gl-serie: #2a78d6; --gl-grade: #e1e0d9; --gl-base: #c3c2b7; --gl-superficie: var(--bs-body-bg); }
        [data-bs-theme="dark"] .grafico-linha { --gl-serie: #3987e5; --gl-grade: #2c2c2a; --gl-base: #383835; }
        .gl-area { display: grid; grid-template-columns: 2.75rem 1fr; }
        .gl-eixo-y { position: relative; height: var(--gl-altura); }
        .gl-eixo-y span { position: absolute; right: .5rem; transform: translateY(-50%); font-size: .75rem;
            color: var(--bs-secondary-color); font-variant-numeric: tabular-nums; }
        .gl-plot { position: relative; min-width: 0; }
        .gl-plot svg { display: block; overflow: visible; }
        .gl-grade { stroke: var(--gl-grade); stroke-width: 1; }
        .gl-base { stroke: var(--gl-base); stroke-width: 1; }
        .gl-linha { stroke: var(--gl-serie); stroke-width: 2; stroke-linecap: round; }
        .gl-ponto { fill: var(--gl-serie); stroke: var(--gl-superficie); stroke-width: 2; }
        .gl-colunas { position: absolute; inset: 0 0 auto 0; height: var(--gl-altura); display: flex; }
        .gl-coluna { position: relative; flex: 1 1 0; outline: none; cursor: default; }
        .gl-guia { position: absolute; left: 50%; top: 0; bottom: 0; border-left: 1px solid var(--gl-base); opacity: 0; }
        .gl-tooltip { position: absolute; left: calc(50% + 8px); top: 0; z-index: 5; white-space: nowrap; pointer-events: none;
            padding: .25rem .5rem; font-size: .75rem; border-radius: .375rem;
            background: var(--bs-body-color); color: var(--bs-body-bg); opacity: 0; transition: opacity .1s; }
        .gl-tooltip-esq { left: auto; right: calc(50% + 8px); }
        .gl-coluna:hover .gl-guia, .gl-coluna:focus-visible .gl-guia,
        .gl-coluna:hover .gl-tooltip, .gl-coluna:focus-visible .gl-tooltip { opacity: 1; }
        .gl-coluna:focus-visible { box-shadow: inset 0 0 0 2px var(--gl-serie); border-radius: .25rem; }
        .gl-eixo-x { display: flex; margin-top: .375rem; }
        /* min-width: 0 mantém as colunas iguais às do gráfico; o rótulo pode transbordar, centralizado sob o ponto. */
        .gl-eixo-x span { flex: 1 1 0; min-width: 0; display: flex; justify-content: center;
            font-size: .75rem; color: var(--bs-secondary-color); white-space: nowrap; }
        @media (max-width: 576px) { .gl-rotulo-oculto { visibility: hidden; } }
    </style>
@endonce
