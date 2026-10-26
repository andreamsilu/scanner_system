<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ConfigurationCacheTest extends TestCase
{
    public function test_application_configuration_can_be_cached_and_loaded(): void
    {
        $directory = sys_get_temp_dir().'/scanner-config-cache-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $cachePath = $directory.'/config.php';

        try {
            $process = new Process(
                [PHP_BINARY, 'artisan', 'config:cache'],
                dirname(__DIR__, 2),
                ['APP_CONFIG_CACHE' => $cachePath],
            );
            $process->setTimeout(120);
            $process->run();

            $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
            $this->assertFileExists($cachePath);
            $config = require $cachePath;
            $this->assertArrayHasKey('app', $config);
            $this->assertArrayHasKey('broadcasting', $config);
        } finally {
            if (is_file($cachePath)) {
                unlink($cachePath);
            }
            rmdir($directory);
        }
    }
}
