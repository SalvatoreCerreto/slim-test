<?php declare(strict_types=1);

namespace App\User\Domain;

use DateTime;


use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

#[Entity, Table(name: 'user')]
class User
{
    private ?DateTime $created_time = null;

    public function __construct(
        private int $id,
        private string $username,
        private string $first_name,
        private string $last_name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): void
    {
        $this->first_name = $first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): void
    {
        $this->last_name = $last_name;
    }

    public function getCreatedTime(): ?DateTime
    {
        return $this->created_time;
    }

    public function onPrePersist(): void
    {
        $this->created_time = new DateTime();
    }

}