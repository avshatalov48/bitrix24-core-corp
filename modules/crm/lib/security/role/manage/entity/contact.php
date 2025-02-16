<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

class Contact implements PermissionEntity, FilterableByCategory
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
		$factory = Service\Container::getInstance()->getFactory(CCrmOwnerType::Contact);
		if ($factory === null)
		{
			return [];
		}

		$hiddenContactCategories = [
			Service\Factory\SmartDocument::CONTACT_CATEGORY_CODE,
		];

		$result = [];

		$categories = $factory->getCategories();
		$categories = $this->filterCategories($categories);

		foreach ($categories as $category)
		{
			if (in_array($category->getCode(), $hiddenContactCategories))
			{
				continue;
			}

			$entityName = Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId());
			$entityTitle = $category->getSingleNameIfPossible();

			if ($category->getIsDefault())
			{
				$entityTitle = Container::getInstance()->getFactory(CCrmOwnerType::Contact)->getEntityDescription();
			}

			$result[] = new EntityDTO(
				$entityName,
				$entityTitle,
				[],
				$this->permissions(),
				null,
				'person',
				'--ui-color-palette-green-50',
			);
		}

		return $result;
	}
}
