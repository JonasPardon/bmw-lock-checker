<?php

namespace App\Console\Commands;

use App\Services\BmwCarData\CarDataClient;
use App\Services\BmwCarData\Notifier;
use Illuminate\Console\Command;

class BmwCheck extends Command
{
    protected $signature = 'bmw:check {vin?} {container?} {--notify : Send an ntfy push when the car is not locked} {--raw : Print all telematic values}';

    protected $description = 'Read the lock state from BMW CarData; optionally push a warning when unlocked';

    public function handle(CarDataClient $client, Notifier $notifier): int
    {
        $vin = $this->argument('vin') ?? config('bmw.vin');
        $container = $this->argument('container') ?? config('bmw.container_id');
        if (! $vin || ! $container) {
            $this->error('Pass VIN and container ID, or set BMW_VIN / BMW_CONTAINER_ID in .env');

            return self::FAILURE;
        }

        $state = $client->lockState($vin, $container);
        $notified = false;
        if ($this->option('notify') && $state['locked'] === false) {
            $notified = $notifier->push('BMW not locked', "Lock status: {$state['lock_status']} (as of {$state['timestamp']})");
        }
        if (! $this->option('raw')) {
            unset($state['raw']);
        }
        $this->line(json_encode($state + ['notified' => $notified], JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
