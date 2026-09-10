<?php

namespace App\Services\BmwCarData;

use Illuminate\Support\Facades\File;

class TokenStore
{
    public function __construct(private readonly string $path) {}

    public function load(): ?array
    {
        return File::exists($this->path) ? json_decode(File::get($this->path), true) : null;
    }

    public function save(array $tokens): void
    {
        $tokens['obtained_at'] ??= time();
        File::ensureDirectoryExists(dirname($this->path));
        File::put($this->path, json_encode($tokens, JSON_PRETTY_PRINT));
        @chmod($this->path, 0600);
    }

    public function clear(): void
    {
        File::delete($this->path);
    }
}
