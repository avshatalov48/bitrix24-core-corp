<?php

namespace Bitrix\Sign\Service\Cache\Memory\Sign;

use Bitrix\Main\EO_User;

/**
 * not all fields!
 * @see \Bitrix\Sign\Repository\MemberRepository::getUserModels()
 */
class UserCache
{
	/**
	 * @var array<int, EO_User>
	 */
	private array $usersById = [];

	/**
	 * @param array<int, EO_User> $usersById userId => EO_User
	 * @return $this
	 */
	public function setCache(array $usersById): static
	{
		$this->usersById += $usersById;

		return $this;
	}

	public function setCacheByModel(EO_User $user): static
	{
		$this->usersById[$user->getId()] = $user;

		return $this;
	}

	public function getLoadedModel(int $userId): ?EO_User
	{
		return $this->usersById[$userId] ?? null;
	}
}