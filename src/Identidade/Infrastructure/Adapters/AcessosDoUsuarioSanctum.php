<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Adapters;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Identidade\Application\Ports\AcessosDoUsuario;
use Src\Shared\Domain\Uuid;

final class AcessosDoUsuarioSanctum implements AcessosDoUsuario
{
    public function emitirToken(string $usuarioId, string $nome): string
    {
        return User::query()->findOrFail($usuarioId)->createToken($nome)->plainTextToken;
    }

    public function revogarToken(string $usuarioId, string $tokenId): bool
    {
        if (! ctype_digit($tokenId) || ! Uuid::ehValido($usuarioId)) {
            return false;
        }

        return User::query()->findOrFail($usuarioId)->tokens()->whereKey((int) $tokenId)->delete() > 0;
    }

    public function revogarTudo(string $usuarioId): void
    {
        User::query()->find($usuarioId)?->tokens()->delete();

        // Sessões guardadas no banco (SESSION_DRIVER=database) são encerradas na hora; nos demais drivers,
        // o middleware GarantirUsuarioAtivo desconecta o usuário na próxima requisição (RN-33).
        $tabela = config('session.table', 'sessions');
        if (Schema::hasTable($tabela)) {
            DB::table($tabela)->where('user_id', $usuarioId)->delete();
        }
    }
}
