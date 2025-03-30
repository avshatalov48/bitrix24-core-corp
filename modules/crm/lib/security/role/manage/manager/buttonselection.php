<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager;

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Security\Role\Manage\RoleSelectionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class ButtonSelection implements RoleSelectionManager
{
	public const CRITERION = 'button';

	public static function create(?CreateSettingsDto $settingsDto): ?self
	{
		$criterion = $settingsDto?->getCriterion();

		return ($criterion === self::CRITERION) ? new self() : null;
	}

	public function buildModels(): array
	{
		return (new PermissionEntityBuilder())
			->include(Permission::Button)
			->include(Permission::ButtonConfig)
			->buildOfMade()
		;
	}

	public function preSaveChecks(array $userGroups): Result
	{
		return new Result();
	}

	public function hasPermissionsToEditRights(): bool
	{
		return Container::getInstance()->getUserPermissions()->canWriteButtonConfig();
	}

	public function prohibitToSaveRoleWithoutAtLeastOneRight(): bool
	{
		return true;
	}

	public function needShowRoleWithoutRights(): bool
	{
		return true;
	}

	public function getSliderBackUrl(): ?Uri
	{
		return new Uri('/crm/button/');
	}

	public function getUrl(): ?Uri
	{
		$criterion = self::CRITERION;

		return new Uri("/crm/perms/{$criterion}/");
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
		return \Bitrix\Crm\Security\Role\GroupCodeGenerator::getWidgetGroupCode();
	}

	public function getMenuId(): ?string
	{
		return self::CRITERION;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_CONFIG_PERMISSION_BUTTON');
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
