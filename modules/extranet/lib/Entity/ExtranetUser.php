<?php

namespace Bitrix\Extranet\Entity;

use Bitrix\Extranet\Contract;
use Bitrix\Extranet\Enum;
use Bitrix\Main\EO_User;

class ExtranetUser implements Contract\Entity
{
	public function __construct(
		private readonly int $userId,
		private readonly string $role,
		private readonly ?int $id = null,
		private readonly ?EO_User $user = null,
	)
	{
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getRole(): Enum\User\ExtranetRole
	{
		return Enum\User\ExtranetRole::tryFrom($this->role);
	}

	public function isChargeable(): bool
	{
		return $this->getRole()->isChargeable();
	}

	public function getUser(): ?EO_User
	{
		return $this->user;
	}
}
