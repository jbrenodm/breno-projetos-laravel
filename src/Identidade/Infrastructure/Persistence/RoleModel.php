<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Persistence;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Src\Identidade\Domain\Papel;

final class RoleModel extends Model
{
    use HasUuids;

    protected $table = 'roles';

    protected $fillable = ['id', 'nome', 'descricao', 'sigla'];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }

    /** Linha do papel em "roles"; se faltar, cria com nome e sigla padrão (nunca sobrescreve um nome editado — RN-41). */
    public static function garantir(Papel $papel): self
    {
        return self::query()->firstOrCreate(
            ['nome' => $papel->value],
            ['descricao' => $papel->nomePadrao(), 'sigla' => $papel->siglaPadrao()],
        );
    }
}
