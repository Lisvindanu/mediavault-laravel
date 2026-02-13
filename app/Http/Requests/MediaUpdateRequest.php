<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|in:music,podcast,tutorial,entertainment,documentary,sports,news,uncategorized',
            'tags' => 'nullable|array',
            'is_favorite' => 'sometimes|boolean',
            'playback_speed' => 'sometimes|numeric|min:0.25|max:2',
        ];
    }
}
