<?php

namespace Bitrix\HumanResources\Controller;

use Bitrix\HumanResources\Engine\Controller;
use Bitrix\HumanResources\Internals\Service\Container as InternalContainer;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Item;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;

final class User extends Controller
{
	public function getCurrentIdAction(): int
	{
		$userId = CurrentUser::get()->getId();

		return $userId ?? 0;
	}

	/**
	 * @return void
	 */
	public function firstTimeOpenAction(): void
	{
		\CUserOptions::SetOption("humanresources", 'first_time_opened', 'Y');
	}

	public function isUserInMultipleDepartmentsAction(int $userId): bool
	{
		return InternalContainer::getNodeMemberService()->isUserInMultipleNodes($userId);
	}

	public function getInfoByUserMemberAction(
		Item\NodeMember $nodeUserMember,
	): array
	{
		$user = Container::getUserRepository()->getById($nodeUserMember->entityId);

		if (!$user)
		{
			$this->addError(new Error('User not found'));

			return [];
		}

		$userService = Container::getUserService();
		if (!empty($nodeUserMember->roles))
		{
			$role = Container::getRoleHelperService()->getById((int)$nodeUserMember->roles[0]);
			$role = $role?->xmlId ?? null;
		}

		$baseUserInfo = $userService->getBaseInformation($user);
		$baseUserInfo['role'] = $role ?? null;

		return $baseUserInfo;
	}
}