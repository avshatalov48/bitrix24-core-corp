<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Internals\Service\Structure;

use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Config\Storage;
use Bitrix\HumanResources\Enum\Access\RoleCategory;
use Bitrix\Main\Engine\CurrentUser;

final class AccessService
{
	public function checkAccessToEditPermissions(
		RoleCategory $category,
		?int $userId = null,
		bool $checkTariffRestriction = true,
	): bool
	{
		if ($checkTariffRestriction && !Storage::canUsePermissionConfig())
		{
			return false;
		}

		return
			$this->isSupportedRoleCategory($category)
			&& $this->checkAccessToEditRoleCategory($category, $userId)
		;
	}

	private function isSupportedRoleCategory(RoleCategory $category): bool
	{
		return in_array($category, [
			RoleCategory::Department,
			RoleCategory::Team,
		], true);
	}

	private function checkAccessToEditRoleCategory(RoleCategory $roleCategory, ?int $userId = null): bool
	{
		$structureAction = $roleCategory === RoleCategory::Department
			? StructureActionDictionary::ACTION_USERS_ACCESS_EDIT
			: StructureActionDictionary::ACTION_TEAM_ACCESS_EDIT
		;

		return StructureAccessController::can(
			$userId ?? (int)CurrentUser::get()->getId(),
			$structureAction,
		);
	}
}