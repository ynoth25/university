<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

class CreateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create-key {name} {--expires=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $expiresAt = $this->option('expires');

        try {
            $apiKey = ApiKey::createKey($name, $expiresAt);

            $this->info('API Key created successfully!');
            $this->line('Name: ' . $apiKey->name);
            $this->line('Key: ' . $apiKey->key);
            $this->line('Expires: ' . ($apiKey->expires_at ? $apiKey->expires_at->format('Y-m-d H:i:s') : 'Never'));

            $this->warn('Please save this key securely. It will not be shown again.');
        } catch (\Exception $e) {
            $this->error('Failed to create API key: ' . $e->getMessage());
        }
    }
}
