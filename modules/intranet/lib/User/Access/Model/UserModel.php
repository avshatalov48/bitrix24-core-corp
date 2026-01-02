<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Model;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Service\ServiceContainer;

class UserModel extends \Bitrix\Main\Access\User\UserModel
{
	private ?User $user = null;

	public function getRoles(): array
	{
		return [];
	}

	public function getPermission(string $permissionId): ?int
	{
		return null;
	}

	public function isAdmin(): bool
	{
		return $this->getUserEntity()->isAdmin();
	}

	public function isIntegrator(): bool
	{
		return $this->getUserEntity()->isIntegrator();
	}

	public function getUserEntity(): User
	{
		if ($this->user)
		{
			return $this->user;
		}

		$userRepository = ServiceContainer::getInstance()->userRepository();
		$this->user = $userRepository->getUserById($this->getUserId());

		return $this->user;
	}
}
