<?php

declare(strict_types=1);

namespace EnjoysCMS\Module\Hybridauth;

final class Config
{

    private const MODULE_NAME = 'enjoyscms/hybridauth';


    public function __construct(
        private readonly \Enjoys\Config\Config $config,
//        private Container $container,
//        private Session $session,
//        private EntityManager $em,
//        private LoggerInterface $logger,
//        ModuleCollection $moduleCollection
    )
    {
//


//        if (file_exists($module->path . '/config.yml')) {
//            $config->addConfig(
//                [
//                    self::MODULE_NAME => file_get_contents($module->path . '/config.yml')
//                ],
//                ['flags' => Yaml::PARSE_CONSTANT],
//                \Enjoys\Config\Config::YAML,
//                false
//            );
//        }
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config->get(self::MODULE_NAME);
        }
        return $this->config->get(sprintf('%s->%s', self::MODULE_NAME, $key), $default);
    }


    public function all(): array
    {
        return $this->config->get();
    }

}
