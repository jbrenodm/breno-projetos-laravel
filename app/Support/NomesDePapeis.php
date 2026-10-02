<?php

declare(strict_types=1);

namespace App\Support;

use Src\Identidade\Application\Queries\PapeisQuery;
use Src\Identidade\Domain\Papel;

/**
 * RN-41: fonte única dos nomes de papéis exibidos nas telas (nome e sigla editáveis pelo Admin Geral do Sistema).
 * Nas views: @inject('papeis', \App\Support\NomesDePapeis::class) e {{ $papeis->sigla(Papel::ACCOUNT_MANAGER) }}.
 * Não é singleton de propósito: cada view faz uma consulta (tabela de 3 linhas) e nunca mostra um nome desatualizado.
 */
final class NomesDePapeis
{
    /** @var array<string, array{nome: string, sigla: ?string}>|null */
    private ?array $nomes = null;

    public function __construct(private readonly PapeisQuery $query) {}

    public function nome(Papel $papel): string
    {
        return $this->carregar()[$papel->value]['nome'];
    }

    /** Sigla do papel; sem sigla, usa o nome. */
    public function sigla(Papel $papel): string
    {
        return $this->carregar()[$papel->value]['sigla'] ?? $this->nome($papel);
    }

    /** Opção "todos" de um filtro: "Todos os AMs" ou, sem sigla, "Todos (Gerente de Contas)". */
    public function todos(Papel $papel): string
    {
        $sigla = $this->carregar()[$papel->value]['sigla'];

        return $sigla !== null ? "Todos os {$sigla}s" : 'Todos ('.$this->nome($papel).')';
    }

    /** @return array<string, array{nome: string, sigla: ?string}> */
    private function carregar(): array
    {
        return $this->nomes ??= $this->query->nomes();
    }
}
