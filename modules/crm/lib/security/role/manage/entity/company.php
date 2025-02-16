<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

class Company implements PermissionEntity, FilterableByCategory
{
	use Trait\FilterableByCategory;

	private function permissions(): array
	{
		return PermissionAttrPresets::crmEntityPreset();
	}

	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$result = [];

		$factory = Service\Container::getInstance()->getFactory(CCrmOwnerType::Company);
		if ($factory === null)
		{
			return [];
		}

		$categories = $factory->getCategories();
		$categories = $this->filterCategories($categories);

		foreach ($categories as $category)
		{
			$entityName = Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId());
			$entityTitle = $category->getSingleNameIfPossible();

			if ($category->getIsDefault())
			{
				$entityTitle = Container::getInstance()->getFactory(CCrmOwnerType::Company)->getEntityDescription();
			}

			$result[] = new EntityDTO(
				$entityName,
				$entityTitle,
				[],
				$this->permissions(),
				null,
				'city',
				'--ui-color-palette-orange-50',
			);
		}

		return $result;
	}
}
