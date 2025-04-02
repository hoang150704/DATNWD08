<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeEnumCommand extends GeneratorCommand
{
    protected $name = 'make:enum';

    protected $description = 'Tạo mới một Enum class';

    protected $type = 'Enum';

    protected function getStub()
    {
        return base_path('stubs/enum.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\Enums';
    }
}
