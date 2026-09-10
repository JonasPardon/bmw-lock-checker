<?php

namespace App\Services\BmwCarData;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * OAuth 2.0 Device Authorization Grant (RFC 8628) + PKCE against BMW GCDM.
 */
class CarDataAuth
{
    public function __construct(private readonly TokenStore $store) {}

    /**
     * @param  Closure(string $url, string $userCode): void  $prompt  shown once, before polling starts
     */
    public function deviceLogin(Closure $prompt): array
    {
        $clientId = config('bmw.client_id') ?: throw new RuntimeException('BMW_CLIENT_ID is not set in .env');
        $verifier = Str::random(96);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $dc = Http::asForm()->acceptJson()->post(config('bmw.device_code_url'), [
            'client_id' => $clientId,
            'response_type' => 'device_code',
            'scope' => config('bmw.scope'),
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ])->throw()->json();

        $prompt($dc['verification_uri_complete'] ?? $dc['verification_uri'], $dc['user_code'] ?? '');

        $interval = (int) ($dc['interval'] ?? 5);
        $deadline = time() + (int) ($dc['expires_in'] ?? 300);

        while (time() < $deadline) {
            sleep($interval);
            $tok = Http::asForm()->acceptJson()->post(config('bmw.token_url'), [
                'client_id' => $clientId,
                'device_code' => $dc['device_code'],
                'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
                'code_verifier' => $verifier,
            ])->json() ?? [];

            if (isset($tok['access_token'])) {
                $this->store->save($tok);
                return $tok;
            }
            match ($tok['error'] ?? null) {
                'authorization_pending' => null,
                'slow_down' => $interval += 5,
                default => throw new RuntimeException('Token request failed: '.json_encode($tok)),
            };
        }

        throw new RuntimeException('Device code expired before the login was approved.');
    }

    /** Returns tokens, refreshing the 1-hour access token when it is about to expire. */
    public function validTokens(): array
    {
        $tokens = $this->store->load()
            ?? throw new RuntimeException('Not logged in. Run: php artisan bmw:login');

        $age = time() - ($tokens['obtained_at'] ?? 0);
        if ($age > (int) ($tokens['expires_in'] ?? 3600) - 120) {
            $tokens = $this->refresh($tokens);
        }

        return $tokens;
    }

    public function refresh(array $tokens): array
    {
        $tok = Http::asForm()->acceptJson()->post(config('bmw.token_url'), [
            'client_id' => config('bmw.client_id'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'] ?? '',
        ])->json() ?? [];

        if (! isset($tok['access_token'])) {
            throw new RuntimeException('Refresh failed (refresh tokens live 2 weeks; run bmw:login again): '.json_encode($tok));
        }
        $this->store->save($tok);

        return $tok;
    }

    /** GCID = MQTT username. BMW returns it in the token response; fall back to id_token claims. */
    public function gcid(array $tokens): ?string
    {
        if (! empty($tokens['gcid'])) {
            return $tokens['gcid'];
        }
        $parts = explode('.', $tokens['id_token'] ?? '');
        if (count($parts) < 2) {
            return null;
        }
        $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?? [];

        return $claims['gcid'] ?? $claims['sub'] ?? null;
    }
}
