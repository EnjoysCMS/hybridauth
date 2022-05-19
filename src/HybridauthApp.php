<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth;


use DI\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Cookie\Exception;
use EnjoysCMS\Core\Components\Auth\Authorize;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Core\Entities\Group;
use EnjoysCMS\Core\Entities\User;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


final class HybridauthApp
{
    private Hybridauth $hybridauth;
    private Authorize $authorize;
    private EntityManager $em;
    private array $config;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function __construct(private Container $container)
    {
        $this->config = $this->container->get('Config')->getConfig('hybridauth') ?? [];
        $this->config['callback'] = $this->container->get(UrlGeneratorInterface::class)->generate(
            'hybridauth/callback',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->hybridauth = new Hybridauth($this->config);
        $this->authorize = $this->container->get(Authorize::class);
        $this->em = $this->container->get(EntityManager::class);
    }


    public function getHybridauth(): Hybridauth
    {
        return $this->hybridauth;
    }

    /**
     * @throws OptimisticLockException
     * @throws \Throwable
     * @throws \Doctrine\ORM\ORMException
     * @throws ORMException
     * @throws Exception
     */
    public function auth(array $data)
    {
        if (!array_key_exists('provider', $data)){
            throw new InvalidArgumentException('The parameter `provider` not set');
        }

        if (!array_key_exists('identifier', $data)){
            throw new InvalidArgumentException('The parameter `identifier` not set');
        }

        if (!array_key_exists('redirectUrl', $data)){
            throw new InvalidArgumentException('The parameter `redirectUrl` not set');
        }

        try {

            $user = $this->getUser($data);

            if($user === null){
                if(false === ($this->config['allow-auto-register'] ?? true)){
                    Redirect::http($this->container->get(UrlGeneratorInterface::class)->generate(
                        'hybridauth/error-page',
                        ['reason' => 'disable'],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ));
                }
                $user = $this->registerUser($data);
            }

            $this->authorize->setAuthorized($user, [
                'authenticate' => 'hybridauth'
            ]);

            Redirect::http(urldecode($data['redirectUrl']));

        } catch (\Throwable $e) {
            $this->authorize->logout();
            throw $e;
        }
    }

    private function getUser(array $data): ?User
    {
        /** @var Entities\Hybridauth|null $hybridauthData */
        $hybridauthData = $this->em->getRepository(Entities\Hybridauth::class)->findOneBy([
            'identifier' => $data['identifier'],
            'provider' => $data['provider'],
        ]);
        if ($hybridauthData === null) {
            return null;
        }
        return $hybridauthData->getUser();
    }

    private function registerUser(array $data): User
    {
        $userGroup = $this->em->getRepository(Group::class)->findOneBy(['name' => 'Users']);

        $user = new User();
        $user->setLogin(uniqid('login'));
        $user->setPasswordHash('');
        $user->setName($data['name'] ?? uniqid($data['provider']));
        $user->setEmail($data['email']);
        $user->setGroups($userGroup);
        $this->em->persist($user);

        $hybridauthData = new Entities\Hybridauth();
        $hybridauthData->setIdentifier($data['identifier']);
        $hybridauthData->setUser($user);
        $hybridauthData->setProvider($data['provider']);
        $this->em->persist($hybridauthData);

        $this->em->flush();

        return $user;
    }

}
