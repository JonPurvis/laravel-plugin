<?php

declare(strict_types=1);

namespace Saloon\Laravel\Console\Commands;

use Illuminate\Support\Arr;
use Saloon\Enums\Method;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\select;

class MakeRequest extends MakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'saloon:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Saloon request class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Saloon Request';

    /**
     * The namespace to place the file
     *
     * @var string
     */
    protected $namespace = '\Http\Integrations\{integration}\Requests';

    protected $stub = 'saloon.request.stub';

    protected function getOptions(): array
    {
        return [
            ['method', null, InputOption::VALUE_REQUIRED, 'the method of the request'],
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if ($this->didReceiveOptions($input)) {
            return;
        }

        $methodType = select(
            'What method should the saloon request send?',
            Arr::pluck(Method::cases(), 'name')
        );

        $input->setOption('method', $methodType);
    }

    protected function buildClass($name): MakeRequest|string
    {
        $stub = $this->getStub();

        return $this->replaceNamespace($stub, $name)->replaceClass(
            $this->replaceMethod($stub, $this->option('method')),
            $name
        );
    }

    protected function replaceMethod($stub, $name): string
    {
        return str_replace('{{ method }}', $name, $stub);
    }
}
