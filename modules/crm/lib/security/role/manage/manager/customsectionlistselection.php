<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class CustomSectionListSelection implements RoleSelectionManager
{
	public const CRITERION = 'AUTOMATED_SOLUTION_LIST';

	public static function create(?CreateSettingsDto $settingsDto): ?self
	{
		return $settingsDto?->getCriterion() === self::CRITERION ? new self() : null;
	}

	public function buildModels(): array
	{
		return (new PermissionEntityBuilder())
			->include(Permission::AutomatedSolutionList)
			->buildOfMade()
		;
	}

	public function preSaveChecks(array $userGroups): Result
	{
		return new Result();
	}

	public function hasPermissionsToEditRights(): bool
	{
		return Container::getInstance()->getUserPermissions()->isAutomatedSolutionsAdmin();
	}

	public function prohibitToSaveRoleWithoutAtLeastOneRight(): bool
	{
		return false;
	}

	public function needShowRoleWithoutRights(): bool
	{
		return true;
	}

	public function getSliderBackUrl(): ?Uri
	{
		return Container::getInstance()->getRouter()->getAutomatedSolutionListUrl();
	}

	public function getUrl(): ?Uri
	{
		return new Uri("/automation/type/automated_solution/permissions/");
	}

	public function isAvailableTool(): bool
	{
		return Container::getInstance()->getIntranetToolsManager()->checkExternalDynamicAvailability();
	}

	public function printInaccessibilityContent(): void
	{
		print AvailabilityManager::getInstance()->getExternalDynamicInaccessibilityContent();
	}

	public function getGroupCode(): ?string
	{
		return GroupCodeGenerator::getAutomatedSolutionListCode();
	}

	public function getMenuId(): ?string
	{
		return self::CRITERION;
	}

	public function getControllerData(): array
	{
		return [
			'criterion' => self::CRITERION,
			'sectionCode' => null,
			'isAutomation' => true,
			'menuId' => $this->getMenuId(),
		];
	}
}
