<?php

namespace App\Console\Commands;

use App\Services\BmwCarData\CarDataClient;
use Illuminate\Console\Command;

class BmwCreateContainer extends Command
{
    protected $signature = 'bmw:create-container {--list : List existing containers instead}';

    protected $description = 'Create the "security" container (lock/door/window/location descriptors)';

    public function handle(CarDataClient $client): int
    {
        if ($this->option('list')) {
            $this->line(json_encode($client->containers(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }
        $res = $client->createContainer('security-poc', 'Lock/door/window state for home automation', config('bmw.security_descriptors'));
        $this->line(json_encode($res, JSON_PRETTY_PRINT));
        $this->info('Put the containerId in .env as BMW_CONTAINER_ID.');

        return self::SUCCESS;
    }
}
