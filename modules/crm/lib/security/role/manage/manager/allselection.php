<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

final class AllSelection implements RoleSelectionManager
{
	public const CRITERION = 'all';

	public static function create(?CreateSettingsDto $settingsDto): ?self
	{
		return $settingsDto?->getCriterion() === self::CRITERION ? new self() : null;
	}

	public function buildModels(): array
	{
		$typesToExclude = IntranetManager::getEntityTypesInCustomSections();

		return (new PermissionEntityBuilder())
			->includeAll()
			->excludeEntityTypeIds(Permission::Dynamic, $typesToExclude)
			->exclude(Permission::Button)
			->exclude(Permission::ButtonConfig)
			->exclude(Permission::WebForm)
			->exclude(Permission::WebFormConfig)
			->exclude(Permission::AutomatedSolutionList)
			->exclude(Permission::AutomatedSolutionConfig)
			->buildOfMade()
		;
	}

	public function hasPermissionsToEditRights(): bool
	{
		return Container::getInstance()->getUserPermissions()->canWriteConfig();
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
		$defaultRoot = Container::getInstance()->getRouter()->getDefaultRoot();

		return new Uri($defaultRoot);
	}

	public function getUrl(): ?Uri
	{
		$criterion = self::CRITERION;

		return new Uri("/crm/perms/{$criterion}/");
	}

	public function preSaveChecks(array $userGroups): Result
	{
		return new Result();
	}

	public function isAvailableTool(): bool
	{
		return Container::getInstance()
			->getIntranetToolsManager()
			->checkCrmAvailability()
		;
	}

	public function printInaccessibilityContent(): void
	{
		print AvailabilityManager::getInstance()
			->getCrmInaccessibilityContent()
		;
	}

	public function getGroupCode(): ?string
	{
		return null;
	}

	public function getMenuId(): ?string
	{
		return self::CRITERION;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_CONFIG_PERMISSION_CRM');
	}

	public function getControllerData(): array
	{
		return [
			'criterion' => self::CRITERION,
			'sectionCode' => null,
			'isAutomation' => false,
			'menuId' => $this->getMenuId(),
		];
	}
}
