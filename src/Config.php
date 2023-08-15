<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Hybridauth;

use EnjoysCMS\Core\Modules\AbstractModuleConfig;

final class Config extends AbstractModuleConfig
{
    public function getModulePackageName(): string
    {
        return 'enjoyscms/hybridauth';
    }
}
