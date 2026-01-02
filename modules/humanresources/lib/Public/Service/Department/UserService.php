<?php

namespace Bitrix\HumanResources\Public\Service\Department;

use Bitrix\HumanResources\Public\Service\Container as PublicContainer;
use Bitrix\HumanResources\Public\Service\Node\UserService as NodeUserService;
use Bitrix\HumanResources\Type\StructureRole;

class UserService
{
	private NodeUserService $nodeUserService;

	public function __construct()
	{
		$this->nodeUserService = PublicContainer::getUserService();
	}

	/**
	 * Returns true if user is head or deputy of any department, excluding teams
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public function isHeadOfDepartment(int $userId): bool
	{
		$headMember = $this->nodeUserService->findByUserIdAndStructureRoles(
			$userId,
			[StructureRole::HEAD],
		);

		return $headMember !== null;
	}

	/**
	 * Returns true if user is head or deputy of any department, excluding teams
	 *
	 * @param int $userId
	 *
	 * @return bool
	 */
	public function isHeadOrDeputyOfDepartment(int $userId): bool
	{
		$headMember = $this->nodeUserService->findByUserIdAndStructureRoles(
			$userId,
			[StructureRole::HEAD, StructureRole::DEPUTY_HEAD],
		);

		return $headMember !== null;
	}
}
