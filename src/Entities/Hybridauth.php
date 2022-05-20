<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Hybridauth\Entities;

use Doctrine\ORM\Mapping as ORM;
use EnjoysCMS\Core\Entities\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="hybridauth")
 */
class Hybridauth
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $identifier;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $provider;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $avatar = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $displayName = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $profileUrl = null;


    /**
     * @ORM\ManyToOne(targetEntity="EnjoysCMS\Core\Entities\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private User $user;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    public function setProfileUrl(?string $profileUrl): void
    {
        $this->profileUrl = $profileUrl;
    }
}
