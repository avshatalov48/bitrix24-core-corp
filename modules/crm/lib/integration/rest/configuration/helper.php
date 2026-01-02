<?php
namespace Bitrix\Crm\Integration\Rest\Configuration;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use CCrmOwnerType;

/** @internal */
class Helper
{
	public function exportCrmDynamicTypesInfo(array $params = []): array
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		$result = [];

		$automatedSolutionModeParams =
			(
				isset($params['automatedSolutionModeParams'])
				&& is_array($params['automatedSolutionModeParams'])
			)
				? $params['automatedSolutionModeParams']
				: []
		;

		$helper = new Helper();

		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadCategories' => true,
			'isLoadStages' => true,
		]);

		foreach ($typesMap->getTypes() as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			$customSectionId = $type->getCustomSectionId() ?? 0;
			if (
				is_int($entityTypeId)
				&& CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
				&& $helper->checkDynamicTypeExportConditions(
					array_merge(
						$automatedSolutionModeParams,
						$helper->getDynamicTypeCheckExportParamsByEntityTypeId($entityTypeId)
					)
				)
			)
			{
				$typeId = $type->getId();
				$userFieldEntityId =
					ServiceLocator::getInstance()
						->get('crm.type.factory')
						->getUserFieldEntityId($type->getId())
				;
				$result[$typeId] = [
					'entityTypeId' => $entityTypeId,
					'customSectionId' => $customSectionId,
					'categoryIds' => [],
					'userFieldEntityId' => $userFieldEntityId,
					'userFieldNames' => [],
				];
				foreach ($typesMap->getCategories($type->getEntityTypeId()) as $category)
				{
					$result[$typeId]['categoryIds'][] = $category->getId();
				}

				$userFieldInfos = $USER_FIELD_MANAGER->GetUserFields(
					$userFieldEntityId,
					0,
					LANGUAGE_ID,
					false,
					['FIELD_NAME']
				);
				foreach ($userFieldInfos as $userFieldInfo)
				{
					$result[$typeId]['userFieldNames'][] = $userFieldInfo['FIELD_NAME'];
				}
			}
		}

		return $result;
	}

	public function getDynamicCategoryCode(int $entityTypeId, int $categoryId): string
	{
		return "DT{$entityTypeId}_$categoryId";
	}

	public function getDynamicEntityTypeIdByOldEntityTypeId(int $oldEntityTypeId, array $ratio): int
	{
		$newDynamicEntityTypeId = 0;

		$newDynamicEntityTypeIdRatioKey = CCrmOwnerType::DynamicTypePrefixName . $oldEntityTypeId;
		if (
			isset($ratio['CRM_DYNAMIC_TYPES'][$newDynamicEntityTypeIdRatioKey])
			&& $ratio['CRM_DYNAMIC_TYPES'][$newDynamicEntityTypeIdRatioKey] > 0
		)
		{
			$entityTypeId = (int)$ratio['CRM_DYNAMIC_TYPES'][$newDynamicEntityTypeIdRatioKey];
			if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
			{
				$newDynamicEntityTypeId = $entityTypeId;
			}
		}

		return $newDynamicEntityTypeId;
	}

	public function getNewDynamicTypeCategoryIdByRatio(
		int $oldDynamicEntityTypeId,
		int $oldCategoryId,
		array $ratio
	): int
	{
		$newCategoryId = 0;

		$ratioKey = $this->getDynamicCategoryCode($oldDynamicEntityTypeId, $oldCategoryId);
		if (isset($ratio['CRM_STATUS'][$ratioKey]) && $ratio['CRM_STATUS'][$ratioKey] > 0)
		{
			$newCategoryId = (int)$ratio['CRM_STATUS'][$ratioKey];
		}

		return $newCategoryId;
	}

	public function prepareDynamicTypeReplacementLists(array $dynamicTypesInfo, array $ratioInfo): array
	{
		$result = [
			'from' => [],
			'to' => [],
		];

		foreach ($dynamicTypesInfo as $oldDynamicTypeId => $dynamicTypeInfo)
		{
			$oldDynamicTypeId = (int)$oldDynamicTypeId;
			$oldDynamicEntityTypeId = (int)($dynamicTypeInfo['entityTypeId'] ?? 0);
			if (
				$oldDynamicEntityTypeId > 0
				&& isset($ratioInfo['CRM_DYNAMIC_TYPES'])
			)
			{
				$newDynamicTypeIdRatioKey = "DT$oldDynamicTypeId";
				$newEntityTypeIdRatioKey = CCrmOwnerType::DynamicTypePrefixName . $oldDynamicEntityTypeId;
				if (
					isset($ratioInfo['CRM_DYNAMIC_TYPES'][$newDynamicTypeIdRatioKey])
					&& $ratioInfo['CRM_DYNAMIC_TYPES'][$newDynamicTypeIdRatioKey] > 0
					&& isset($ratioInfo['CRM_DYNAMIC_TYPES'][$newEntityTypeIdRatioKey])
					&& $ratioInfo['CRM_DYNAMIC_TYPES'][$newEntityTypeIdRatioKey] > 0
				)
				{
					$newDynamicTypeId = (int)$ratioInfo['CRM_DYNAMIC_TYPES'][$newDynamicTypeIdRatioKey];
					$newDynamicEntityTypeId = (int)$ratioInfo['CRM_DYNAMIC_TYPES'][$newEntityTypeIdRatioKey];

					// /DYNAMIC_\d+/
					$result['from'][] = CCrmOwnerType::DynamicTypePrefixName . $oldDynamicEntityTypeId;
					$result['to'][] = CCrmOwnerType::DynamicTypePrefixName . $newDynamicEntityTypeId;

					if (
						isset($dynamicTypeInfo['categoryIds'])
						&& is_array($dynamicTypeInfo['categoryIds'])
					)
					{
						foreach ($dynamicTypeInfo['categoryIds'] as $oldCategoryId)
						{
							$oldCategoryId = (int)$oldCategoryId;
							$catgoryRatioKey = $this->getDynamicCategoryCode($oldDynamicEntityTypeId, $oldCategoryId);
							if (
								isset($ratioInfo['CRM_STATUS'][$catgoryRatioKey])
								&& $ratioInfo['CRM_STATUS'][$catgoryRatioKey] > 0
							)
							{
								$newCategoryId = (int)$ratioInfo['CRM_STATUS'][$catgoryRatioKey];

								// /DT\d+_\d+/
								$result['from'][] = $catgoryRatioKey;
								$result['to'][] = $this->getDynamicCategoryCode(
									$newDynamicEntityTypeId,
									$newCategoryId
								);
							}
						}
					}

					$matches = [];
					if (
						isset($dynamicTypeInfo['userFieldEntityId'])
						&& is_string($dynamicTypeInfo['userFieldEntityId'])
						&& isset($dynamicTypeInfo['userFieldNames'])
						&& is_array($dynamicTypeInfo['userFieldNames'])
						&& !empty($dynamicTypeInfo['userFieldNames'])
						&& preg_match('/CRM_(\d+)/u', $dynamicTypeInfo['userFieldEntityId'], $matches)
					)
					{
						$oldDynamicTypeId = $matches[1];
						$oldUserFieldPrefix = "UF_CRM_{$oldDynamicTypeId}_";
						$oldUserFieldPrefixLength = strlen($oldUserFieldPrefix);
						$newUserFieldPrefix = "UF_CRM_{$newDynamicTypeId}_";
						foreach($dynamicTypeInfo['userFieldNames'] as $oldUserFieldName)
						{
							if (substr($oldUserFieldName, 0, $oldUserFieldPrefixLength) === $oldUserFieldPrefix)
							{
								$newUserFieldName =
									$newUserFieldPrefix . substr($oldUserFieldName, $oldUserFieldPrefixLength)
								;

								// /UF_CRM_\d+_/
								$result['from'][] = $oldUserFieldName;
								$result['to'][] = $newUserFieldName;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	public function checkAutomatedSolutionModeExportParams(array $options): bool
	{
		$automatedSolutionModeParams = $this->getAutomatedSolutionModeParams($options);
		if (
			$automatedSolutionModeParams['isAutomatedSolutionMode']
			&& !Container::getInstance()->getUserPermissions()->automatedSolution()->canEdit()
		)
		{
			return false;
		}
		if (
			$automatedSolutionModeParams['isSingleAutomatedSolutionMode']
			&& $automatedSolutionModeParams['customSectionId'] <= 0
		)
		{
			return false;
		}

		return true;
	}

	public function checkAutomatedSolutionModeClearParams(array $params): bool
	{
		$automatedSolutionModeParams = $this->getAutomatedSolutionModeImportParams($params);
		if ($automatedSolutionModeParams['isAutomatedSolutionMode'])
		{
			return false;
		}

		return true;
	}

	public function checkAutomatedSolutionModeImportParams(array $params): bool
	{
		$automatedSolutionModeParams = $this->getAutomatedSolutionModeImportParams($params);
		if (
			$automatedSolutionModeParams['isAutomatedSolutionMode']
			&& !Container::getInstance()->getUserPermissions()->automatedSolution()->canEdit()
		)
		{
			return false;
		}

		return true;
	}

	public function getAutomatedSolutionModeParams(array $params): array
	{
		$manifestCode = $params['MANIFEST']['CODE'] ?? '';
		$isAutomatedSolutionMode = str_starts_with($manifestCode, 'automated_solution');
		$isSingleAutomatedSolutionMode = (($manifestCode) === 'automated_solution_one');
		$customSection = null;
		$customSectionId = 0;
		if (
			$isSingleAutomatedSolutionMode
			&& isset($params['ADDITIONAL_OPTION']['automatedSolutionCode'])
			&& is_string($params['ADDITIONAL_OPTION']['automatedSolutionCode'])
		)
		{
			$automatedSolutionCode = $params['ADDITIONAL_OPTION']['automatedSolutionCode'];
			if ($automatedSolutionCode !== '')
			{
				$customSection = IntranetManager::getCustomSection($automatedSolutionCode);
			}
		}
		if ($isSingleAutomatedSolutionMode && $customSection)
		{
			$customSectionId = (int)$customSection->getId();
		}

		return [
			'isAutomatedSolutionMode' => $isAutomatedSolutionMode,
			'isSingleAutomatedSolutionMode' => $isSingleAutomatedSolutionMode,
			'customSectionId' => $customSectionId,
		];
	}

	public function getAutomatedSolutionModeImportParams(array $params): array
	{
		$manifestCode = $params['IMPORT_MANIFEST']['CODE'] ?? '';
		$isAutomatedSolutionMode = str_starts_with($manifestCode, 'automated_solution');
		$isSingleAutomatedSolutionMode = (($manifestCode) === 'automated_solution_one');

		return [
			'isAutomatedSolutionMode' => $isAutomatedSolutionMode,
			'isSingleAutomatedSolutionMode' => $isSingleAutomatedSolutionMode,
		];
	}

	public function getDynamicTypeCheckExportParamsByEntityTypeId(int $entityTypeId): array
	{
		return [
			'isDynamicType' => $this->isDynamicEntityType($entityTypeId),
			'isDynamicTypeExists' => $this->checkDynamicEntityType($entityTypeId),
			'dynamicTypeCustomSectionId' => $this->getDynamicTypeCustomSectionIdByEntityTypeId($entityTypeId),
		];
	}

	public function checkDynamicTypeRelationWithAutomatedSolutionForImport(
		array $typeFields,
		array $importParams
	): bool
	{
		$helper = new Helper();
		$automatedSolutionModeParams = $helper->getAutomatedSolutionModeImportParams($importParams);
		if ($automatedSolutionModeParams['isAutomatedSolutionMode'])
		{
			if (
				isset($typeFields['CUSTOM_SECTION_ID'])
				&& $typeFields['CUSTOM_SECTION_ID'] > 0
			)
			{
				$automatedSolutionId = $helper->getAutomatedSolutionIdByTypeFieldsForImport($typeFields, $importParams);
				if ($automatedSolutionId <= 0)
				{
					return false;
				}
			}
		}

		return true;
	}

	public function getDynamicTypeCheckImportParamsByTypeFieldsForImport(
		array $typeFields,
		array $importParams
	): array
	{
		$manifestCode = $importParams['IMPORT_MANIFEST']['CODE'] ?? '';
		$isAutomatedSolutionMode = str_starts_with($manifestCode, 'automated_solution');
		$dynamicTypeCustomSectionId = (int)($typeFields['CUSTOM_SECTION_ID'] ?? 0);
		$isAutomatedSolutionRelationChecked = $this->checkDynamicTypeRelationWithAutomatedSolutionForImport(
			$typeFields,
			$importParams
		);

		return [
			'isDynamicType' => $this->isDynamicEntityType((int)($typeFields['ENTITY_TYPE_ID'] ?? 0)),
			'isAutomatedSolutionMode' => $isAutomatedSolutionMode,
			'dynamicTypeCustomSectionId' => $dynamicTypeCustomSectionId,
			'isAutomatedSolutionRelationChecked' => $isAutomatedSolutionRelationChecked,
		];
	}

	public function getDynamicTypeCheckImportParamsByTypeCustomSectionIdForImport(
		int $entityTypeId,
		int $customSectionId,
		array $importParams
	): array
	{
		$manifestCode = $importParams['IMPORT_MANIFEST']['CODE'] ?? '';
		$isAutomatedSolutionMode = str_starts_with($manifestCode, 'automated_solution');
		$isAutomatedSolutionRelationChecked = $this->checkAutomatedSolutionByCustomSectionId($customSectionId);

		return [
			'isDynamicType' => $this->isDynamicEntityType($entityTypeId),
			'isAutomatedSolutionMode' => $isAutomatedSolutionMode,
			'dynamicTypeCustomSectionId' => $customSectionId,
			'isAutomatedSolutionRelationChecked' => $isAutomatedSolutionRelationChecked,
		];
	}

	public function checkDynamicTypeExportConditions(array $params): bool
	{
		$isAutomatedSolutionMode = (bool)($params['isAutomatedSolutionMode'] ?? false);
		$isSingleAutomatedSolutionMode = (bool)($params['isSingleAutomatedSolutionMode'] ?? false);
		$customSectionId = (int)($params['customSectionId'] ?? 0);
		$isDynamicType = (bool)($params['isDynamicType'] ?? false);
		$isDynamicTypeExists = (bool)($params['isDynamicTypeExists'] ?? false);
		$dynamicTypeCustomSectionId = (int)($params['dynamicTypeCustomSectionId'] ?? 0);

		if ($isDynamicType)
		{
			if (!$isDynamicTypeExists)
			{
				return false;
			}

			if ($dynamicTypeCustomSectionId > 0)
			{
				if ($isAutomatedSolutionMode)
				{
					if ($isSingleAutomatedSolutionMode && $customSectionId !== $dynamicTypeCustomSectionId)
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			else
			{
				if ($isAutomatedSolutionMode)
				{
					return false;
				}
			}
		}
		else
		{
			if ($isAutomatedSolutionMode)
			{
				return false;
			}
		}

		return true;
	}

	public function checkDynamicTypeImportConditionsByParams(array $params): bool
	{
		$isAutomatedSolutionMode = (bool)($params['isAutomatedSolutionMode'] ?? false);
		$isDynamicType = (bool)($params['isDynamicType'] ?? false);
		$dynamicTypeCustomSectionId = (int)($params['dynamicTypeCustomSectionId'] ?? 0);
		$isAutomatedSolutionRelationChecked = (bool)($params['isAutomatedSolutionRelationChecked'] ?? false);

		if ($isDynamicType)
		{
			if ($dynamicTypeCustomSectionId > 0)
			{
				if ($isAutomatedSolutionMode)
				{
					if (!$isAutomatedSolutionRelationChecked)
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			else
			{
				if ($isAutomatedSolutionMode)
				{
					return false;
				}
			}
		}
		else
		{
			if ($isAutomatedSolutionMode)
			{
				return false;
			}
		}

		return true;
	}

	function checkDynamicTypeImportConditions(int $dynamicEntityTypeId, array $importData): bool
	{
		$helper = new Helper();
		$automatedSolutionModeParams = static::getAutomatedSolutionModeImportParams($importData);
		$dynamicType = Container::getInstance()->getTypeByEntityTypeId($dynamicEntityTypeId);
		if (
			$dynamicType
			&& $helper->checkDynamicTypeImportConditionsByParams(
				array_merge(
					$automatedSolutionModeParams,
					$helper->getDynamicTypeCheckImportParamsByTypeCustomSectionIdForImport(
						$dynamicEntityTypeId,
						($dynamicType->getCustomSectionId() ?? 0),
						$importData
					)
				)
			)
		)
		{
			return true;
		}

		return false;
	}

	public function isDynamicEntityType(int $entityTypeId): bool
	{
		if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return true;
		}

		return false;
	}

	public function checkDynamicEntityType(int $entityTypeId): bool
	{
		$type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		if ($type)
		{
			return true;
		}

		return false;
	}

	public function getDynamicEntityTypeIdByEntityTypeName(string $entityTypeName): int
	{
		$entityTypeId = 0;
		$dynamicTypePrefix = CCrmOwnerType::DynamicTypePrefixName;
		$dynamicTypeRegExp = "/$dynamicTypePrefix(\\d+)/u";
		$matches = [];

		if (preg_match($dynamicTypeRegExp, $entityTypeName, $matches))
		{
			$entityTypeId = (int)$matches[1];
		}

		return $entityTypeId;
	}

	public function getDynamicTypeCustomSectionIdByEntityTypeId(int $entityTypeId): int
	{
		if ($this->isDynamicEntityType($entityTypeId))
		{
			$type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
			if ($type)
			{
				$customSectionId = $type->getCustomSectionId();
				if ($customSectionId > 0)
				{
					return $customSectionId;
				}
			}
		}

		return 0;
	}

	public function checkAutomatedSolutionByCustomSectionId(int $customSectionId): bool
	{
		foreach (
			Container::getInstance()
				->getAutomatedSolutionManager()
				->getExistingIntranetCustomSections()
			as $customSection
		)
		{
			if ($customSectionId === $customSection->getId())
			{
				return true;
			}
		}

		return false;
	}

	public function getCustomSectionIdByTypeFieldsForImport(array $typeFields, array $importParams): int
	{
		$result = 0;

		$customSectionId = (int)($typeFields['CUSTOM_SECTION_ID'] ?? 0);
		if (
			$customSectionId > 0
			&& isset($importParams['RATIO']['AUTOMATED_SOLUTION']["CS$customSectionId"])
		)
		{
			$customSectionId = (int)$importParams['RATIO']['AUTOMATED_SOLUTION']["CS$customSectionId"];
			if ($this->checkAutomatedSolutionByCustomSectionId($customSectionId))
			{
				$result = $customSectionId;
			}
		}

		return $result;
	}

	public function getAutomatedSolutionIdByTypeFieldsForImport(array $typeFields, array $importParams): int
	{
		$result = 0;

		$customSectionId = $this->getCustomSectionIdByTypeFieldsForImport($typeFields, $importParams);
		if ($customSectionId > 0)
		{
			foreach (
				Container::getInstance()->getAutomatedSolutionManager()->getExistingAutomatedSolutions() as $fields
			)
			{
				if (
					isset($fields['INTRANET_CUSTOM_SECTION_ID'])
					&& $customSectionId === (int)$fields['INTRANET_CUSTOM_SECTION_ID']
				)
				{
					$result = (int)$fields['ID'];
					break;
				}
			}
		}

		return $result;
	}
}
