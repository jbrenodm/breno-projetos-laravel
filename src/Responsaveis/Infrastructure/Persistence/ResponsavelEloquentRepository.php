<?php

declare(strict_types=1);

namespace Src\Responsaveis\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Responsaveis\Domain\Entities\Responsavel;
use Src\Responsaveis\Domain\Funcao;
use Src\Responsaveis\Domain\Repositories\ResponsavelRepositoryInterface;
use Src\Shared\Domain\Uuid;

final class ResponsavelEloquentRepository implements ResponsavelRepositoryInterface
{
    public function buscarPorId(string $id): ?Responsavel
    {
        if (! Uuid::ehValido($id)) {
            return null;
        }

        $model = ResponsavelModel::query()->with('funcoes')->find($id);

        return $model === null ? null : new Responsavel(
            $model->id,
            $model->nome,
            $model->email,
            $model->funcoes->map(fn (ResponsavelFuncaoModel $f) => Funcao::from($f->funcao))->values()->all(),
            $model->ativo,
        );
    }

    public function emailEmUso(string $email, ?string $ignorarId = null): bool
    {
        return ResponsavelModel::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->exists();
    }

    /** Sem mass assignment do request: o array de persistência é montado aqui (REQUISITOS.md §8). */
    public function salvar(Responsavel $responsavel): void
    {
        DB::transaction(function () use ($responsavel): void {
            $model = ResponsavelModel::query()->updateOrCreate(['id' => $responsavel->getId()], [
                'nome' => $responsavel->getNome(),
                'email' => $responsavel->getEmail(),
                'ativo' => $responsavel->isAtivo(),
            ]);

            $model->funcoes()->delete();
            $model->funcoes()->createMany(array_map(
                fn (Funcao $f) => ['funcao' => $f->value],
                $responsavel->getFuncoes(),
            ));
        });
    }
}
