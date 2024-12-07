<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Service;

class DynamicItem implements PermissionEntity
{
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

		$result = [];
		foreach ($typesMap->getTypes() as $type)
		{
			$isAutomationEnabled = $typesMap->isAutomationEnabled($type->getEntityTypeId());
			$isStagesEnabled = $typesMap->isStagesEnabled($type->getEntityTypeId());

			$perms = $this->permissions($isAutomationEnabled, $isStagesEnabled);

			$stagesFieldName = htmlspecialcharsbx($typesMap->getStagesFieldName($type->getEntityTypeId()));
			foreach ($typesMap->getCategories($type->getEntityTypeId()) as $category)
			{
				$entityName = htmlspecialcharsbx(
					Service\UserPermissions::getPermissionEntityType($type->getEntityTypeId(), $category->getId())
				);
				$entityTitle = $type->getTitle();
				if ($type->getIsCategoriesEnabled())
				{
					$entityTitle .= ' ' . $category->getName();
				}

				$fields = [];
				if ($type->getIsStagesEnabled())
				{
					$stages = [];
					foreach ($typesMap->getStages($type->getEntityTypeId(), $category->getId()) as $stage)
					{
						$stages[htmlspecialcharsbx($stage->getStatusId())] = $stage->getName();
					}

					$fields = [$stagesFieldName => $stages];

					$perms = $this->permissions($isAutomationEnabled, $isStagesEnabled, $fields[$stagesFieldName]);
				}

				$result[] = new EntityDTO($entityName, $entityTitle, $fields, $perms);
			}
		}

		return $result;
	}
}
