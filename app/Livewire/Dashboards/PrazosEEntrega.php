<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Shared\Application\Ports\Relogio;

/** Dashboards › Prazos e entrega: estamos entregando no prazo? (somente leitura) */
#[Title('Prazos e entrega')]
final class PrazosEEntrega extends Component
{
    public const PERIODOS = [3, 6, 12];

    private const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    #[Url(except: 12)]
    public int $meses = 12;

    public function render(ProjetoQuery $projetos, Relogio $relogio): View
    {
        $meses = in_array($this->meses, self::PERIODOS, true) ? $this->meses : 12;
        $dados = $projetos->prazosEEntrega($relogio->hoje(), $meses);

        return view('livewire.dashboards.prazos-e-entrega', [
            'dados' => $dados,
            'periodos' => self::PERIODOS,
            'pontos' => array_map(fn (array $m) => [
                'rotulo' => self::rotuloDoMes($m['mes']),
                'valor' => $m['percentual_no_prazo'],
                'detalhe' => self::rotuloDoMes($m['mes']).': '.($m['concluidas'] === 0
                    ? 'sem entregas'
                    : self::numero($m['percentual_no_prazo']).'% no prazo ('.$m['no_prazo'].' de '.$m['concluidas'].')'),
            ], $dados['por_mes']),
        ]);
    }

    /** Converte médias em itens do <x-grafico-barras>. */
    public static function barrasDeDias(array $linhas): array
    {
        return array_map(fn (array $l) => $l + [
            'total' => $l['media_dias'],
            'exibir' => self::numero($l['media_dias']).' d',
            'detalhe' => $l['nome'].': média de '.self::numero($l['media_dias']).' dias ('.$l['atividades']
                .' '.($l['atividades'] === 1 ? 'atividade' : 'atividades').')',
        ], $linhas);
    }

    public static function numero(?float $valor): string
    {
        return $valor === null ? '—' : number_format($valor, $valor == (int) $valor ? 0 : 1, ',', '.');
    }

    private static function rotuloDoMes(string $anoMes): string
    {
        [$ano, $mes] = explode('-', $anoMes);

        return self::MESES[(int) $mes - 1].'/'.substr($ano, 2);
    }
}
