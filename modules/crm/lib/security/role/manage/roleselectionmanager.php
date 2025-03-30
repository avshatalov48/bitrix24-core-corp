<?php

namespace Bitrix\Crm\Security\Role\Manage;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO\UserGroupsData;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

interface RoleSelectionManager
{
	public static function create(?CreateSettingsDto $settingsDto): ?self;

	/**
	 * @return EntityDTO[]
	 */
	public function buildModels(): array;

	/**
	 * @param UserGroupsData[] $userGroups
	 * @return Result
	 */
	public function preSaveChecks(array $userGroups): Result;

	public function hasPermissionsToEditRights(): bool;

	public function prohibitToSaveRoleWithoutAtLeastOneRight(): bool;

	public function needShowRoleWithoutRights(): bool;

	public function getSliderBackUrl(): ?Uri;

	public function getUrl(): ?Uri;

	public function isAvailableTool(): bool;

	public function printInaccessibilityContent(): void;

	public function getGroupCode(): ?string;

	public function getMenuId(): ?string;
}
