@props(['titulo', 'subtitulo' => null, 'itens', 'unidade' => 'atividades', 'vazio' => 'Nada a exibir.'])
@php($maximo = max([...array_column($itens, 'total'), 0]) ?: 1) {{-- tudo zero não pode dividir por zero --}}
{{-- Barras horizontais de uma série: valor na ponta, tooltip ao passar o mouse/foco; a lista é também a visão em tabela.
     Cada item: nome, total e, opcionalmente, url (o nome vira link), exibir (valor formatado) e detalhe (texto do tooltip). --}}
<div {{ $attributes->class('card shadow-sm h-100 grafico-barras') }}>
    <div class="card-body">
        <h2 class="h6 mb-0">{{ $titulo }}</h2>
        @if ($subtitulo) <div class="small text-muted mb-3">{{ $subtitulo }}</div> @endif
        @if ($itens === [])
            <div class="text-muted small py-4 text-center"><i class="bi bi-check-circle me-1"></i>{{ $vazio }}</div>
        @else
            <ul class="list-unstyled mb-0" role="list">
                @foreach ($itens as $item)
                    @php($rotulo = $item['detalhe'] ?? "{$item['nome']}: {$item['total']} ".($item['total'] === 1 ? rtrim($unidade, 's') : $unidade))
                    <li class="gb-linha" tabindex="0" aria-label="{{ $rotulo }}">
                        @if (isset($item['url']))
                            <a class="gb-nome" href="{{ $item['url'] }}" title="{{ $item['nome'] }}" wire:navigate>{{ $item['nome'] }}</a>
                        @else
                            <span class="gb-nome" title="{{ $item['nome'] }}">{{ $item['nome'] }}</span>
                        @endif
                        {{-- A largura é relativa à trilha inteira; o espaço do valor fica reservado no padding, então barras nunca são cortadas. --}}
                        <span class="gb-trilha" style="--gb-largura: {{ $item['total'] > 0 ? max(2, round($item['total'] / $maximo * 100, 1)) : 0 }}%">
                            <span class="gb-barra"></span>
                            <span class="gb-valor">{{ $item['exibir'] ?? $item['total'] }}</span>
                        </span>
                        <span class="gb-tooltip" role="tooltip">{{ $rotulo }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@once
    <style>
        .grafico-barras { --gb-serie: #2a78d6; --gb-trilha: #e1e0d9; }
        [data-bs-theme="dark"] .grafico-barras { --gb-serie: #3987e5; --gb-trilha: #2c2c2a; }
        .gb-linha { position: relative; display: grid; grid-template-columns: minmax(6rem, 35%) 1fr; align-items: center;
            gap: .75rem; padding: .3rem .25rem; border-radius: .375rem; outline: none; }
        .gb-linha:hover, .gb-linha:focus-visible { background: var(--bs-tertiary-bg); }
        .gb-linha:focus-visible { box-shadow: 0 0 0 2px var(--gb-serie); }
        .gb-nome { font-size: .875rem; color: var(--bs-body-color); line-height: 1.25; overflow-wrap: anywhere;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .gb-trilha { position: relative; display: block; min-width: 0; height: 16px; margin-right: 3.75rem;
            border-left: 1px solid var(--gb-trilha); }
        .gb-barra { display: block; height: 100%; width: var(--gb-largura); background: var(--gb-serie); border-radius: 0 4px 4px 0; }
        .gb-valor { position: absolute; top: 50%; left: calc(var(--gb-largura) + .5rem); transform: translateY(-50%);
            white-space: nowrap; font-size: .8125rem; font-weight: 600; color: var(--bs-secondary-color);
            font-variant-numeric: tabular-nums; }
        /* O tooltip fica dentro da largura do gráfico e quebra linha se o texto for longo. */
        .gb-tooltip { position: absolute; z-index: 5; left: .25rem; bottom: calc(100% + 4px); width: max-content;
            max-width: calc(100% - .5rem);
            padding: .25rem .5rem; font-size: .75rem; border-radius: .375rem; pointer-events: none;
            background: var(--bs-body-color); color: var(--bs-body-bg); opacity: 0; transition: opacity .1s; }
        .gb-linha:hover .gb-tooltip, .gb-linha:focus-visible .gb-tooltip { opacity: 1; }
        @media (max-width: 576px) {
            .gb-linha { grid-template-columns: 1fr; gap: .25rem; }
            .gb-nome { -webkit-line-clamp: unset; }
        }
    </style>
@endonce
