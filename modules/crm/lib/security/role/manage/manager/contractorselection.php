<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager;

use Bitrix\Crm\Integration\Catalog\Contractor\CategoryRepository;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\GroupCodeGenerator;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\Manager\Contract\SectionableRoleSelectionManager;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

final class ContractorSelection implements SectionableRoleSelectionManager
{
	private const CRITERION = 'contractor';

	public static function create(?CreateSettingsDto $settingsDto): ?self
	{
		if (!CategoryRepository::isAtLeastOneContractorExists())
		{
			return null;
		}

		return $settingsDto?->getCriterion() === self::CRITERION ? new self() : null;
	}

	public function buildModels(): array
	{
		return (new PermissionEntityBuilder())
			->include(Permission::ContractorContact)
			->include(Permission::ContractorCompany)
			->include(Permission::ContractorConfig)
			->buildOfMade();
	}

	public function preSaveChecks(array $userGroups): Result
	{
		return new Result();
	}

	public function hasPermissionsToEditRights(): bool
	{
		return Container::getInstance()->getUserPermissions()->inventoryManagementContractor()->canWriteConfig();
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
		return new Uri(SITE_DIR . 'shop/documents/');
	}

	public function getUrl(): ?Uri
	{
		$criterion = self::CRITERION;

		return new Uri("/crm/perms/{$criterion}/");
	}

	public function isAvailableTool(): bool
	{
		$toolsManager = Container::getInstance()->getIntranetToolsManager();

		return $toolsManager->checkInventoryAvailability()
			&& $toolsManager->checkCrmAvailability();
	}

	public function printInaccessibilityContent(): void
	{
		$availabilityManager = AvailabilityManager::getInstance();
		$toolsManager = Container::getInstance()->getIntranetToolsManager();

		if (!$toolsManager->checkCrmAvailability())
		{
			print $availabilityManager->getCrmInaccessibilityContent();

			return;
		}

		if (!$toolsManager->checkInventoryAvailability())
		{
			print $availabilityManager->getInventoryInaccessibilityContent();
		}
	}

	public function getGroupCode(): ?string
	{
		return GroupCodeGenerator::getContractorGroupCode();
	}

	public function getMenuId(): ?string
	{
		return self::CRITERION;
	}

	public function getTitle(): string
	{
		return Loc::getMessage('CRM_PERMISSIONS_CONTRACTOR_SELECTION_SECTION_TITLE');
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
