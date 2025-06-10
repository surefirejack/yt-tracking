<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ToggleDubTestMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dub:test-mode {action? : enable, disable, or status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle Dub API test mode (enable/disable/status)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action') ?? 'status';
        
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->error('.env file not found');
            return 1;
        }
        
        $envContent = File::get($envPath);
        
        switch ($action) {
            case 'enable':
                $this->enableTestMode($envContent, $envPath);
                break;
                
            case 'disable':
                $this->disableTestMode($envContent, $envPath);
                break;
                
            case 'status':
            default:
                $this->showStatus();
                break;
        }
        
        return 0;
    }
    
    protected function enableTestMode(string $envContent, string $envPath): void
    {
        if (strpos($envContent, 'DUB_TEST_MODE=') !== false) {
            $envContent = preg_replace('/DUB_TEST_MODE=.*/', 'DUB_TEST_MODE=true', $envContent);
        } else {
            $envContent .= "\nDUB_TEST_MODE=true";
        }
        
        File::put($envPath, $envContent);
        $this->call('config:clear');
        
        $this->info('✅ Dub test mode ENABLED');
        $this->line('   Analytics will now return realistic test data instead of making API calls.');
    }
    
    protected function disableTestMode(string $envContent, string $envPath): void
    {
        if (strpos($envContent, 'DUB_TEST_MODE=') !== false) {
            $envContent = preg_replace('/DUB_TEST_MODE=.*/', 'DUB_TEST_MODE=false', $envContent);
        } else {
            $envContent .= "\nDUB_TEST_MODE=false";
        }
        
        File::put($envPath, $envContent);
        $this->call('config:clear');
        
        $this->info('✅ Dub test mode DISABLED');
        $this->line('   Analytics will now make real API calls to Dub.co');
    }
    
    protected function showStatus(): void
    {
        $testMode = config('services.dub.test_mode', false);
        
        $this->line('');
        $this->line('🔍 <info>Current Dub Test Mode Status:</info>');
        
        if ($testMode) {
            $this->line('   Status: <fg=green>ENABLED</fg=green> ✅');
            $this->line('   📊 Analytics will return test data');
            $this->line('   🚫 No API calls will be made to Dub.co');
        } else {
            $this->line('   Status: <fg=red>DISABLED</fg=red> ❌');
            $this->line('   🌐 Analytics will make real API calls');
            $this->line('   📡 Requires valid DUB_API_KEY');
        }
        
        $this->line('');
        $this->line('<comment>Usage:</comment>');
        $this->line('  php artisan dub:test-mode enable   # Enable test mode');
        $this->line('  php artisan dub:test-mode disable  # Disable test mode');
        $this->line('  php artisan dub:test-mode status   # Show current status');
    }
}
