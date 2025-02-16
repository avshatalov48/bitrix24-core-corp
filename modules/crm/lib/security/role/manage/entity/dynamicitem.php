<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\EO_ItemCategory;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service;
use Bitrix\Crm\Security\Role\Manage\Entity\Trait;

class DynamicItem implements PermissionEntity, FilterableByTypes, FilterableByCategory
{
	use Trait\FilterableByCategory;
	use Trait\FilterableByTypes;

	private function permissions(bool $isAutomationEnabled, bool $isStagesEnabled, array $stages = []): array
	{
		$permissions = $isAutomationEnabled
			? PermissionAttrPresets::crmEntityPresetAutomation()
			: PermissionAttrPresets::crmEntityPreset()
		;

		$permissions = array_merge(
			$permissions,
			PermissionAttrPresets::crmEntityKanbanHideSum()
		);

		if ($isStagesEnabled)
		{
			$permissions = array_merge(
				$permissions,
				PermissionAttrPresets::crmStageTransition($stages),
			);
		}

		return $permissions;
	}

	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$typesMap = Service\Container::getInstance()->getDynamicTypesMap()->load();
		$types = $this->filterTypes($typesMap->getTypes());

		$result = [];
		foreach ($types as $type)
		{
			$isAutomationEnabled = $typesMap->isAutomationEnabled($type->getEntityTypeId());
			$isStagesEnabled = $typesMap->isStagesEnabled($type->getEntityTypeId());
			$isCategoriesEnabled = $typesMap->isCategoriesEnabled($type->getEntityTypeId());

			$perms = $this->permissions($isAutomationEnabled, $isStagesEnabled);

			$stagesFieldName = $typesMap->getStagesFieldName($type->getEntityTypeId());

			$categories = $typesMap->getCategories($type->getEntityTypeId());
			$categories = $this->filterItemCategories($categories);

			foreach ($categories as $category)
			{
				$entityName = Service\UserPermissions::getPermissionEntityType($type->getEntityTypeId(), $category->getId());

				$fields = [];
				if ($type->getIsStagesEnabled())
				{
					$stages = [];
					foreach ($typesMap->getStages($type->getEntityTypeId(), $category->getId()) as $stage)
					{
						$stages[$stage->getStatusId()] = $stage->getName();
					}

					$fields = [$stagesFieldName => $stages];

					$perms = $this->permissions($isAutomationEnabled, $isStagesEnabled, $fields[$stagesFieldName]);
				}

				$result[] = new EntityDTO(
					$entityName,
					$type->getTitle(),
					$fields,
					$perms,
					$isCategoriesEnabled ? $category->getName() : null,
					'smart-process',
					'--ui-color-accent-light-blue',
				);
			}
		}

		return $result;
	}

	/**
	 * @param Type[] $types
	 * @return Type[]
	 */
	protected function filterTypes(array $types): array
	{
		if ($this->excludeEntityTypeIds !== null)
		{
			$isExclude = fn (Type $type)
				=> !in_array($type->getEntityTypeId(), $this->excludeEntityTypeIds, true)
			;

			$types = array_filter($types, $isExclude);
		}

		if ($this->filterByEntityTypeIds !== null)
		{
			$isRemain = fn (Type $type)
				=> in_array($type->getEntityTypeId(), $this->filterByEntityTypeIds, true)
			;

			$types = array_filter($types, $isRemain);
		}

		return $types;
	}
}
