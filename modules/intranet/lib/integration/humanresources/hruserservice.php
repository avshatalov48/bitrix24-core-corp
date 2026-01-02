<?php

namespace Bitrix\Intranet\Integration\HumanResources;

use Bitrix\HumanResources\Service\Container;
use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\HumanResources\Service\UserService;

class HrUserService
{
	private UserService $hrUserService;

	public function __construct()
	{
		if (!Loader::includeModule('humanresources'))
		{
			return new SystemException('Module "humanresources" is not installed.');
		}

		$this->hrUserService = Container::getUserService();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isEmployee(User $user): bool
	{
		return $this->hrUserService->isEmployee($user->getId());
	}

	public function filterEmployees(UserCollection $userCollection): UserCollection
	{
		return $userCollection->filter(fn (User $user) => $this->isEmployee($user));
	}

	public function filterNotEmployees(UserCollection $userCollection): UserCollection
	{
		return $userCollection->filter(fn (User $user) => !$this->isEmployee($user));
	}
}