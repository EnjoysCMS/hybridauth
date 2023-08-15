<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth;


use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\User\Profile;

final class Data
{

    private string $provider;
    private string $redirectUrl;
    private string $identifier;
    private array $token;
    private Profile $userProfile;

    /**
     * @param array{
     *     'provider': string,
     *     'redirectUrl': string|null,
     *     'identifier': string|int,
     *     'token': array,
     *     'userProfile': Profile,
     * } $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data)
    {
        if (!array_key_exists('provider', $data)) {
            throw new InvalidArgumentException('The parameter `provider` not set');
        }

        if (!array_key_exists('identifier', $data)) {
            throw new InvalidArgumentException('The parameter `identifier` not set');
        }

        if (!array_key_exists('redirectUrl', $data)) {
            throw new InvalidArgumentException('The parameter `redirectUrl` not set');
        }

        $this->provider = $data['provider'];
        $this->redirectUrl = $data['redirectUrl'] ?? '';
        $this->identifier = $data['identifier'];
        $this->token = $data['token'];
        $this->userProfile = $data['userProfile'];
    }


    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getToken(): array
    {
        return $this->token;
    }


    public function getUserProfile(): Profile
    {
        return $this->userProfile;
    }
}
