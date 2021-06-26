<?php

namespace Crashub\Tests\Fixtures;

class SampleInvokableController
{
    public function __invoke()
    {
        app('crashub')->report(new \Exception('Test Error'));
    }
}
