<?php declare(strict_types=1);

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\ConnectException;
use Saloon\Http\Faking\MockClient;
use Saloon\Contracts\SaloonResponse;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingSaloonRequest;
use Saloon\Http\Responses\PsrResponse;
use Saloon\Exceptions\FatalRequestException;
use Saloon\Http\Responses\SimulatedResponse;
use Saloon\Laravel\Tests\Fixtures\Connectors\InvalidConnectionConnector;
use Saloon\Laravel\Tests\Fixtures\Connectors\TestConnector;
use Saloon\Laravel\Tests\Fixtures\Requests\ErrorRequest;
use Saloon\Laravel\Tests\Fixtures\Requests\UserRequest;

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

    $pool->withResponseHandler(function (SaloonResponse $response) use (&$count) {
        expect($response)->toBeInstanceOf(PsrResponse::class);
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
        expect($ex->getPendingSaloonRequest())->toBeInstanceOf(PendingSaloonRequest::class);

        $count++;
    });

    $promise = $pool->send();

    $promise->wait();

    expect($count)->toEqual(5);
});

test('you can use pool with a mock client added and it wont send real requests', function () {
    $mockResponses = [
        MockResponse::make(200, ['name' => 'Sam']),
        MockResponse::make(200, ['name' => 'Charlotte']),
        MockResponse::make(200, ['name' => 'Mantas']),
        MockResponse::make(200, ['name' => 'Emily']),
        MockResponse::make(500, ['name' => 'Error']),
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

    $pool->withResponseHandler(function (SaloonResponse $response) use (&$count, $mockResponses) {
        expect($response)->toBeInstanceOf(SimulatedResponse::class);
        expect($response->json())->toEqual($mockResponses[$count]->getBody()->all());

        $count++;
    });

    $promise = $pool->send();

    $promise->wait();

    expect($count)->toEqual(5);
});
