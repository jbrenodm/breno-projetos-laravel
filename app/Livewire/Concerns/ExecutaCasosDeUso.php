<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Closure;
use Src\Shared\Application\RecursoNaoEncontradoException;
use Src\Shared\Domain\RegraDeNegocioException;

/**
 * Executa um caso de uso e transforma violações de regra de negócio em mensagem de erro na tela.
 */
trait ExecutaCasosDeUso
{
    protected function executar(Closure $acao, string $campoErro = 'geral'): bool
    {
        try {
            $acao();

            return true;
        } catch (RegraDeNegocioException $e) {
            $this->addError($campoErro, $e->getMessage());
        } catch (RecursoNaoEncontradoException) {
            $this->addError($campoErro, 'Registro não encontrado.');
        }

        return false;
    }
}
