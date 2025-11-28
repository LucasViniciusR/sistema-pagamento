<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'numeric', 'min:0.01'],
            'payer' => ['required', 'integer', 'exists:usuarios,id'],
            'payee' => ['required', 'integer', 'exists:usuarios,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'O valor da transferência é obrigatório',
            'value.numeric' => 'O valor deve ser numérico',
            'value.min' => 'O valor da transferência deve ser pelo menos 0.01',
            'payer.required' => 'O pagador é obrigatório',
            'payer.exists' => 'Pagador não encontrado',
            'payee.required' => 'O recebedor é obrigatório',
            'payee.exists' => 'Recebedor não encontrado',
        ];
    }
}
