<?php

namespace App\Http\Requests\Community\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required','in:approved,hidden,waiting_for_approval'],
            'ids' => ['required','array','exists:questions,id'],
        ];
    }
}
