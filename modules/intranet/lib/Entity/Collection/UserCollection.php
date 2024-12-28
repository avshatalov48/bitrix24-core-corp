<?php

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Intranet\Entity\User;
use Bitrix\Main\ArgumentException;

/**
 * @extends BaseCollection<User>
 */
class UserCollection extends BaseCollection
{
	protected static function getItemClassName(): string
	{
		return User::class;
	}

	public function mapToAccessCodes(): array
	{
		return $this->map(fn(User $user) => $user->getAccessCode());
	}

	/**
	 * @return array<int>
	 */
	public function getIds(): array
	{
		return $this->map(fn(User $user) => $user->getId());
	}

	/**
	 * @param int $userId
	 * @return User|null
	 * @throws ArgumentException
	 */
	public function getByUserId(int $userId): ?User
	{
		return $this->filter(fn(User $user) => $user->getId() === $userId)->first();
	}
}