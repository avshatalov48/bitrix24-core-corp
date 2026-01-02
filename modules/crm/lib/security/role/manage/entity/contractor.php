<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Integration\Catalog;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use CCrmOwnerType;

final class Contractor implements PermissionEntity
{
	/**
	 * @param int $entityTypeId of Contact or Company
	 */
	public function __construct(
		private readonly int $entityTypeId,
	)
	{
	}

	public function make(): array
	{
		$category = Catalog\Contractor\CategoryRepository::getByEntityTypeId($this->entityTypeId);
		if ($category === null)
		{
			return [];
		}

		$entityName = (new PermissionEntityTypeHelper($this->entityTypeId))
			->getPermissionEntityTypeForCategory($category->getId());

		return [
			new EntityDTO(
				$entityName,
				$category->getSingleNameIfPossible(),
				[],
				$this->permissions(),
				null,
				$this->iconCode(),
				$this->iconColor(),
			),
		];
	}

	private function permissions(): array
	{
		return PermissionAttrPresets::crmEntityPreset();
	}

	private function iconCode(): ?string
	{
		return match ($this->entityTypeId) {
			CCrmOwnerType::Contact => 'person',
			CCrmOwnerType::Company => 'city',
			default => null,
		};
	}

	private function iconColor(): ?string
	{
		return match ($this->entityTypeId) {
			CCrmOwnerType::Contact => '--ui-color-palette-green-50',
			CCrmOwnerType::Company => '--ui-color-palette-orange-50',
			default => null,
		};
	}
}
