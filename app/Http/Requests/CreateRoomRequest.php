<?php

namespace TechStudio\Community\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use TechStudio\Core\app\Helper\SlugGenerator;
use TechStudio\Core\app\Models\Category ;

class CreateRoomRequest extends FormRequest
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
            'title' => ['string', Rule::unique('chat_rooms')->ignore($this->room)],
            'slug' => ['string'],
            'categoryId' => ['required'],
            'file' => ['nullable','max:2048','mimes:jpeg,png,jpg,gif'],
            'status' => ['required',Rule::in(['active','inactive','draft'])],
            'members.*' => ['nullable','exists:user_profiles,id',]
        ];
    }
}
