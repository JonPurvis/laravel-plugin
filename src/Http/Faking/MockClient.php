<?php declare(strict_types=1);

namespace Saloon\Laravel\Http\Faking;

use Saloon\Http\Faking\MockClient as BaseMockClient;

class MockClient extends BaseMockClient
{
    /**
     * @var bool
     */
    protected bool $isMocking = false;

    /**
     * Start mocking
     *
     * @param array $responses
     * @return $this
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonInvalidMockResponseCaptureMethodException
     */
    public function startMocking(array $responses = []): self
    {
        $this->addResponses($responses);

        $this->isMocking = true;

        return $this;
    }

    /**
     * Check if we are mocking.
     *
     * @return bool
     */
    public function isMocking(): bool
    {
        return $this->isMocking;
    }

    /**
     * Return the mock client out of the Laravel container
     *
     * @return \Illuminate\Contracts\Foundation\Application|mixed
     */
    public static function resolve(): static
    {
        return resolve(static::class);
    }
}
