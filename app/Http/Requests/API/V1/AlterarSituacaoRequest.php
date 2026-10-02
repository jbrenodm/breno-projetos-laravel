<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

/** RN-31 */
final class AlterarSituacaoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ativo' => ['required', 'boolean'],
        ];
    }
}
