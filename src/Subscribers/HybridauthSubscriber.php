<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Subscribers;


use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class HybridauthSubscriber implements EventSubscriberInterface
{

    public function __construct(private EntityManager $em)
    {
    }

    public static function getSubscribedEvents()
    {
        return [];
    }

}
