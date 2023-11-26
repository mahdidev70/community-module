<?php

namespace App\Http\Requests\Community\Forum;

use Illuminate\Foundation\Http\FormRequest;

class NewMessageRequest extends FormRequest
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
            "replyTo" => ['nullable','integer'],
            "message" => ['required','string'],
            'attachments' =>['nullable','array','max:3'],
            'attachments.*' => ['integer','exists:files,id']
        ];
    }
}
