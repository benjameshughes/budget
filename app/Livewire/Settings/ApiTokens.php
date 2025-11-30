<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Component;

class ApiTokens extends Component
{
    public string $tokenName = '';

    public ?string $plainTextToken = null;

    /**
     * Create a new API token for the user.
     */
    public function createToken(): void
    {
        $validated = $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
        ]);

        $token = Auth::user()->createToken($validated['tokenName']);

        $this->plainTextToken = $token->plainTextToken;
        $this->tokenName = '';

        $this->dispatch('token-created');
    }

    /**
     * Revoke an API token.
     */
    public function revokeToken(int $tokenId): void
    {
        $token = PersonalAccessToken::findOrFail($tokenId);

        if ($token->tokenable_id !== Auth::id()) {
            abort(403);
        }

        $token->delete();

        $this->dispatch('token-revoked');
    }

    /**
     * Clear the plain text token.
     */
    public function clearToken(): void
    {
        $this->plainTextToken = null;
    }

    /**
     * Render the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings.api-tokens', [
            'tokens' => Auth::user()->tokens()->latest()->get(),
        ]);
    }
}
