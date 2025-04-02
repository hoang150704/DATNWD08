<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeServiceCommand extends GeneratorCommand
{
    protected $name = 'make:service';

    protected $description = 'Tạo mới một Service class';

    protected $type = 'Service';

    protected function getStub()
    {
        return base_path('stubs/service.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\Services';
    }
}
