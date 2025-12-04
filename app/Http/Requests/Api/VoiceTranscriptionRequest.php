<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class VoiceTranscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth handled by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimes:webm,mp3,mp4,m4a,wav,ogg', 'max:25000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'audio.required' => 'An audio file is required.',
            'audio.file' => 'The audio must be a valid file.',
            'audio.mimes' => 'The audio must be a webm, mp3, mp4, m4a, wav, or ogg file.',
            'audio.max' => 'The audio file must not exceed 25MB.',
        ];
    }
}
