<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Src\Identidade\Application\Queries\UsuariosQuery;
use Src\Identidade\Domain\Papel;
use Src\Parceiros\Application\Queries\ParceirosQuery;
use Src\Projetos\Application\Queries\FiltroAtividades;
use Src\Projetos\Application\Queries\OrdenacaoAtividades;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;
use Src\Projetos\Domain\ValueObjects\TipoAtividade;

/**
 * Dashboards › Todas as atividades (somente leitura).
 * Filtros e ordenação ficam na URL; valores inválidos são ignorados.
 */
#[Title('Todas as atividades')]
final class TodasAsAtividades extends Component
{
    private const FILTROS = ['status', 'busca', 'clienteId', 'tipo', 'accountManagerId', 'preVendasId', 'fornecedorId',
        'somenteAtrasadas', 'entradaDe', 'entradaAte'];

    #[Url(except: '')]
    public string $status = '';

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'cliente', except: '')]
    public string $clienteId = '';

    #[Url(except: '')]
    public string $tipo = '';

    #[Url(as: 'am', except: '')]
    public string $accountManagerId = '';

    #[Url(as: 'pv', except: '')]
    public string $preVendasId = '';

    #[Url(as: 'fornecedor', except: '')]
    public string $fornecedorId = '';

    #[Url(as: 'atrasadas', except: false)]
    public bool $somenteAtrasadas = false;

    #[Url(as: 'de', except: '')]
    public string $entradaDe = '';

    #[Url(as: 'ate', except: '')]
    public string $entradaAte = '';

    #[Url(as: 'ordem', except: 'data_entrada')]
    public string $ordenarPor = 'data_entrada';

    #[Url(as: 'sentido', except: 'desc')]
    public string $sentido = 'desc';

    public function limparFiltros(): void
    {
        $this->reset(self::FILTROS);
    }

    public function inverterSentido(): void
    {
        $this->sentido = $this->sentido === 'desc' ? 'asc' : 'desc';
    }

    public function render(ProjetoQuery $projetos, ParceirosQuery $parceiros, UsuariosQuery $usuarios): View
    {
        $filtro = $this->filtro();

        return view('livewire.dashboards.todas-as-atividades', [
            'atividades' => $projetos->listarAtividades($filtro),
            'statusPossiveis' => StatusAtividade::cases(),
            'tipos' => TipoAtividade::cases(),
            'ordenacoes' => OrdenacaoAtividades::cases(),
            'clientes' => $parceiros->listarClientes(),
            'fornecedores' => $parceiros->listarFornecedores(),
            'accountManagers' => $usuarios->listarAtivosPorPapel(Papel::ACCOUNT_MANAGER),
            'preVendas' => $usuarios->listarAtivosPorPapel(Papel::PRE_VENDAS),
            'filtrosAtivos' => count(array_filter(self::FILTROS, fn (string $campo) => (bool) $this->{$campo})),
        ]);
    }

    private function filtro(): FiltroAtividades
    {
        return new FiltroAtividades(
            status: StatusAtividade::tryFrom($this->status)?->value,
            busca: mb_substr(trim($this->busca), 0, 100) ?: null,
            clienteId: self::uuid($this->clienteId),
            tipo: TipoAtividade::tryFrom($this->tipo)?->value,
            accountManagerId: self::uuid($this->accountManagerId),
            preVendasId: self::uuid($this->preVendasId),
            fornecedorId: self::uuid($this->fornecedorId),
            somenteAtrasadas: $this->somenteAtrasadas,
            entradaDe: self::data($this->entradaDe),
            entradaAte: self::data($this->entradaAte),
            ordenarPor: OrdenacaoAtividades::tryFrom($this->ordenarPor) ?? OrdenacaoAtividades::DATA_ENTRADA,
            decrescente: $this->sentido !== 'asc',
        );
    }

    private static function uuid(string $valor): ?string
    {
        return Str::isUuid($valor) ? $valor : null;
    }

    private static function data(string $valor): ?DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('!Y-m-d', $valor) ?: null;
    }
}
