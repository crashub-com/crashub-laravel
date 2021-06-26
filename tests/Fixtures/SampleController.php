<?php

namespace Crashub\Tests\Fixtures;

class SampleController
{
    public function index()
    {
        app('crashub')->report(new \Exception('Test Error'));
    }
}
