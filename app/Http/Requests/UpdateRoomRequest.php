<?php

namespace TechStudio\Lms\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends CreateRoomRequest
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
        return array_merge(parent::rules(), [
            'slug' => ['required', Rule::unique('chat_rooms')->ignore($this->room), 'alpha_dash'],
            'title' => ['required', Rule::unique('chat_rooms')->ignore($this->room)],
        ]);
    }
}
