<?php

declare(strict_types=1);

namespace Saloon\Laravel\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class ListCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'saloon:list';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List all Saloon Authenticators, Connectors, Requests, Plugins and Responses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();

        $this->components->twoColumnDetail(
            '<fg=green;options=bold>General</>',
            '<fg=white>Integrations: ' . count($this->getIntegrations()) . '</>'
        );

        $this->newLine();

        foreach ($this->getIntegrations() as $integration) {
            $this->getIntegrationOutput($integration);

            foreach ($this->getIntegrationAuthenticators($integration) as $integrationAuthenticator) {
                $this->getIntegrationAuthenticatorOutput($integrationAuthenticator);
            }

            foreach ($this->getIntegrationConnectors($integration) as $integrationConnector) {
                $this->getIntegrationConnectorOutput($integrationConnector);
            }

            foreach ($this->getIntegrationRequests($integration) as $integrationRequest) {
                $this->getIntegrationRequestOutput($integrationRequest);
            }

            foreach ($this->getIntegrationPlugins($integration) as $integrationPlugin) {
                $this->getIntegrationPluginOutput($integrationPlugin);
            }

            foreach ($this->getIntegrationResponses($integration) as $integrationResponse) {
                $this->getIntegrationResponseOutput($integrationResponse);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function getIntegrations(): array
    {
        if (! $files = glob(config('saloon.integrations_path').'/*')) {
            return [];
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function getIntegrationConnectors(string $integration): array
    {
        if (! $files = glob($integration . '/*Connector.php')) {
            return [];
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function getIntegrationRequests(string $integration): array
    {
        if (! $files = glob($integration . '/Requests/*')) {
            return [];
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function getIntegrationPlugins(string $integration): array
    {
        if (! $files = glob($integration . '/Plugins/*')) {
            return [];
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function getIntegrationResponses(string $integration): array
    {
        if (! $files = glob($integration . '/Responses/*')) {
            return [];
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function getIntegrationAuthenticators(string $integration): array
    {
        if (! $files = glob($integration . '/Auth/*')) {
            return [];
        }

        return $files;
    }

    protected function getIntegrationOutput(string $integration): void
    {
        $this->components->twoColumnDetail(
            '<fg=green;options=bold>' . Str::afterLast($integration, '/') . '</>',
            sprintf(
                '<fg=white>Authenticators: %s / Connectors: %s / Requests: %s / Plugins: %s / Responses: %s</>',
                count($this->getIntegrationAuthenticators($integration)),
                count($this->getIntegrationConnectors($integration)),
                count($this->getIntegrationRequests($integration)),
                count($this->getIntegrationPlugins($integration)),
                count($this->getIntegrationResponses($integration)),
            )
        );
    }

    protected function getIntegrationAuthenticatorOutput(string $authenticator): void
    {
        $this->components->twoColumnDetail(
            '<fg=red>Authenticator</> <fg=gray>...</> ' . Str::afterLast($authenticator, '/')
        );
    }

    protected function getIntegrationConnectorOutput(string $connector): void
    {
        $this->components->twoColumnDetail(
            '<fg=blue>Connector</> <fg=gray>.......</> ' . Str::afterLast($connector, '/'),
            '<fg=gray>' . $this->getIntegrationConnectorBaseUrl($connector) . '</>'
        );
    }

    protected function getIntegrationRequestOutput(string $request): void
    {
        $requestMethod = Str::afterLast($this->getIntegrationRequestMethod($request), ':');

        $requestMethodOutputColour = match ($requestMethod) {
            'GET' => 'blue',
            'PATCH', 'POST', 'PUT' => 'green',
            'DELETE' => 'red',
            default => 'magenta'
        };

        $this->components->twoColumnDetail(
            '<fg=magenta>Request</> <fg=gray>.........</> ' .
            Str::afterLast($request, '/'),
            ' <fg=gray>' . $this->getIntegrationRequestEndpoint($request) . '</>' .
            ' <fg=' . $requestMethodOutputColour . '>' .
            Str::afterLast($this->getIntegrationRequestMethod($request), ':') . '</> '
        );
    }


    protected function getIntegrationPluginOutput(string $plugin): void
    {
        $this->components->twoColumnDetail(
            '<fg=cyan>Plugin</> <fg=gray>..........</> ' . Str::afterLast($plugin, '/')
        );
    }


    protected function getIntegrationResponseOutput(string $response): void
    {
        $this->components->twoColumnDetail(
            '<fg=yellow>Response</> <fg=gray>........</> ' . Str::afterLast($response, '/')
        );
    }

    protected function getIntegrationRequestMethod(string $request): string
    {
        $contents = file_get_contents($request);

        if ($contents === false) {
            $contents = '';
        }

        return Str::match('/\$method\s*=\s*(.*?);/', $contents);
    }


    protected function getIntegrationRequestEndpoint(string $request): string
    {
        $contents = file_get_contents($request);

        if ($contents === false) {
            $contents = '';
        }

        $regex = '/public\s+function\s+resolveEndpoint\(\):\s+string\s*\{\s*return\s+(.*?);/s';

        $match = Str::match($regex, $contents);
        $matchSegments = explode('/', $match);

        foreach ($matchSegments as $key => $matchSegment) {
            if (Str::contains($matchSegment, '$this->')) {
                $matchSegments[$key] = '{' . Str::before(
                    Str::after(str_replace(' ', '', $matchSegment), '>'),
                    '.\''
                ) . '}';
            }
        }

        return str_replace('\'', '', implode('/', $matchSegments));
    }


    protected function getIntegrationConnectorBaseUrl(string $connector): string
    {
        $contents = file_get_contents($connector);

        if ($contents === false) {
            $contents = '';
        }

        $regex = '/public\s+function\s+resolveBaseUrl\(\):\s+string\s*\{\s*return\s+\'(.*?)\';\s*/s';
        $matches = Str::match($regex, $contents);

        return Str::after($matches, '://');
    }
}
