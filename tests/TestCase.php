<?php

namespace Crashub\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('crashub.project_key', 'project key');
    }
}
