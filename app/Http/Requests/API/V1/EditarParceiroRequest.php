<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

/** RN-30: edição dos dados cadastrais de Cliente ou Fornecedor (soluções têm endpoints próprios). */
final class EditarParceiroRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
        ];
    }
}
