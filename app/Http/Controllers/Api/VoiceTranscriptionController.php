<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

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

        try {
            $audioFile = $request->file('audio');
            $extension = $audioFile->getClientOriginalExtension() ?: 'webm';

            // Call OpenAI Whisper API directly with proper filename
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

            if (! $response->successful()) {
                \Log::error('OpenAI transcription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'error' => 'Transcription failed',
                    'message' => $response->json('error.message', 'Unknown error'),
                ], 500);
            }

            return response()->json([
                'text' => $response->json('text'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Voice transcription failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to transcribe audio',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
