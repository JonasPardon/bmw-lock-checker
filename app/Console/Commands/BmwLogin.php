<?php

namespace App\Console\Commands;

use App\Services\BmwCarData\CarDataAuth;
use Illuminate\Console\Command;

class BmwLogin extends Command
{
    protected $signature = 'bmw:login';

    protected $description = 'Authenticate against BMW CarData with the OAuth device code flow';

    public function handle(CarDataAuth $auth): int
    {
        $auth->deviceLogin(function (string $url, string $code) {
            $this->info('Open this URL in a browser and log in with your BMW ID:');
            $this->line("  {$url}");
            $this->line("User code: {$code}");
            $this->newLine();
            $this->comment('Waiting for approval...');
        });
        $this->info('Login successful. Tokens stored in '.config('bmw.token_file'));

        return self::SUCCESS;
    }
}
