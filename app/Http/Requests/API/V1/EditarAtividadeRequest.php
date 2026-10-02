<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Projetos\Domain\ValueObjects\TipoAtividade;

/** RN-28: o status não faz parte da edição. */
final class EditarAtividadeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:2000'],
            'tipo' => ['required', Rule::enum(TipoAtividade::class)],
            'data_entrada' => ['required', 'date_format:Y-m-d'],
            'data_limite' => ['required', 'date_format:Y-m-d'],
            'data_inicio' => ['nullable', 'date_format:Y-m-d'],
            'data_termino' => ['nullable', 'date_format:Y-m-d'],
            'account_manager_id' => ['required', 'uuid'],
            'pre_vendas_id' => ['required', 'uuid'],
            'observacao' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
