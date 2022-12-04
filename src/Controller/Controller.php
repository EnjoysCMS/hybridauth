<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enjoys\Session\Session;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Core\Components\Auth\Identity;
use EnjoysCMS\Core\Components\Helpers\Error;
use EnjoysCMS\Core\Components\Helpers\Redirect;
use EnjoysCMS\Module\Hybridauth\Data;
use EnjoysCMS\Module\Hybridauth\Entities\Hybridauth;
use EnjoysCMS\Module\Hybridauth\HybridauthApp;
use HttpSoft\Message\Uri;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Controller extends BaseController
{
    public function __construct(
        private HybridauthApp $hybridauthApp,
        private UrlGeneratorInterface $urlGenerator,
        private ServerRequestInterface $request,
        private Session $session
    ) {
        parent::__construct();
        $this->hybridauthApp->getHybridauth()->disconnectAllAdapters();
    }

    /**
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    #[Route(
        path: 'oauth/callback',
        name: 'hybridauth/callback',
        options: [
            'acl' => false
        ]
    )]
    public function callbackPage()
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

            $this->session->delete('hybridauth');

            $userProfile = $adapter->getUserProfile();
            $accessToken = $adapter->getAccessToken();

            $data = new Data([
                'provider' => $provider,
                'redirectUrl' => $redirectUrl,
                'identifier' => $userProfile->identifier,
                'token' => $accessToken,
                'userProfile' => $userProfile,
            ]);

            $this->hybridauthApp->$method($data);
        } catch (\Throwable $e) {
            $redirectUrl ??= $this->urlGenerator->generate('system/index');
            $url = new Uri(urldecode($redirectUrl));
            $url = $url->withQuery(
                sprintf(
                    '%s=%s',
                    HybridauthApp::ERROR_QUERY,
                    base64_encode($e->getMessage())
                )
            );
            Redirect::http($url->__toString());
        }
    }

    /**
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     */
    #[Route(
        path: 'oauth',
        name: 'hybridauth/authenticate',
        options: [
            'acl' => false
        ]
    )]
    public function authenticate(): void
    {
        $provider = $this->request->getQueryParams()['provider'];
        $redirectUrl = $this->request->getQueryParams()['redirect'] ??  $this->urlGenerator->generate(
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

            $this->hybridauthApp->getHybridauth()->authenticate($provider);
        } catch (\Throwable $e) {
            $url = new Uri(urldecode($redirectUrl));
            $url = $url->withQuery(
                sprintf(
                    '%s=%s',
                    HybridauthApp::ERROR_QUERY,
                    base64_encode($e->getMessage())
                )
            );
            Redirect::http($url->__toString());
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    #[Route(
        path: 'oauth/detach',
        name: 'hybridauth/detach',
        options: [
            'acl' => false
        ]
    )]
    public function detach(Identity $identity, EntityManager $em, UrlGeneratorInterface $urlGenerator): void
    {
        if (!$identity->getUser()->isUser()) {
            Error::code(403);
        }

        $hybridauthData = $em->getRepository(Hybridauth::class)->findOneBy([
            'id' => $this->request->getQueryParams()['id'] ?? null,
            'user' => $identity->getUser()
        ]);

        if ($hybridauthData === null) {
            Error::code(404);
        }

        $em->remove($hybridauthData);
        $em->flush();

        Redirect::http($urlGenerator->generate('system/profile'));
    }

}
