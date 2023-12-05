<?php

namespace TechStudio\Community\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use TechStudio\Core\app\Helper\SlugGenerator;

class CreateQuestionRequest extends FormRequest
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
           'text' => ['required','string'],
            'attachments' => ['nullable','array', 'max:3'],
            'attachments.*' => ['integer','exists:files,id'],
            'categorySlug' => ['required','string'],
            'slug' => ['required','unique:community_questions,slug'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'slug'=> SlugGenerator::transform(($this->text)),
        ]);
    }

}
