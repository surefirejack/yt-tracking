<?php

namespace App\Console\Commands\Configs;

use App\Services\ConfigManager;
use Illuminate\Console\Command;

class EncryptConfigs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:encrypt-configs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt the app configs stored in the database.';

    /**
     * Execute the console command.
     */
    public function handle(ConfigManager $configManager)
    {
        $configManager->encryptSensitiveConfigs();

        $this->info('Configs encrypted successfully.');
    }
}
