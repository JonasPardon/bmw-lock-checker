<?php

namespace App\Http\Controllers;

use App\Services\BmwCarData\CarDataClient;
use App\Services\BmwCarData\Notifier;
use App\Services\BmwCarData\RemoteLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Local HTTP surface for the presence automation:
 * iPhone arrives home -> iOS Shortcuts POSTs /api/bmw/check -> push if unlocked.
 */
class BmwController extends Controller
{
    public function vehicles(CarDataClient $client): JsonResponse
    {
        return response()->json($client->vehicles());
    }

    public function status(Request $request, CarDataClient $client): JsonResponse
    {
        [$vin, $container] = $this->target($request);
        $state = $client->lockState($vin, $container);
        unset($state['raw']);

        return response()->json($state);
    }

    public function check(Request $request, CarDataClient $client, Notifier $notifier): JsonResponse
    {
        [$vin, $container] = $this->target($request);
        $state = $client->lockState($vin, $container);
        $notified = $state['locked'] === false
            && $notifier->push('BMW not locked', "Lock status: {$state['lock_status']} (as of {$state['timestamp']})");
        unset($state['raw']);

        return response()->json($state + ['notified' => $notified]);
    }

    public function lock(Request $request): JsonResponse
    {
        return response()->json(RemoteLock::result($request->input('vin', config('bmw.vin') ?? '')), 501);
    }

    /** @return array{string,string} */
    private function target(Request $request): array
    {
        $vin = $request->input('vin', config('bmw.vin'));
        $container = $request->input('container', config('bmw.container_id'));
        abort_unless($vin && $container, 400, 'vin and container are required (or set BMW_VIN / BMW_CONTAINER_ID)');

        return [$vin, $container];
    }
}
