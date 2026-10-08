<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use App\Http\Requests\API\V1\Concerns\InformaTipoDeAtividade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;

final class RegistrarAtividadeRequest extends FormRequest
{
    use InformaTipoDeAtividade;

    public function rules(): array
    {
        return $this->regrasDoTipo() + [
            'descricao' => ['required', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(StatusAtividade::class)],
            'data_entrada' => ['required', 'date_format:Y-m-d'],
            'data_limite' => ['required', 'date_format:Y-m-d'],
            'data_inicio' => ['nullable', 'date_format:Y-m-d'],
            'data_termino' => ['nullable', 'date_format:Y-m-d'],
            'account_manager_id' => ['nullable', 'uuid'],
            'pre_vendas_id' => ['nullable', 'uuid'],
            'observacao' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
