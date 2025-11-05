<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePseudonymSalt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-pseudonym-salt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new pseudonym salt for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $currentSalt = env('PSEUDONYM_SALT');

        if ($currentSalt && ! $this->confirm('This will invalidate all existing pseudonyms. Do you really wish to run this command?')) {
            $this->components->info('Command cancelled.');

            return Command::SUCCESS;
        }

        $salt = $this->generateRandomSalt();

        if (! $this->setEnvironmentValue('PSEUDONYM_SALT', $salt)) {
            return Command::FAILURE;
        }

        $this->laravel['config']['app.pseudonym_salt'] = $salt;

        $this->components->info('Pseudonym salt set successfully.');

        return Command::SUCCESS;
    }

    /**
     * Generate a random salt.
     *
     * @return string
     */
    protected function generateRandomSalt()
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * Set the environment value in the .env file.
     *
     * @param  string  $key
     * @param  string  $value
     */
    protected function setEnvironmentValue($key, $value): bool
    {
        $envFile = $this->laravel->environmentFilePath();

        if (! file_exists($envFile)) {
            $this->components->error('Environment file not found.');

            return false;
        }

        $contents = file_get_contents($envFile);

        $oldValue = env($key);

        if ($oldValue) {
            // Replace existing value
            $contents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $contents
            );
        } else {
            // Append new key
            $contents .= "\n{$key}={$value}\n";
        }

        file_put_contents($envFile, $contents);

        return true;
    }
}
