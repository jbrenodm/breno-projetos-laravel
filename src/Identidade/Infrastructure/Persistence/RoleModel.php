<?php

declare(strict_types=1);

namespace Src\Identidade\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class RoleModel extends Model
{
    use HasUuids;

    protected $table = 'roles';

    protected $fillable = ['id', 'nome', 'descricao'];
}
