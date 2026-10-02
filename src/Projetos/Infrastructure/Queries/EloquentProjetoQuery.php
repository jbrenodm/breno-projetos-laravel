<?php

declare(strict_types=1);

namespace Src\Projetos\Infrastructure\Queries;

use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Src\Projetos\Application\Queries\FiltroAtividades;
use Src\Projetos\Application\Queries\OrdenacaoAtividades;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\StatusProjeto;
use Src\Shared\Domain\Uuid;

/** Leitura otimizada via Query Builder (sem hidratar o agregado). */
final class EloquentProjetoQuery implements ProjetoQuery
{
    private const NOME_CLIENTE = 'COALESCE(c.nome_fantasia, c.razao_social)';

    public function listar(): array
    {
        $concluida = StatusAtividade::CONCLUIDA->value;

        $projetos = DB::table('projetos as p')
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->leftJoin('atividades as a', 'a.projeto_id', '=', 'p.id')
            ->groupBy('p.id', 'c.nome_fantasia', 'c.razao_social')
            ->orderByDesc('p.created_at')
            ->selectRaw('p.id, p.codigo_oportunidade, p.status, p.created_at, '.self::NOME_CLIENTE.' as cliente')
            ->selectRaw('COUNT(a.id) as total_atividades')
            ->selectRaw('COUNT(a.id) FILTER (WHERE a.status = ?) as atividades_concluidas', [$concluida])
            ->get();

        $fornecedores = $this->fornecedoresPorProjeto($projetos->pluck('id')->all());

        return $projetos->map(fn ($p) => [
            'id' => $p->id,
            'cliente' => $p->cliente,
            'codigo_oportunidade' => $p->codigo_oportunidade,
            'status' => $p->status,
            'fornecedores' => array_values(array_unique(array_column($fornecedores[$p->id] ?? [], 'fornecedor'))),
            'total_atividades' => (int) $p->total_atividades,
            'atividades_concluidas' => (int) $p->atividades_concluidas,
            'criado_em' => (string) $p->created_at,
        ])->all();
    }

    public function detalhar(string $projetoId): ?array
    {
        if (! Uuid::ehValido($projetoId)) {
            return null;
        }

        $p = DB::table('projetos as p')
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->where('p.id', $projetoId)
            ->selectRaw('p.id, p.cliente_id, p.codigo_oportunidade, p.status, '.self::NOME_CLIENTE.' as cliente')
            ->first();

        if ($p === null) {
            return null;
        }

        $atividades = DB::table('atividades as a')
            ->join('users as am', 'am.id', '=', 'a.account_manager_id')
            ->join('users as pv', 'pv.id', '=', 'a.pre_vendas_id')
            ->where('a.projeto_id', $projetoId)
            ->orderBy('a.sequencia')
            ->select('a.*', 'am.name as account_manager', 'pv.name as pre_vendas')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'sequencia' => (int) $a->sequencia,
                'descricao' => $a->descricao,
                'tipo' => $a->tipo,
                'status' => $a->status,
                'data_entrada' => $a->data_entrada,
                'data_limite' => $a->data_limite,
                'data_inicio' => $a->data_inicio,
                'data_termino' => $a->data_termino,
                'atrasada' => $this->estaAtrasada($a->data_limite, $a->data_termino),
                'account_manager_id' => $a->account_manager_id,
                'account_manager' => $a->account_manager,
                'pre_vendas_id' => $a->pre_vendas_id,
                'pre_vendas' => $a->pre_vendas,
                'observacao' => $a->observacao,
                'proximos_status' => array_map(
                    fn (StatusAtividade $s) => $s->value,
                    StatusAtividade::from($a->status)->destinosPermitidos(),
                ),
            ])
            ->all();

        return [
            'id' => $p->id,
            'cliente_id' => $p->cliente_id,
            'cliente' => $p->cliente,
            'codigo_oportunidade' => $p->codigo_oportunidade,
            'status' => $p->status,
            'fornecedores' => $this->fornecedoresPorProjeto([$p->id])[$p->id] ?? [],
            'atividades' => $atividades,
        ];
    }

    public function listarAtividades(FiltroAtividades $filtro = new FiltroAtividades): array
    {
        $busca = trim((string) $filtro->busca);
        $sentido = $filtro->decrescente ? 'desc' : 'asc';
        $hoje = now()->toDateString();

        $atividades = DB::table('atividades as a')
            ->join('projetos as p', 'p.id', '=', 'a.projeto_id')
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->join('users as am', 'am.id', '=', 'a.account_manager_id')
            ->join('users as pv', 'pv.id', '=', 'a.pre_vendas_id')
            ->when($filtro->status, fn ($q, $v) => $q->where('a.status', $v))
            ->when($filtro->tipo, fn ($q, $v) => $q->where('a.tipo', $v))
            ->when($filtro->clienteId, fn ($q, $v) => $q->where('p.cliente_id', $v))
            ->when($filtro->accountManagerId, fn ($q, $v) => $q->where('a.account_manager_id', $v))
            ->when($filtro->preVendasId, fn ($q, $v) => $q->where('a.pre_vendas_id', $v))
            ->when($filtro->fornecedorId, fn ($q, $v) => $q->whereExists(fn ($sub) => $sub->from('projeto_fornecedores as pf')
                ->whereColumn('pf.projeto_id', 'p.id')->where('pf.fornecedor_id', $v)))
            ->when($filtro->somenteAtrasadas, fn ($q) => $q->whereRaw('COALESCE(a.data_termino, ?) > a.data_limite', [$hoje]))
            ->when($filtro->entradaDe, fn ($q, $v) => $q->where('a.data_entrada', '>=', $v->format('Y-m-d')))
            ->when($filtro->entradaAte, fn ($q, $v) => $q->where('a.data_entrada', '<=', $v->format('Y-m-d')))
            ->when($busca !== '', fn ($q) => $q->where(function ($q) use ($busca): void {
                $termo = '%'.addcslashes($busca, '%_\\').'%';
                $q->whereRaw(self::NOME_CLIENTE.' ILIKE ?', [$termo])
                    ->orWhere('c.razao_social', 'ILIKE', $termo)
                    ->orWhere('a.descricao', 'ILIKE', $termo);
            }))
            ->orderByRaw(match ($filtro->ordenarPor) {
                OrdenacaoAtividades::DATA_ENTRADA => 'a.data_entrada',
                OrdenacaoAtividades::DATA_LIMITE => 'a.data_limite',
                OrdenacaoAtividades::CLIENTE => self::NOME_CLIENTE,
                OrdenacaoAtividades::STATUS => 'a.status',
                OrdenacaoAtividades::TIPO => 'a.tipo',
            }.' '.$sentido)
            ->orderByRaw(self::NOME_CLIENTE)
            ->orderBy('a.sequencia')
            ->selectRaw('a.*, p.status as projeto_status, p.codigo_oportunidade, '.self::NOME_CLIENTE.' as cliente')
            ->addSelect('am.name as account_manager', 'pv.name as pre_vendas')
            ->get();

        $fornecedores = $this->fornecedoresPorProjeto($atividades->pluck('projeto_id')->unique()->values()->all());

        return $atividades->map(fn ($a) => [
            'id' => $a->id,
            'projeto_id' => $a->projeto_id,
            'projeto_status' => $a->projeto_status,
            'cliente' => $a->cliente,
            'codigo_oportunidade' => $a->codigo_oportunidade,
            'fornecedores' => array_values(array_unique(array_column($fornecedores[$a->projeto_id] ?? [], 'fornecedor'))),
            'descricao' => $a->descricao,
            'tipo' => $a->tipo,
            'status' => $a->status,
            'data_entrada' => $a->data_entrada,
            'data_limite' => $a->data_limite,
            'data_inicio' => $a->data_inicio,
            'data_termino' => $a->data_termino,
            'atrasada' => $this->estaAtrasada($a->data_limite, $a->data_termino),
            'account_manager' => $a->account_manager,
            'pre_vendas' => $a->pre_vendas,
        ])->all();
    }

    public function painelOperacional(DateTimeImmutable $hoje): array
    {
        $dia = $hoje->format('Y-m-d');
        $daquiA7Dias = $hoje->modify('+7 days')->format('Y-m-d');
        $concluida = StatusAtividade::CONCLUIDA->value;

        $indicadores = $this->atividadesOperacionais()
            ->selectRaw('COUNT(*) FILTER (WHERE a.status <> ?) as abertas', [$concluida])
            ->selectRaw('COUNT(*) FILTER (WHERE a.status <> ? AND a.data_limite < ?) as atrasadas', [$concluida, $dia])
            ->selectRaw('COUNT(*) FILTER (WHERE a.status <> ? AND a.data_limite BETWEEN ? AND ?) as vencem', [$concluida, $dia, $daquiA7Dias])
            ->selectRaw('COUNT(*) FILTER (WHERE a.status = ? AND a.data_termino BETWEEN ? AND ?) as concluidas_no_mes',
                [$concluida, $hoje->modify('first day of this month')->format('Y-m-d'), $hoje->modify('last day of this month')->format('Y-m-d')])
            ->first();

        $atrasadas = fn () => $this->atividadesOperacionais()
            ->where('a.status', '<>', $concluida)
            ->where('a.data_limite', '<', $dia)
            ->orderByDesc('total');
        $agrupar = fn (Builder $consulta) => $consulta->get()
            ->map(fn ($l) => ['id' => $l->id, 'nome' => $l->nome, 'total' => (int) $l->total])
            ->all();

        $porAm = $atrasadas()
            ->join('users as u', 'u.id', '=', 'a.account_manager_id')
            ->groupBy('u.id', 'u.name')
            ->orderBy('u.name')
            ->selectRaw('u.id, u.name as nome, COUNT(*) as total');

        $porCliente = $atrasadas()
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->groupBy('c.id', 'c.nome_fantasia', 'c.razao_social')
            ->orderByRaw(self::NOME_CLIENTE)
            ->selectRaw('c.id, '.self::NOME_CLIENTE.' as nome, COUNT(*) as total');

        $porProjeto = $atrasadas()
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->groupBy('p.id', 'p.codigo_oportunidade', 'p.created_at', 'c.nome_fantasia', 'c.razao_social')
            ->orderByRaw(self::NOME_CLIENTE)
            ->orderBy('p.created_at')
            ->selectRaw('p.id, '.self::NOME_CLIENTE." || ' — ' || COALESCE(p.codigo_oportunidade, 'aberto em ' || TO_CHAR(p.created_at, 'DD/MM/YYYY')) as nome, COUNT(*) as total");

        $proximos = $this->atividadesOperacionais()
            ->join('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->join('users as am', 'am.id', '=', 'a.account_manager_id')
            ->join('users as pv', 'pv.id', '=', 'a.pre_vendas_id')
            ->where('a.status', '<>', $concluida)
            ->whereBetween('a.data_limite', [$dia, $daquiA7Dias])
            ->orderBy('a.data_limite')
            ->orderByRaw(self::NOME_CLIENTE)
            ->limit(10)
            ->selectRaw('a.id, a.projeto_id, a.descricao, a.tipo, a.status, a.data_limite, '.self::NOME_CLIENTE.' as cliente')
            ->addSelect('am.name as account_manager', 'pv.name as pre_vendas')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'projeto_id' => $a->projeto_id,
                'cliente' => $a->cliente,
                'descricao' => $a->descricao,
                'tipo' => $a->tipo,
                'status' => $a->status,
                'data_limite' => substr((string) $a->data_limite, 0, 10),
                'dias_restantes' => (int) $hoje->diff(new DateTimeImmutable(substr((string) $a->data_limite, 0, 10)))->days,
                'account_manager' => $a->account_manager,
                'pre_vendas' => $a->pre_vendas,
            ])
            ->all();

        return [
            'abertas' => (int) $indicadores->abertas,
            'atrasadas' => (int) $indicadores->atrasadas,
            'vencem_em_7_dias' => (int) $indicadores->vencem,
            'concluidas_no_mes' => (int) $indicadores->concluidas_no_mes,
            'atrasadas_por_am' => $agrupar($porAm),
            'atrasadas_por_cliente' => $agrupar($porCliente),
            'atrasadas_por_projeto' => $agrupar($porProjeto),
            'proximos_vencimentos' => $proximos,
        ];
    }

    public function prazosEEntrega(DateTimeImmutable $hoje, int $meses = 12): array
    {
        $meses = max(1, $meses);
        $inicio = $hoje->modify('first day of this month')->modify('-'.($meses - 1).' months');
        $fim = $hoje->modify('last day of this month');

        $concluidas = fn () => $this->atividadesOperacionais()
            ->where('a.status', StatusAtividade::CONCLUIDA->value)
            ->whereBetween('a.data_termino', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')]);
        $media = fn ($valor) => $valor === null ? null : round((float) $valor, 1);

        $resumo = $concluidas()
            ->selectRaw('COUNT(*) as concluidas')
            ->selectRaw('COUNT(*) FILTER (WHERE a.data_termino <= a.data_limite) as no_prazo')
            ->selectRaw('AVG(a.data_termino - a.data_inicio) as execucao')
            ->selectRaw('AVG(a.data_termino - a.data_limite) FILTER (WHERE a.data_termino > a.data_limite) as atraso')
            ->first();

        $porMes = $concluidas()
            ->groupByRaw("TO_CHAR(a.data_termino, 'YYYY-MM')")
            ->selectRaw("TO_CHAR(a.data_termino, 'YYYY-MM') as mes, COUNT(*) as concluidas")
            ->selectRaw('COUNT(*) FILTER (WHERE a.data_termino <= a.data_limite) as no_prazo')
            ->get()
            ->keyBy('mes');

        $serie = [];
        for ($mes = $inicio; $mes <= $fim; $mes = $mes->modify('+1 month')) {
            $linha = $porMes->get($mes->format('Y-m'));
            $total = (int) ($linha->concluidas ?? 0);
            $noPrazo = (int) ($linha->no_prazo ?? 0);
            $serie[] = [
                'mes' => $mes->format('Y-m'),
                'concluidas' => $total,
                'no_prazo' => $noPrazo,
                'percentual_no_prazo' => $total > 0 ? round($noPrazo * 100 / $total, 1) : null,
            ];
        }

        $mediaPor = fn (Builder $consulta, string $diferenca) => $consulta
            ->selectRaw("AVG({$diferenca}) as media, COUNT(*) as atividades")
            ->orderByDesc('media')
            ->orderBy('nome')
            ->get()
            ->map(fn ($l) => array_filter([
                'id' => $l->id ?? null,
                'nome' => $l->nome,
                'media_dias' => $media($l->media),
                'atividades' => (int) $l->atividades,
            ], fn ($v) => $v !== null))
            ->all();

        $total = (int) $resumo->concluidas;
        $noPrazo = (int) $resumo->no_prazo;

        return [
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fim->format('Y-m-d'),
            'concluidas' => $total,
            'no_prazo' => $noPrazo,
            'com_atraso' => $total - $noPrazo,
            'percentual_no_prazo' => $total > 0 ? round($noPrazo * 100 / $total, 1) : null,
            'execucao_media_dias' => $media($resumo->execucao),
            'atraso_medio_dias' => $media($resumo->atraso),
            'por_mes' => $serie,
            'execucao_por_tipo' => $mediaPor(
                $concluidas()->whereNotNull('a.data_inicio')->groupBy('a.tipo')->addSelect('a.tipo as nome'),
                'a.data_termino - a.data_inicio',
            ),
            'atraso_por_tipo' => $mediaPor(
                $concluidas()->whereColumn('a.data_termino', '>', 'a.data_limite')->groupBy('a.tipo')->addSelect('a.tipo as nome'),
                'a.data_termino - a.data_limite',
            ),
            'atraso_por_am' => $mediaPor(
                $concluidas()->whereColumn('a.data_termino', '>', 'a.data_limite')
                    ->join('users as u', 'u.id', '=', 'a.account_manager_id')
                    ->groupBy('u.id', 'u.name')->addSelect('u.id', 'u.name as nome'),
                'a.data_termino - a.data_limite',
            ),
        ];
    }

    /** Atividades de projetos não cancelados (base dos dashboards). */
    private function atividadesOperacionais(): Builder
    {
        return DB::table('atividades as a')
            ->join('projetos as p', 'p.id', '=', 'a.projeto_id')
            ->where('p.status', '<>', StatusProjeto::CANCELADO->value);
    }

    public function responsaveisSugeridos(string $projetoId): array
    {
        $ultima = Uuid::ehValido($projetoId)
            ? DB::table('atividades')->where('projeto_id', $projetoId)->orderByDesc('sequencia')
                ->first(['account_manager_id', 'pre_vendas_id'])
            : null;

        return [
            'account_manager_id' => $ultima?->account_manager_id,
            'pre_vendas_id' => $ultima?->pre_vendas_id,
        ];
    }

    /**
     * @param  list<string>  $projetoIds
     * @return array<string, list<array{fornecedor_id: string, fornecedor: string, solucao_id: ?string, solucao: ?string}>>
     */
    private function fornecedoresPorProjeto(array $projetoIds): array
    {
        if ($projetoIds === []) {
            return [];
        }

        return DB::table('projeto_fornecedores as pf')
            ->join('fornecedores as f', 'f.id', '=', 'pf.fornecedor_id')
            ->leftJoin('solucoes as s', 's.id', '=', 'pf.solucao_id')
            ->whereIn('pf.projeto_id', $projetoIds)
            ->orderBy('pf.id')
            ->selectRaw('pf.projeto_id, pf.fornecedor_id, COALESCE(f.nome_fantasia, f.razao_social) as fornecedor, pf.solucao_id, s.nome as solucao')
            ->get()
            ->groupBy('projeto_id')
            ->map(fn ($linhas) => $linhas->map(fn ($l) => [
                'fornecedor_id' => $l->fornecedor_id,
                'fornecedor' => $l->fornecedor,
                'solucao_id' => $l->solucao_id,
                'solucao' => $l->solucao,
            ])->values()->all())
            ->all();
    }

    private function estaAtrasada(string $dataLimite, ?string $dataTermino): bool
    {
        $referencia = $dataTermino ?? now()->toDateString();

        return substr($referencia, 0, 10) > substr($dataLimite, 0, 10);
    }
}
