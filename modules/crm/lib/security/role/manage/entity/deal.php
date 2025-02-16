<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;
use Bitrix\Crm\Security\Role\Manage\Entity\Trait\FilterableByCategory as FilterableByCategoryTrait;

class Deal implements PermissionEntity, FilterableByCategory
{
	use FilterableByCategoryTrait;

	private function permissions(array $stages): array
	{
		return array_merge(
			PermissionAttrPresets::crmEntityPresetAutomation(),
			PermissionAttrPresets::crmEntityKanbanHideSum(),
			PermissionAttrPresets::crmStageTransition($stages)
		);
	}

	/**
	 * @return EntityDTO[]
	 */
	public function make(): array
	{
		$result = [];

		$dealCategoryConfigs = $this->getDealCategoriesConfig();

		foreach($dealCategoryConfigs as $typeName => $config)
		{
			$name = \CCrmOwnerType::GetDescription(\CCrmOwnerType::Deal);
			$description = $config['CATEGORY_NAME'] ?? null;

			$fields = $this->getStageFieldsFromConfig($config);

			$result[] = new EntityDTO(
				$typeName,
				$name,
				$fields,
				$this->permissions($fields['STAGE_ID']),
				$description,
				'deal',
				'--ui-color-accent-purple',
			);
		}

		return $result;
	}

	private function getDealCategoriesConfig(): array
	{
		return $this->filterByCategoryId === null
			? DealCategory::getPermissionRoleConfigurationsWithDefault()
			: DealCategory::getPermissionRoleConfiguration($this->filterByCategoryId)
		;
	}

	private function getStageFieldsFromConfig(array $config): array
	{
		$stageIdFields = $config['FIELDS']['STAGE_ID'] ?? null;

		if ($stageIdFields === null)
		{
			throw new \Exception('Deal mast have stages');
		}

		$result = [];
		foreach ($stageIdFields as $stageId => $stageName)
		{
			$result[$stageId] = $stageName;
		}

		return ['STAGE_ID' => $result];
	}
}
