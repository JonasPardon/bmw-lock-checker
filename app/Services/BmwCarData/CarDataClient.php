<?php

namespace App\Services\BmwCarData;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * BMW CarData Customer API v1. This is the COMPLETE operation set from the
 * official swagger: nine GETs plus container create/delete. There is no
 * endpoint that sends a command (lock, unlock, climate, ...) to the vehicle.
 */
class CarDataClient
{
    public function __construct(private readonly CarDataAuth $auth) {}

    private function http(): PendingRequest
    {
        $tokens = $this->auth->validTokens();

        return Http::baseUrl(config('bmw.api_base'))
            ->withToken($tokens['access_token'])
            ->withHeaders(['x-version' => 'v1'])
            ->acceptJson()
            ->timeout(30);
    }

    public function vehicles(): array
    {
        return $this->http()->get('/customers/vehicles/mappings')->throw()->json();
    }

    public function basicData(string $vin): array
    {
        return $this->http()->get("/customers/vehicles/{$vin}/basicData")->throw()->json();
    }

    public function telematicData(string $vin, string $containerId): array
    {
        return $this->http()->get("/customers/vehicles/{$vin}/telematicData", ['containerId' => $containerId])->throw()->json();
    }

    public function chargingHistory(string $vin): array
    {
        return $this->http()->get("/customers/vehicles/{$vin}/chargingHistory")->throw()->json();
    }

    public function tyreDiagnosis(string $vin): array
    {
        return $this->http()->get("/customers/vehicles/{$vin}/smartMaintenanceTyreDiagnosis")->throw()->json();
    }

    public function containers(): array
    {
        return $this->http()->get('/customers/containers')->throw()->json();
    }

    /** Containers define which descriptors you may READ; they are not vehicle commands. */
    public function createContainer(string $name, string $purpose, array $descriptors): array
    {
        return $this->http()->post('/customers/containers', [
            'name' => $name, 'purpose' => $purpose, 'technicalDescriptors' => $descriptors,
        ])->throw()->json();
    }

    public function deleteContainer(string $containerId): void
    {
        $this->http()->delete("/customers/containers/{$containerId}")->throw();
    }

    /** @return array{vin:string,lock_status:?string,locked:?bool,timestamp:?string,raw:array} */
    public function lockState(string $vin, string $containerId): array
    {
        $data = $this->telematicData($vin, $containerId)['telematicData'] ?? [];
        $entry = $data[config('bmw.lock_descriptor')] ?? [];
        $value = $entry['value'] ?? null;

        return [
            'vin' => $vin,
            'lock_status' => $value,
            'locked' => $value === null ? null : in_array($value, config('bmw.locked_values'), true),
            'timestamp' => $entry['timestamp'] ?? null,
            'raw' => $data,
        ];
    }
}
