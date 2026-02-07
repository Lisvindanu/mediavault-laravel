<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
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
            'device_id' => 'required|string|max:255',
            'sync_timestamp' => 'required|integer',
            'media_items' => 'required|array',
            'media_items.*.id' => 'required|string',
            'media_items.*.url' => 'required|url',
            'media_items.*.title' => 'required|string|max:255',
            'media_items.*.description' => 'nullable|string',
            'media_items.*.thumbnail_url' => 'nullable|url',
            'media_items.*.duration_seconds' => 'required|integer|min:0',
            'media_items.*.category' => 'required|string|in:music,podcast,tutorial,entertainment,documentary,sports,news,uncategorized',
            'media_items.*.source_platform' => 'required|string|in:youtube,soundcloud,vimeo',
            'media_items.*.quality' => 'nullable|string',
            'media_items.*.tags' => 'nullable|array',
            'media_items.*.is_favorite' => 'required|boolean',
            'media_items.*.playback_speed' => 'required|numeric|min:0.25|max:2',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'string',
        ];
    }
}
}
