<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Hybridauth;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

final class Config
{

    private \EnjoysCMS\Core\Components\Modules\ModuleConfig $moduleConfig;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(Container $container)
    {
        $this->moduleConfig = $container->make(
            \EnjoysCMS\Core\Components\Modules\ModuleConfig::class,
            ['moduleName' => 'enjoyscms/hybridauth']
        );
    }

    public function getConfig(): array
    {
        return $this->moduleConfig->getAll();
    }

    public function getModuleConfig(): \EnjoysCMS\Core\Components\Modules\ModuleConfig
    {
        return $this->moduleConfig;
    }

}
