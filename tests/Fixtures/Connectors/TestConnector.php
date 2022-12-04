<?php declare(strict_types=1);

namespace Saloon\Laravel\Tests\Fixtures\Connectors;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class TestConnector extends Connector
{
    use AcceptsJson;

    /**
     * Define the base url of the api.
     *
     * @return string
     */
    public function resolveBaseUrl(): string
    {
        return apiUrl();
    }

    /**
     * Define the base headers that will be applied in every request.
     *
     * @return string[]
     */
    protected function defaultHeaders(): array
    {
        return [];
    }
}
