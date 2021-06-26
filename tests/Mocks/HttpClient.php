<?php

namespace Crashub\Tests\Mocks;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class HttpClient
{
    private $container;

    function __construct()
    {
        $this->container = [];
    }

    public function create()
    {
        $mock = new MockHandler([
            new Response(201)
        ]);

        $history = Middleware::history($this->container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        return new Client(['handler' => $handlerStack]);
    }

    public function lastRequest()
    {
        return count($this->container) > 0 ? $this->container[count($this->container) - 1]['request'] : null;
    }
}
