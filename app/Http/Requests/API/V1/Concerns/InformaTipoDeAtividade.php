<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Concerns;

use Illuminate\Validation\Validator;
use Src\Projetos\Application\Queries\TiposAtividadeQuery;

/**
 * RN-43: na API o tipo da atividade vem pelo nome atual ("tipo", sem diferenciar maiúsculas) ou pelo id ("tipo_id").
 * Se o tipo está ativo é verificado no caso de uso.
 */
trait InformaTipoDeAtividade
{
    /** @return array<string, list<string>> */
    protected function regrasDoTipo(): array
    {
        return [
            'tipo' => ['required_without:tipo_id', 'nullable', 'string', 'max:60'],
            'tipo_id' => ['required_without:tipo', 'nullable', 'uuid'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $nome = $this->input('tipo');
            if ($this->filled('tipo_id') || ! is_string($nome) || $validator->errors()->has('tipo')) {
                return;
            }

            if (app(TiposAtividadeQuery::class)->idPorNome($nome) === null) {
                $validator->errors()->add('tipo', "Tipo de atividade inválido: {$nome}.");
            }
        }];
    }

    public function tipoId(): string
    {
        return $this->validated('tipo_id') ?? (string) app(TiposAtividadeQuery::class)->idPorNome((string) $this->validated('tipo'));
    }
}
