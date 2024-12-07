<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets;

class Deal implements PermissionEntity
{
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

		$dealCategoryConfigs = DealCategory::getPermissionRoleConfigurationsWithDefault();

		foreach($dealCategoryConfigs as $typeName => $config)
		{
			$name = isset($config['NAME']) ? $config['NAME'] : $typeName;

			$fields = $this->getStageFieldsFromConfig($config);

			$result[] = new EntityDTO($typeName, $name, $fields, $this->permissions($fields['STAGE_ID']));
		}

		return $result;
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
			$result[htmlspecialcharsbx($stageId)] = $stageName;
		}

		return ['STAGE_ID' => $result];
	}
}