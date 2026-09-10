<?php

namespace App\Console\Commands;

use App\Services\BmwCarData\CarDataClient;
use Illuminate\Console\Command;

class BmwVehicles extends Command
{
    protected $signature = 'bmw:vehicles {--basic : Also fetch basicData for each VIN}';

    protected $description = 'List vehicles mapped to the BMW ID';

    public function handle(CarDataClient $client): int
    {
        $vehicles = $client->vehicles();
        $this->line(json_encode($vehicles, JSON_PRETTY_PRINT));
        if ($this->option('basic')) {
            foreach ($vehicles as $v) {
                $this->line(json_encode($client->basicData($v['vin']), JSON_PRETTY_PRINT));
            }
        }

        return self::SUCCESS;
    }
}
