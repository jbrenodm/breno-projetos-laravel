<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Projetos\Domain\ValueObjects\StatusAtividade;

final class AlterarStatusAtividadeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusAtividade::class)],
            'data' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
