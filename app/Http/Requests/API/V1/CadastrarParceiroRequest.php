<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

/** Cliente e Fornecedor compartilham os mesmos dados cadastrais (RN-21). */
final class CadastrarParceiroRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'solucoes' => ['nullable', 'array'],
            'solucoes.*' => ['string', 'max:255'],
        ];
    }
}
