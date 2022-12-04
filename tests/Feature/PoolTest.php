<?php declare(strict_types=1);

use Saloon\Http\PendingRequest;
use Saloon\Http\Faking\MockClient;
use Saloon\Contracts\SaloonResponse;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Responses\PsrResponse;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\ConnectException;
use Saloon\Exceptions\FatalRequestException;
use Saloon\Http\Responses\Response;
use Saloon\Http\Responses\SimulatedResponse;
use Saloon\Laravel\Tests\Fixtures\Requests\UserRequest;
use Saloon\Laravel\Tests\Fixtures\Requests\ErrorRequest;
use Saloon\Laravel\Tests\Fixtures\Connectors\TestConnector;
use Saloon\Laravel\Tests\Fixtures\Connectors\InvalidConnectionConnector;
use Saloon\Contracts\Response as ResponseContract;

test('you can create a pool on a connector', function () {
    $connector = new TestConnector;
    $count = 0;

    $pool = $connector->pool([
        new UserRequest,
        new UserRequest,
        new UserRequest,
        new UserRequest,
        new UserRequest,
    ]);

    $pool->setConcurrency(5);

    $pool->withResponseHandler(function (ResponseContract $response) use (&$count) {
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->json())->toEqual([
            'name' => 'Sammyjo20',
            'actual_name' => 'Sam',
            'twitter' => '@carre_sam',
        ]);

        $count++;
    });

    $promise = $pool->send();

    expect($promise)->toBeInstanceOf(PromiseInterface::class);

    $promise->wait();

    expect($count)->toEqual(5);
});

test('if a pool has a request that cannot connect it will be caught in the handleException callback', function () {
    $connector = new InvalidConnectionConnector;
    $count = 0;

    $pool = $connector->pool([
        new UserRequest,
        new UserRequest,
        new UserRequest,
        new UserRequest,
        new UserRequest,
    ]);

    $pool->setConcurrency(5);

    $pool->withExceptionHandler(function (FatalRequestException $ex) use (&$count) {
        expect($ex)->toBeInstanceOf(FatalRequestException::class);
        expect($ex->getPrevious())->toBeInstanceOf(ConnectException::class);
        expect($ex->getPendingRequest())->toBeInstanceOf(PendingRequest::class);

        $count++;
    });

    $promise = $pool->send();

    $promise->wait();

    expect($count)->toEqual(5);
});

test('you can use pool with a mock client added and it wont send real requests', function () {
    $mockResponses = [
        MockResponse::make(['name' => 'Sam']),
        MockResponse::make(['name' => 'Charlotte']),
        MockResponse::make(['name' => 'Mantas']),
        MockResponse::make(['name' => 'Emily']),
        MockResponse::make(['name' => 'Error'], 500),
    ];

    $mockClient = new MockClient($mockResponses);

    $connector = new TestConnector;
    $connector->withMockClient($mockClient);
    $count = 0;

    $pool = $connector->pool([
        new UserRequest,
        new UserRequest,
        new UserRequest,
        new UserRequest,
        new ErrorRequest,
    ]);

    $pool->setConcurrency(6);

    $pool->withResponseHandler(function (ResponseContract $response) use (&$count, $mockResponses) {
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->json())->toEqual($mockResponses[$count]->getBody()->all());

        $count++;
    });

    $promise = $pool->send();

    $promise->wait();

    expect($count)->toEqual(5);
});
