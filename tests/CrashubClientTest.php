<?php declare(strict_types=1);

namespace Crashub\Tests;

use Crashub\CrashubClient;
use Crashub\Tests\Fixtures\SampleController;
use Crashub\Tests\Fixtures\SampleInvokableController;
use Crashub\Tests\Mocks\HttpClient;
use Illuminate\Support\Facades\Route;

final class CrashubClientTest extends TestCase
{
    private $httpClient;
    private $crashubClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new HttpClient();
        $this->crashubClient = new CrashubClient($this->httpClient->create());
        $this->instance('crashub', $this->crashubClient);
    }

    public function testSendsProjectKey(): void
    {
        $this->crashubClient->report(new \Exception('Test Error'));

        $lastRequest = $this->httpClient->lastRequest();
        $headers = $lastRequest->getHeaders();
        $this->assertEquals('project key', $headers['X-Project-Key'][0]);
    }

    public function testSendsJsonContentType(): void
    {
        $this->crashubClient->report(new \Exception('Test Error'));

        $lastRequest = $this->httpClient->lastRequest();
        $headers = $lastRequest->getHeaders();
        $this->assertEquals('application/json', $headers['Content-Type'][0]);
    }

    public function testSendsExceptionDetails()
    {
        $this->crashubClient->report(new \Exception('Test Error'));

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('Exception', $body['exception']['class']);
        $this->assertEquals('Test Error', $body['exception']['message']);
        $this->assertArrayHasKey('callstack', $body['exception']);
    }

    public function testSendsUserId()
    {
        request()->setUserResolver(function () {
            return new class {
                public function getAuthIdentifier()
                {
                    return 'user id';
                }
            };
        });

        $this->crashubClient->report(new \Exception('Test Error'));

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('user id', $body['context']['user_id']);
    }

    public function testSendsActionAndControllerNamesFromRegularController()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('Crashub\Tests\Fixtures\SampleController', $body['request']['component']);
        $this->assertEquals('index', $body['request']['action']);
    }

    public function testSendsControllerNameFromInvokableController()
    {
        Route::get('/test', SampleInvokableController::class);

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('Crashub\Tests\Fixtures\SampleInvokableController', $body['request']['component']);
        $this->assertNull($body['request']['action']);
    }

    public function testDoesNotSendActionAndControllerNamesFromClosure()
    {
        Route::get('/test', function()
        {
            app('crashub')->report(new \Exception('Test Error'));
        });

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertNull($body['request']['component']);
        $this->assertNull($body['request']['action']);
    }

    public function testSendsRequestUrlWithoutQuery()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test?a=1&b=2');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('http://localhost/test', $body['request']['url']);
    }

    public function testSendsRequestParams()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test?a=1&b=2');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('1', $body['request']['params']['a']);
        $this->assertEquals('2', $body['request']['params']['b']);
    }

    public function testSendsEnvironmentName()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertEquals('testing', $body['server']['environment']);
    }

    public function testSendsProjectRootPath()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertNotEmpty($body['server']['project_root']);
    }

    public function testSendsProcessId()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertIsInt($body['server']['pid']);
    }

    public function testSendsServerHostname()
    {
        Route::get('/test', [SampleController::class, 'index']);

        $this->get('/test');

        $lastRequest = $this->httpClient->lastRequest();
        $body = json_decode($lastRequest->getBody()->getContents(), true);
        $this->assertNotEmpty($body['server']['hostname']);
    }
}
