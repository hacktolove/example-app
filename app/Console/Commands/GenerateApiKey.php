<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('api-key:generate')]
#[Description('Generate a new API key for the check-sub/subscribe endpoints and store it in .env')]
class GenerateApiKey extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $key = Str::random(40);

        if (! $this->writeKeyToEnvironmentFile($key)) {
            return;
        }

        $this->laravel['config']['app.api_key'] = $key;

        $this->components->info('API key generated: '.$key);
    }

    protected function writeKeyToEnvironmentFile(string $key): bool
    {
        $path = $this->laravel->environmentFilePath();
        $input = file_get_contents($path);

        $currentKey = (string) config('app.api_key');
        $escaped = preg_quote('='.$currentKey, '/');
        $pattern = "/^APP_API_KEY{$escaped}/m";

        $replaced = preg_replace($pattern, 'APP_API_KEY='.$key, $input, -1, $count);

        if ($count === 0) {
            $replaced = rtrim($input)."\nAPP_API_KEY={$key}\n";
        }

        file_put_contents($path, $replaced);

        return true;
    }
}
