<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;

final class AlterarClienteDoProjetoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'uuid'],
        ];
    }
}
