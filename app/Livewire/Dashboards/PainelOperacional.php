<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Projetos\Application\Queries\ProjetoQuery;
use Src\Shared\Application\Ports\Relogio;

/** Dashboards › Painel operacional: o que precisa de atenção agora (somente leitura). */
#[Title('Painel operacional')]
final class PainelOperacional extends Component
{
    public function render(ProjetoQuery $projetos, Relogio $relogio): View
    {
        $hoje = $relogio->hoje();

        return view('livewire.dashboards.painel-operacional', [
            'painel' => $projetos->painelOperacional($hoje),
            'hoje' => $hoje,
        ]);
    }
}
