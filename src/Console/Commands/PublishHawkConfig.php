<?php

declare(strict_types=1);

namespace HawkBundle\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishHawkConfig extends Command
{
    protected $signature = 'hawkbundle:publish {--token= : Hawk integration token}';
    protected $description = 'Publishes the HawkBundle configuration file';

    public function handle()
    {
        $token = $this->option('token');

        $sourcePath = __DIR__ . '/../../../config/hawk.php';

        $destinationPath = config_path('hawk.php');

        if (!File::exists($sourcePath)) {
            $this->error("Source configuration file not found at {$sourcePath}");

            return 1;
        }

        if (File::exists($destinationPath)) {
            if (!$this->confirm('Configuration file already exists. Overwrite it?')) {
                $this->info('Publishing aborted.');

                return 0;
            }
        }

        File::copy($sourcePath, $destinationPath);
        $this->info('Configuration file published successfully.');

        $this->updateConfigFile($destinationPath, $token);

        return 0;
    }

    protected function updateConfigFile(string $filePath, string $token): void
    {
        $configContent = File::get($filePath);

        $updatedContent = preg_replace(
            "/('integration_token'\s*=>\s*)(env\(\'HAWK_TOKEN\',\s*)\'\'(\))/",
            "$1$2'$token'$3",
            $configContent
        );

        File::put($filePath, $updatedContent);

        $this->info('Integration token updated successfully.');
    }
}
