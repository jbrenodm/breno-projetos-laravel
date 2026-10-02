<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

/** Validação sintática (a semântica fica no domínio). */
final class RegistrarProjetoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'uuid'],
            'codigo_oportunidade' => ['nullable', 'string', 'max:50'],
            'fornecedores' => ['required', 'array', 'min:1'],
            'fornecedores.*.fornecedor_id' => ['required', 'uuid'],
            'fornecedores.*.solucao_id' => ['nullable', 'uuid'],
        ];
    }
}
