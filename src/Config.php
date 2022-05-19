<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Hybridauth;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use EnjoysCMS\Core\Components\Modules\ModuleConfig;

final class Config
{

    private ModuleConfig $moduleConfig;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(Container $container)
    {
        $this->moduleConfig = $container->make(
            ModuleConfig::class,
            ['moduleName' => 'enjoyscms/hybridauth']
        );
    }

    public function getConfig(): array
    {
        return $this->moduleConfig->getAll();
    }

    public function getModuleConfig(): ModuleConfig
    {
        return $this->moduleConfig;
    }

}
