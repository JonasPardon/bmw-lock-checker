<?php

namespace App\Services\BmwCarData;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Notifier
{
    public function push(string $title, string $message, string $priority = 'high'): bool
    {
        $topic = config('bmw.ntfy_topic');
        if (! $topic) {
            Log::warning("NTFY_TOPIC not set; would have sent: {$title} - {$message}");

            return false;
        }

        return Http::withHeaders(['Title' => $title, 'Priority' => $priority, 'Tags' => 'car,unlock'])
            ->withBody($message, 'text/plain')
            ->post(rtrim(config('bmw.ntfy_server'), '/').'/'.$topic)
            ->successful();
    }
}
