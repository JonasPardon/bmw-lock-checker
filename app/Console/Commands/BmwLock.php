<?php

namespace App\Console\Commands;

use App\Services\BmwCarData\RemoteLock;
use Illuminate\Console\Command;

class BmwLock extends Command
{
    protected $signature = 'bmw:lock {vin?}';

    protected $description = 'Remote lock — not possible via any third-party BMW API; prints the reason';

    public function handle(): int
    {
        $this->line(json_encode(RemoteLock::result($this->argument('vin') ?? config('bmw.vin') ?? ''), JSON_PRETTY_PRINT));

        return 2;
    }
}
