<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Hybridauth;

final class Config
{

    private const MODULE_NAME = 'enjoyscms/hybridauth';

    public function __construct(private readonly \Enjoys\Config\Config $config)
    {
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }
        return $this->config->get(sprintf('%s->%s', self::MODULE_NAME, $key), $default);
    }

    public function all(): array
    {
        return $this->config->get(self::MODULE_NAME);
    }

}
