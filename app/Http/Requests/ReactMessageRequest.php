<?php

namespace TechStudio\Community\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactMessageRequest extends FormRequest
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
           "chatMessageId" => ['required','integer','exists:chat_messages,id'],
            "reaction" => ['required',Rule::in(['like','dislike','happy','smile','sad','ok','clap','clear']),]
        ];
    }
}
