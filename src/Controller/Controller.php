<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Session\Session;
use EnjoysCMS\Core\Auth\Identity;
use EnjoysCMS\Core\Exception\ForbiddenException;
use EnjoysCMS\Core\Exception\NotFoundException;
use EnjoysCMS\Core\Http\Response\RedirectInterface;
use EnjoysCMS\Core\Routing\Annotation\Route;
use EnjoysCMS\Module\Hybridauth\Data;
use EnjoysCMS\Module\Hybridauth\Entities\Hybridauth;
use EnjoysCMS\Module\Hybridauth\HybridauthApp;
use Exception;
use HttpSoft\Message\Uri;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Exception\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

#[Route('oauth', 'hybridauth_', needAuthorized: false)]
final class Controller
{
    public function __construct(
        private readonly HybridauthApp $hybridauthApp,
        private readonly ServerRequestInterface $request,
        private readonly Session $session,
        private readonly RedirectInterface $redirect
    ) {
        $this->hybridauthApp->getHybridauth()->disconnectAllAdapters();
    }

    /**
     * @return never|ResponseInterface|void
     */
    #[Route(
        path: '/callback',
        name: 'callback'
    )]
    public function callback()
    {
        try {
            $storage = $this->session->get('hybridauth', []);
            $redirectUrl = ($storage['redirect'] ?? null);
            $provider = ($storage['provider'] ?? null);
            $method = ($storage['method'] ?? 'auth');

            if ($provider === null) {
                throw new InvalidArgumentException('This page is either invalid or has already been consumed');
            }

            if (!in_array($method, HybridauthApp::ALLOW_METHODS, true)) {
                throw new InvalidArgumentException('Method parameter not allowed');
            }

            $adapter = $this->hybridauthApp->getHybridauth()->getAdapter($provider);
            $adapter->authenticate();

            $userProfile = $adapter->getUserProfile();
            $accessToken = $adapter->getAccessToken();

            $data = new Data([
                'provider' => $provider,
                'redirectUrl' => $redirectUrl,
                'identifier' => $userProfile->identifier,
                'token' => $accessToken,
                'userProfile' => $userProfile,
            ]);

            return $this->hybridauthApp->$method($data);
        } catch (Throwable $e) {
            $redirectUrl ??= 'system/index';

            if ($redirectUrl === 'system/index') {
                return $this->redirect->toRoute(
                    $redirectUrl,
                    [HybridauthApp::ERROR_QUERY => base64_encode($e->getMessage())]
                );
            }

            $url = new Uri(urldecode($redirectUrl));
            $url = $url->withQuery(
                sprintf(
                    '%s=%s',
                    HybridauthApp::ERROR_QUERY,
                    base64_encode($e->getMessage())
                )
            );

            return $this->redirect->toUrl($url->__toString());
        } finally {
            $this->session->delete('hybridauth');
        }
    }


    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @return AdapterInterface|ResponseInterface|void
     */
    #[Route(name: 'authenticate')]
    public function authenticate(UrlGeneratorInterface $urlGenerator)
    {
        $provider = $this->request->getQueryParams()['provider'] ?? '';
        $redirectUrl = $this->request->getQueryParams()['redirect'] ?? $urlGenerator->generate(
            'system/index',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            if (!in_array($this->request->getQueryParams()['method'] ?? 'auth', HybridauthApp::ALLOW_METHODS, true)) {
                throw new InvalidArgumentException('Method parameter not allowed');
            }

            if (empty($provider)) {
                throw new InvalidArgumentException('The provider not select');
            }

            if (!in_array($provider, $this->hybridauthApp->getHybridauth()->getProviders(), true)) {
                $this->session->delete('hybridauth');
                throw new InvalidArgumentException(sprintf('[Error] The provider `%s` - not supported', $provider));
            }

            $this->session->set([
                'hybridauth' => [
                    'provider' => $provider,
                    'method' => $this->request->getQueryParams()['method'] ?? 'auth',
                    'redirect' => $redirectUrl
                ]
            ]);

            return $this->hybridauthApp->getHybridauth()->authenticate($provider);
        } catch (Throwable $e) {
            $url = new Uri(urldecode($redirectUrl));
            $url = $url->withQuery(
                sprintf(
                    '%s=%s',
                    HybridauthApp::ERROR_QUERY,
                    base64_encode($e->getMessage())
                )
            );
            return $this->redirect->toUrl($url->__toString());
        }
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    #[Route(
        path: '/detach',
        name: 'detach'
    )]
    public function detach(Identity $identity, EntityManager $em): ResponseInterface
    {
        if (!$identity->getUser()->isUser()) {
            throw new ForbiddenException();
        }

        $hybridauthData = $em->getRepository(Hybridauth::class)->findOneBy([
            'id' => $this->request->getQueryParams()['id'] ?? null,
            'user' => $identity->getUser()
        ]);

        if ($hybridauthData === null) {
            throw new NotFoundException();
        }

        $em->remove($hybridauthData);
        $em->flush();

        return $this->redirect->toRoute('system/profile');
    }

}
