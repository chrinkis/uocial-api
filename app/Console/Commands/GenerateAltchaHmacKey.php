<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAltchaHmacKey extends Command
{
    protected $signature = 'app:generate-altcha-hmac-key';

    protected $description = 'Generate a new Altcha HMAC key for the application';

    public function handle(): int
    {
        $currentKey = env('ALTCHA_HMAC_KEY');

        if ($currentKey && ! $this->confirm('This will invalidate all existing Altcha challenges. Do you really wish to run this command?')) {
            $this->components->info('Command cancelled.');

            return Command::SUCCESS;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));

        if (! $this->setEnvironmentValue('ALTCHA_HMAC_KEY', $key)) {
            return Command::FAILURE;
        }

        $this->laravel['config']['services.altcha.hmac_key'] = $key;

        $this->components->info('Altcha HMAC key set successfully.');

        return Command::SUCCESS;
    }

    protected function setEnvironmentValue(string $key, string $value): bool
    {
        $envFile = $this->laravel->environmentFilePath();

        if (! file_exists($envFile)) {
            $this->components->error('Environment file not found.');

            return false;
        }

        $contents = file_get_contents($envFile);

        $oldValue = env($key);

        if ($oldValue) {
            $contents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $contents
            );
        } else {
            $contents .= "\n{$key}={$value}\n";
        }

        file_put_contents($envFile, $contents);

        return true;
    }
}
