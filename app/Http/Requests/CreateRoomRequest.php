<?php

namespace TechStudio\Lms\app\Http\Requests;

use App\Helper\SlugGenerator;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'title' => ['required','string','unique:chat_rooms,title'],
            'slug' => ['required','unique:chat_rooms,slug'],
            'category_slug' => ['required', 'string', 'exists:categories,slug'],
            'file' => ['nullable','max:2048','mimes:jpeg,png,jpg,gif'],
            'status' => ['required',Rule::in(['active','inactive','draft'])],
            'members.*' => ['nullable','exists:user_profiles,id',]
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
           'slug'=> SlugGenerator::transform(($this->title)),
            'category_id' => Category::where('slug', $this->category_slug)->first()->id
        ]);
    }
}
