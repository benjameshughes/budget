<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('returns 401 without authentication', function () {
    $file = UploadedFile::fake()->create('recording.mp3', 100, 'audio/mpeg');

    $response = $this->postJson('/api/voice/transcribe', [
        'audio' => $file,
    ]);

    $response->assertUnauthorized();
});

test('validates audio file is required', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/voice/transcribe', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['audio']);
});

test('validates audio file must be valid mime type', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $file = UploadedFile::fake()->create('file.txt', 100, 'text/plain');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/voice/transcribe', [
            'audio' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['audio']);
});

test('returns error when openai api key is not configured', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Ensure API key is empty
    config(['prism.providers.openai.api_key' => null]);

    // Create a minimal valid MP3 file with MPEG frame sync header
    $tempFile = tmpfile();
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    // Minimal MP3 header: MPEG frame sync bytes
    $mp3Header = "\xFF\xFB\x90\x00";
    fwrite($tempFile, $mp3Header.str_repeat("\x00", 1000));

    $file = new UploadedFile($tempPath, 'recording.mp3', 'audio/mpeg', null, true);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/voice/transcribe', [
            'audio' => $file,
        ]);

    $response->assertStatus(500)
        ->assertJson(['error' => 'OpenAI API key not configured']);

    fclose($tempFile);
});

test('successfully transcribes audio file', function () {
    // This test requires the actual OpenAI API for integration testing
    // The OpenAI client uses a static factory which is difficult to mock
    // For now, we test the endpoint accepts valid input and handles API key configuration
    expect(true)->toBeTrue();
})->skip('Requires actual OpenAI API for integration testing');

test('accepts wav audio format', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    config(['prism.providers.openai.api_key' => null]);

    // Create a minimal valid WAV file with RIFF header
    $tempFile = tmpfile();
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    // Minimal WAV header: RIFF + WAVE format
    $wavHeader = "RIFF\x24\x00\x00\x00WAVEfmt \x10\x00\x00\x00\x01\x00\x01\x00";
    fwrite($tempFile, $wavHeader.str_repeat("\x00", 1000));

    $file = new UploadedFile($tempPath, 'recording.wav', 'audio/wav', null, true);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/voice/transcribe', [
            'audio' => $file,
        ]);

    // Will fail due to missing API key, but validates the wav format is accepted
    $response->assertStatus(500)
        ->assertJson(['error' => 'OpenAI API key not configured']);

    fclose($tempFile);
});

test('accepts mp3 audio format', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    config(['prism.providers.openai.api_key' => null]);

    // Create a minimal valid MP3 file with MPEG frame sync header
    $tempFile = tmpfile();
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    // Minimal MP3 header: MPEG frame sync bytes
    $mp3Header = "\xFF\xFB\x90\x00";
    fwrite($tempFile, $mp3Header.str_repeat("\x00", 1000));

    $file = new UploadedFile($tempPath, 'recording.mp3', 'audio/mpeg', null, true);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/voice/transcribe', [
            'audio' => $file,
        ]);

    $response->assertStatus(500)
        ->assertJson(['error' => 'OpenAI API key not configured']);

    fclose($tempFile);
});

test('rejects files exceeding 25mb', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // Create a file larger than 25MB (25000 KB) using mp3 format
    $file = UploadedFile::fake()->create('recording.mp3', 26000, 'audio/mpeg');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/voice/transcribe', [
            'audio' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['audio']);
});
