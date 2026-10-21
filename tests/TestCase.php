<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $testRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mini-mart-tests';
        $cache = $testRoot.DIRECTORY_SEPARATOR.'cache';
        if (! is_dir($cache)) mkdir($cache, 0777, true);
        foreach (['packages.php', 'services.php'] as $manifest) {
            copy(base_path('bootstrap/cache/'.$manifest), $cache.DIRECTORY_SEPARATOR.$manifest);
        }
        $app->useBootstrapPath($testRoot);
        $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }
}
