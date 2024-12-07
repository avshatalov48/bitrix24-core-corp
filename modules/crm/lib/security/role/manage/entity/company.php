<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

class Company implements PermissionEntity
{
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

		foreach ($factory->getCategories() as $category)
		{

			$entityName = htmlspecialcharsbx(Service\UserPermissions::getPermissionEntityType($factory->getEntityTypeId(), $category->getId()));
			$entityTitle = htmlspecialcharsbx($category->getSingleNameIfPossible());

			if ($category->getIsDefault())
			{
				$entityTitle = Container::getInstance()->getFactory(CCrmOwnerType::Company)->getEntityDescription();
			}

			$result[] = new EntityDTO($entityName, $entityTitle, [], $this->permissions());
		}

		return $result;
	}

}