<?php

namespace EnjoysCMS\Module\Hybridauth\Doctrine\Subscribers;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;

final class SqlitePreFlushSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::preFlush];
    }

    /**
     * @throws Exception
     */
    public function preFlush(PreFlushEventArgs $args): void
    {
        $connection = $args->getObjectManager()->getConnection();

        if ($connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $connection->executeStatement('PRAGMA foreign_keys = ON;');
        }
    }

}
