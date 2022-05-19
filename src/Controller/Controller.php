<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Controller;


use Enjoys\ServerRequestWrapper;
use Enjoys\Session\Session;
use EnjoysCMS\Core\BaseController;
use EnjoysCMS\Module\Hybridauth\Data;
use EnjoysCMS\Module\Hybridauth\HybridauthApp;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class Controller extends BaseController
{
    public function __construct(
        private HybridauthApp $hybridauthApp,
        private ServerRequestWrapper $request,
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
    public function callbackPage(): void
    {
        $storage = $this->session->get('hybridauth', []);
        $redirectUrl = ($storage['redirect'] ?? null);
        $provider = ($storage['provider'] ?? null);

        if ($provider === null) {
            throw new InvalidArgumentException('This page is either invalid or has already been consumed');
        }

        $adapter = $this->hybridauthApp->getHybridauth()->getAdapter($provider);
        $adapter->authenticate();

        $this->session->delete('hybridauth');

        $userProfile = $adapter->getUserProfile();
        $accessToken = $adapter->getAccessToken();

        $this->hybridauthApp->auth(
            new Data([
                'provider' => $provider,
                'redirectUrl' => $redirectUrl,
                'identifier' => $userProfile->identifier,
                'token' => $accessToken,
                'userProfile' => $userProfile,
            ])
        );
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
    public function authenticate(UrlGeneratorInterface $urlGenerator): void
    {
        $provider = $this->request->getQueryData('provider');

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
                'redirect' => $this->request->getQueryData(
                    'redirect',
                    $urlGenerator->generate(
                        'system/index',
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL
                    )
                )
            ]
        ]);

        $this->hybridauthApp->getHybridauth()->authenticate($provider);
    }

    #[Route(
        path: 'oauth/error/{reason}',
        name: 'hybridauth/error-page',
        options: [
            'acl' => false
        ]
    )]
    public function errorPage(): ResponseInterface
    {
        $reason = $this->request->getAttributesData('reason');
        // todo
        return $this->responseText(
            match ($reason) {
                'disable' => 'Запрещена регистрация через соцсети, разрешен только вход с ранее привязанными аккаунтами',
                default => throw new InvalidArgumentException('Unknown error')
            }
        );
    }

}
