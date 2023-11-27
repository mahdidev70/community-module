<?php

namespace TechStudio\Lms\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomCoverRequest extends FormRequest
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
            'file' => 'required|max:2048|mimes:jpeg,png,jpg,gif',
        ];
    }
}
