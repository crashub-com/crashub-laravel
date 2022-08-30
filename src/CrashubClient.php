<?php

namespace Crashub;

class CrashubClient
{
    private $apiUrl;
    private $httpClient;
    private $context;

    function __construct($httpClient)
    {
        $this->apiUrl = config('crashub.api_url') ?: 'https://app.crashub.com/api/crashes';
        $this->httpClient = $httpClient;
        $this->context = [];
    }

    public function report(\Throwable $exception, $context = [])
    {
        try
        {
            $requestBody = [
                'exception' => [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'callstack' => $this->buildCallstack($exception),
                ],
            ];

            if (request()->user())
            {
                $this->context = array_merge(['user_id' => request()->user()->getAuthIdentifier()], $this->context);
            }

            $this->context = array_merge($this->context, $context);

            if (sizeof($this->context) > 0) {
                $requestBody['context'] = $this->context;
            }

            $requestBody['request'] = [
                'component' => $this->controllerName(),
                'action' => $this->actionName(),
                'url' => request()->url(),
                'method' => request()->method(),
                'params' => request()->all(),
            ];

            $requestBody['server'] = [
                'project_root' => base_path(),
                'environment' => app()->environment(),
                'hostname' => gethostname(),
                'pid' => getmypid(),
            ];

            $response = $this->httpClient->post($this->apiUrl,[
                'json' => $requestBody,
                'headers' => [
                    'X-Project-Key' => config('crashub.project_key'),
                ],
            ]);
        }
        catch (\Throwable $e)
        {
            \Illuminate\Support\Facades\Log::error($e);
        }
    }

    public function context($key, $value = null)
    {
        if (is_array($key))
        {
            $this->context = array_merge($this->context, $key);
        }
        else
        {
            $this->context[$key] = $value;
        }
    }

    private function buildCallstack(\Throwable $exception)
    {
        $callstack = [];

        $file = $exception->getFile();
        $line = $exception->getLine();

        foreach ($exception->getTrace() as $frame) {
            $callstack[] = [
                'file' => $file,
                'line' => $line,
                'method' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'snippet' => $this->buildCodeSnippet($file, $line),
            ];

            $file = $frame['file'] ?? '';
            $line = $frame['line'] ?? 'unknown';
        }

        $callstack[] = [
            'file' => $file,
            'line' => $line,
            'method' => '[top]',
            'class' => null,
            'snippet' => $this->buildCodeSnippet($file, $line),
        ];

        return $callstack;
    }

    private function buildCodeSnippet(string $fileName, int $lineNumber)
    {
        $linesCount = 9;

        if (!file_exists($fileName)) { return null; }

        try
        {
            $file = new \SplFileObject($fileName);

            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key() + 1;
            $startLine = max($lineNumber - floor($linesCount / 2), 1);
            $endLine = $startLine + ($linesCount - 1);

            if ($endLine > $totalLines) {
                $endLine = $totalLines;
                $startLine = max($endLine - ($linesCount - 1), 1);
            }

            $result = [];

            $file->seek($startLine - 1);
            $line = $file->current();
            $currentLineNumber = $startLine;

            while ($currentLineNumber <= $endLine) {
                $result[$currentLineNumber] = rtrim(substr($line, 0, 250));

                $file->next();
                $line = $file->current();
                $currentLineNumber++;
            }

            return $result;
        }
        catch (RuntimeException $exception) { return null; }
    }

    private function controllerName()
    {
        $routeAction = \Route::currentRouteAction();

        if (!$routeAction)
        {
            return null;
        }

        return explode('@', $routeAction)[0];
    }

    private function actionName()
    {
        $routeAction = \Route::currentRouteAction();

        if (!$routeAction)
        {
            return null;
        }

        return explode('@', $routeAction)[1] ?? null;
    }
}
