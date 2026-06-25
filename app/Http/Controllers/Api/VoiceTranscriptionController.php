<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\ExpenseParseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VoiceTranscriptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

final class VoiceTranscriptionController extends Controller
{
    /**
     * Transcribe audio to text using OpenAI Whisper.
     */
    public function transcribe(VoiceTranscriptionRequest $request): JsonResponse
    {
        $apiKey = config('prism.providers.openai.api_key');

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'OpenAI API key not configured',
            ], 500);
        }

        $audioFile = $request->file('audio');
        $extension = $audioFile->getClientOriginalExtension() ?: 'webm';

        $response = Http::withToken($apiKey)
            ->attach(
                'file',
                file_get_contents($audioFile->getRealPath()),
                'audio.'.$extension
            )
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'en',
            ]);

        throw_unless($response->successful(), new ExpenseParseException(
            'Transcription failed: '.($response->json('error.message', 'Unknown error'))
        ));

        return response()->json([
            'text' => $response->json('text'),
        ]);
    }
}
