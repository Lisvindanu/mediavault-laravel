<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail_url' => 'nullable|url',
            'duration_seconds' => 'required|integer|min:0',
            'category' => 'required|string|in:music,podcast,tutorial,entertainment,documentary,sports,news,uncategorized',
            'source_platform' => 'required|string|in:youtube,soundcloud,vimeo',
            'quality' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
            'playback_speed' => 'nullable|numeric|min:0.25|max:2',
        ];
    }
}
}
