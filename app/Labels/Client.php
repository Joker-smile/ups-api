<?php

namespace App\Labels\Tracking;

use GuzzleHttp\Pool;
use GuzzleHttp\Client as GClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Closure;

class Client
{

    protected $http;

    public function __construct()
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        $this->http = new GClient(['handler' => $handlerStack]);
    }

    public function pool($requests, Closure $successCallback, Closure $rejectCallback)
    {
        $pool = new Pool($this->http, $requests, [
            'concurrency' => 10,
            'fulfilled' => function ($response, $index) use ($successCallback)
            {
                $successCallback($response, $index);
            },
            'rejected' => function ($reason, $index) use ($rejectCallback)
            {
                $rejectCallback($reason, $index);
            },
            'options' => [
                'delay' => '1',
            ]
        ]);

        $pool->promise()->wait();
    }

    private function retryDecider()
    {
        return function ($retries, Request $request, Response $response = null, RequestException $exception = null)
        {
//            \Log::info($retries);
            // Limit the number of retries to 5
            if ($retries >= 5) {
                return false;
            }

            // Retry connection exceptions
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // Retry on server errors
                if (intval($response->getStatusCode()) >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    private function retryDelay()
    {
        return function ($numberOfRetries)
        {
            return 1000 * $numberOfRetries;
        };
    }

}
