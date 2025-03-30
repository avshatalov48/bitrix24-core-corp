<?php

namespace Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection;

use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Security\Role\Manage\DTO\RoleSelectionManager\CreateSettingsDto;
use Bitrix\Crm\Security\Role\Manage\Enum\Permission;
use Bitrix\Crm\Security\Role\Manage\Manager\EntitySelection;
use Bitrix\Crm\Security\Role\Manage\PermissionEntityBuilder;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Web\Uri;

class CustomSectionEntitySelection extends EntitySelection
{
	private readonly CustomSection $customSection;

	public function __construct(
		CustomSection $customSection,
		int $entityTypeId,
		?int $categoryId = null,
	)
	{
		$this->customSection = $customSection;
		parent::__construct($entityTypeId, $categoryId);
	}

	protected static function doCreate(
		int $entityTypeId,
		?int $categoryId = null,
		?CreateSettingsDto $settingsDto = new CreateSettingsDto(),
	): ?static
	{
		$customSectionCode = $settingsDto?->getCustomSectionCode();
		if ($customSectionCode === null)
		{
			return null;
		}

		$customSection = IntranetManager::getCustomSectionByEntityTypeId($entityTypeId);
		if ($customSectionCode !== $customSection?->getCode())
		{
			return null;
		}

		return new self(
			$customSection,
			$entityTypeId,
			$categoryId,
		);
	}

	public function getUrl(): ?Uri
	{
		$root = IntranetManager::getUrlForCustomSection($this->customSection);
		if ($root === null)
		{
			return null;
		}

		$criterion = $this->buildCriterion();

		return new Uri("{$root}perms/{$criterion}/");
	}

	public function buildModels(): array
	{
		return (new PermissionEntityBuilder())
			->include(Permission::Dynamic)
			->filterByEntityTypeIds(Permission::Dynamic, $this->entityTypeId)
			->filterByCategory(Permission::Dynamic, $this->categoryId)
			->buildOfMade()
		;
	}

	public static function isSuitableEntity(int $entityTypeId, ?int $categoryId = null): bool
	{
		return IntranetManager::isEntityTypeInCustomSection($entityTypeId);
	}


	public function isAvailableTool(): bool
	{
		return Container::getInstance()
			->getIntranetToolsManager()
			->checkExternalDynamicAvailability()
		;
	}

	public function printInaccessibilityContent(): void
	{
		print AvailabilityManager::getInstance()
			->getExternalDynamicInaccessibilityContent()
		;
	}

	public function getMenuId(): ?string
	{
		return null;
	}
}
