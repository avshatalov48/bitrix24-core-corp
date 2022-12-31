<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder;

use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use Bitrix\Crm\Security\Controller\QueryBuilder;
use Bitrix\Crm\Security\QueryBuilder\Options;

class ControllerBased extends QueryBuilder
{
	/** @var string */
	protected static $userRegex = '/^U(\d+)$/i';
	/** @var string */
	protected static $departmentRegex = '/^D(\d+)$/i';
	protected $controller;

	public function __construct(\Bitrix\Crm\Security\Controller\Base $controller)
	{
		$this->controller = $controller;
	}

	public function build(
		Collection $attributes,
		Options $options
	): string
	{
		$restrictionMap = $this->getRestrictionsByAttributes($attributes, $options);
		$dataSourceTable = $this->controller->getTableName();
		$prefix = $options->getAliasPrefix();
		$finalSqlConditions = [];
		$unRestrictedEntityTypes = [];
		$isEntityWithCategories = $this->controller->hasCategories();

		foreach ($restrictionMap as $restriction)
		{
			$entityTypes = isset($restriction['ENTITY_TYPES']) ? $restriction['ENTITY_TYPES'] : [];
			$categoryIds = isset($restriction['CATEGORY_ID']) ? $restriction['CATEGORY_ID'] : [];
			$categoriesCount = $isEntityWithCategories ? count($categoryIds) : 0;

			$progressSqlCondition = '';
			$categoryIdSqlCondition = '';
			if ($categoriesCount > 1)
			{
				$slug = implode(",", $categoryIds);
				$categoryIdSqlCondition = "{$prefix}P.CATEGORY_ID IN ({$slug})";
			}
			elseif ($categoriesCount === 1)
			{
				$categoryId = $categoryIds[0];
				$categoryIdSqlCondition = "{$prefix}P.CATEGORY_ID = {$categoryId}";

				$progressSqlCondition = $this->getProgressSqlCondition($restriction['PROGRESS_STEPS'] ?? [], $prefix);
			}
			elseif (!$isEntityWithCategories)
			{
				$progressSqlCondition = $this->getProgressSqlCondition($restriction['PROGRESS_STEPS'] ?? [], $prefix);
			}
			$categoryIdSqlConditionWithAnd = $categoryIdSqlCondition === '' ? '' : "{$categoryIdSqlCondition} AND ";

			$isOpened = isset($restriction['OPENED']) && $restriction['OPENED'];
			$userIDs = isset($restriction['USER_IDS']) ? $restriction['USER_IDS'] : [];

			$hasOnlyCategoryCondition = false;
			if (!$isOpened && empty($userIDs) && $progressSqlCondition === '')
			{
				if ($categoryIdSqlCondition !== '')
				{
					$finalSqlConditions[] = $categoryIdSqlCondition;
					$hasOnlyCategoryCondition = true;
				}
				$unRestrictedEntityTypes = array_merge($unRestrictedEntityTypes, $entityTypes);
			}
			else
			{
				$isProcessed = false;

				if ($isOpened)
				{
					$baseSqlCondition = "{$categoryIdSqlConditionWithAnd}{$prefix}P.IS_OPENED = 'Y'";
					$finalSqlConditions[] = $progressSqlCondition === ''
						? "({$baseSqlCondition})" : "({$baseSqlCondition} AND {$progressSqlCondition})";

					$isProcessed = true;
				}

				if (!empty($userIDs))
				{
					if (count($userIDs) === 1)
					{
						$baseSqlCondition = "{$categoryIdSqlConditionWithAnd}{$prefix}P.USER_ID = {$userIDs[0]}";
					}
					else
					{
						$slug = implode(',', $userIDs);
						$baseSqlCondition = "{$categoryIdSqlConditionWithAnd}{$prefix}P.USER_ID IN ({$slug})";
					}
					$finalSqlConditions[] = $progressSqlCondition === ''
						? "({$baseSqlCondition})" : "({$baseSqlCondition} AND {$progressSqlCondition})";

					$isProcessed = true;
				}

				if (!$isProcessed)
				{
					$finalSqlConditions[] = "({$categoryIdSqlConditionWithAnd}{$progressSqlCondition})";
				}
			}
		}

		// / Leave for the backward compatibility. Observer access logic moved to the access_attrs table.
		if (\Bitrix\Main\Config\Option::get('crm', 'CRM_MOVE_OBSERVERS_TO_ACCESS_ATTR_IN_WORK', 'Y')  === 'Y')
		{
			$finalSqlConditions = array_merge(
				$finalSqlConditions,
				$this->buildObserverSqlCondition(
					$attributes->getUserId(),
					array_diff($attributes->getAllowedEntityTypes(), $unRestrictedEntityTypes),
					$prefix
				)
			);
		}

		$finalSqlConditions = array_filter($finalSqlConditions);

		if (empty($finalSqlConditions))
		{
			return '';
		}

		if ($options->isReadAllAllowed() && !$hasOnlyCategoryCondition)
		{
			$finalSqlConditions[] = "({$categoryIdSqlConditionWithAnd}{$prefix}P.IS_ALWAYS_READABLE = 'Y')";
		}

		$querySqlCondition = implode(' OR ', $finalSqlConditions);
		if ($options->needReturnRawQuery())
		{
			$distinct = $options->isUseRawQueryDistinct() ? 'DISTINCT ' : '';
			$querySql = "SELECT {$distinct}{$prefix}P.ENTITY_ID FROM {$dataSourceTable} {$prefix}P WHERE {$querySqlCondition}";
			if ($options->getRawQueryLimit() > 0)
			{
				$order = $options->getRawQueryOrder();

				$querySql = Application::getConnection()->getSqlHelper()->getTopSql(
					$querySql . " ORDER BY ENTITY_ID {$order}",
					$options->getRawQueryLimit()
				)
				;
			}

			return $querySql;
		}

		$identity = $options->getIdentityColumnName();

		if ($options->needUseJoin())
		{
			return "INNER JOIN {$dataSourceTable} {$prefix}P ON {$prefix}.{$identity} = {$prefix}P.ENTITY_ID AND ({$querySqlCondition})";
		}

		return "{$prefix}.{$identity} IN (SELECT {$prefix}P.ENTITY_ID FROM {$dataSourceTable} {$prefix}P WHERE {$querySqlCondition})";
	}

	protected function getRestrictionsByAttributes(Collection $attributesCollection, Options $options): array
	{
		$restrictionData = [];
		$userDepartmentIDs = $this->getUserDepartmentIDs($attributesCollection->getUserId());

		$permissionEntityTypes = $attributesCollection->getAllowedEntityTypes();

		foreach ($permissionEntityTypes as $permissionEntityType)
		{
			$entityAttributes = $attributesCollection->getByEntityType($permissionEntityType);
			if (empty($entityAttributes))
			{
				continue;
			}

			$permissionSets = [];
			foreach ($entityAttributes as $attributes)
			{
				if (empty($attributes))
				{
					continue;
				}

				$permissionSet = [
					'USER_ID' => 0,
					'DEPARTMENT_IDS' => [],
					'PROGRESS_STEPS' => [],
					'OPENED' => false,
				];
				for ($i = 0, $length = count($attributes); $i < $length; $i++)
				{
					$attributeValue = $attributes[$i];

					$parsedAttributeValue = '';

					if (
						$this->controller->hasProgressSteps()
						&& $this->controller->tryParseProgressStep($attributeValue, $parsedAttributeValue)
						&& $parsedAttributeValue != ''
					)
					{
						$permissionSet['PROGRESS_STEPS'][] = $parsedAttributeValue;
					}
					elseif ($attributeValue === 'O')
					{
						$permissionSet['OPENED'] = true;
					}
					elseif ($this->tryParseUser($attributeValue, $parsedAttributeValue) && $parsedAttributeValue > 0)
					{
						$permissionSet['USER_ID'] = (int)$parsedAttributeValue;
					}
					elseif ($this->tryParseDepartment($attributeValue, $parsedAttributeValue) && $parsedAttributeValue > 0)
					{
						$permissionSet['DEPARTMENT_IDS'][] = (int)$parsedAttributeValue;
					}
				}

				$permissionSets[] = $permissionSet;
				if ($permissionSet['OPENED']) // if opened are allowed, also my and my department are allowed
				{
					$permissionSets[] = [
						'USER_ID' => $attributesCollection->getUserId(),
						'DEPARTMENT_IDS' => [],
						'PROGRESS_STEPS' => $permissionSet['PROGRESS_STEPS'],
						'OPENED' => false,
					];

					if (!empty($userDepartmentIDs))
					{
						$permissionSets[] = [
							'USER_ID' => 0,
							'DEPARTMENT_IDS' => $userDepartmentIDs,
							'PROGRESS_STEPS' => $permissionSet['PROGRESS_STEPS'],
							'OPENED' => false,
						];
					}
				}
			}

			$permissionFurl = [];
			foreach ($permissionSets as $permissionSet)
			{
				$userID = $permissionSet['USER_ID'];
				$departmentIDs = $permissionSet['DEPARTMENT_IDS'];
				$isOpened = $permissionSet['OPENED'];
				if (!empty($departmentIDs))
				{
					sort($departmentIDs, SORT_NUMERIC);
				}
				$hash = md5(
					'U:' . $userID
					. 'D:' . (!empty($departmentIDs) ? implode(',', $departmentIDs) : '-')
					. 'O:' . ($isOpened ? 'Y' : 'N'));

				if (!isset($permissionFurl[$hash]))
				{
					$permissionFurl[$hash] = $permissionSet;
				}
				elseif (!empty($permissionSet['PROGRESS_STEPS']))
				{
					$permissionFurl[$hash]['PROGRESS_STEPS'] = array_merge(
						$permissionFurl[$hash]['PROGRESS_STEPS'],
						array_diff(
							$permissionSet['PROGRESS_STEPS'],
							$permissionFurl[$hash]['PROGRESS_STEPS']
						)
					);
				}
			}
			$permissionSets = array_values($permissionFurl);

			$restrictionData[$permissionEntityType] = [];
			foreach ($permissionSets as $permissionSet)
			{
				$hash = '-';
				$progressSteps = $permissionSet['PROGRESS_STEPS'];
				if (!empty($progressSteps))
				{
					sort($progressSteps, SORT_STRING);
					$hash = md5(implode(',', $permissionSet['PROGRESS_STEPS']));
				}

				if (!isset($restrictionData[$permissionEntityType][$hash]))
				{
					$restriction = ['PROGRESS_STEPS' => $progressSteps];
				}
				else
				{
					$restriction = $restrictionData[$permissionEntityType][$hash];
				}

				if ($permissionSet['OPENED'])
				{
					$restriction['OPENED'] = true;
				}

				$userID = $permissionSet['USER_ID'];
				if ($userID > 0)
				{
					if (!isset($restriction['USER_IDS']))
					{
						$restriction['USER_IDS'] = [];
					}
					if (!in_array($userID, $restriction['USER_IDS'], true))
					{
						$restriction['USER_IDS'][] = $userID;
					}
				}

				if (!empty($permissionSet['DEPARTMENT_IDS']))
				{
					if (!isset($restriction['USER_IDS']))
					{
						$restriction['USER_IDS'] = [];
					}
					$restriction['USER_IDS'] = array_unique(
						array_merge(
							$restriction['USER_IDS'],
							$this->getDepartmentsUsers($permissionSet['DEPARTMENT_IDS'])
						)
					);
				}
				$restrictionData[$permissionEntityType][$hash] = $restriction;
			}
		}

		$canSkipCategoryRestrictions = false;
		if ($options->canSkipCheckOtherEntityTypes())
		{
			$canSkipCategoryRestrictions = $attributesCollection->areAllEntityTypesAllowed();
			if ($canSkipCategoryRestrictions)
			{
				foreach ($restrictionData as $restrictions)
				{
					if (!empty($restrictions))
					{
						$canSkipCategoryRestrictions = false;
						break;
					}
				}
			}
		}

		$restrictionMap = [];
		foreach ($restrictionData as $permissionEntityType => $restrictions)
		{
			if (empty($restrictions))
			{
				if (!isset($restrictionMap['-']))
				{
					$restrictionMap['-'] = [];
				}
				$this->addTypeAndCategoryToRestrictionMap(
					$restrictionMap['-'],
					$permissionEntityType,
					$canSkipCategoryRestrictions
				);

				continue;
			}

			$allProgressSteps = $this->controller->hasProgressSteps()
				? $this->controller->getProgressSteps($permissionEntityType) : [];

			if (!empty($allProgressSteps))
			{
				sort($allProgressSteps, SORT_STRING);
			}

			foreach ($restrictions as $restriction)
			{
				$isProcessed = false;

				$progressSteps = isset($restriction['PROGRESS_STEPS']) ? $restriction['PROGRESS_STEPS'] : [];
				if (!empty($progressSteps))
				{
					sort($progressSteps, SORT_STRING);
					if (empty(array_diff($allProgressSteps, $progressSteps)))
					{
						$progressSteps = [];
					}
				}

				$userIDs = isset($restriction['USER_IDS']) ? $restriction['USER_IDS'] : [];
				if (!empty($userIDs))
				{
					sort($userIDs, SORT_NUMERIC);

					$hash = md5(
						(!empty($progressSteps) ? $permissionEntityType . ':' . implode(',', $progressSteps) : '-')
						. 'U:' . (!empty($userIDs) ? implode(',', $userIDs) : '-')
					);

					if (!isset($restrictionMap[$hash]))
					{
						$restrictionMap[$hash] = [
							'PROGRESS_STEPS' => $progressSteps,
							'USER_IDS' => $userIDs,
						];
					}
					$this->addTypeAndCategoryToRestrictionMap(
						$restrictionMap[$hash],
						$permissionEntityType
					);

					$isProcessed = true;
				}

				$isOpened = isset($restriction['OPENED']) && $restriction['OPENED'];
				if ($isOpened)
				{
					$hash = md5(
						(!empty($progressSteps) ? $permissionEntityType . ':' . implode(',', $progressSteps) : '-')
						. 'O:' . ($isOpened ? 'Y' : 'N')
					);

					if (!isset($restrictionMap[$hash]))
					{
						$restrictionMap[$hash] = [
							'PROGRESS_STEPS' => $progressSteps,
							'OPENED' => true,
						];
					}
					$this->addTypeAndCategoryToRestrictionMap(
						$restrictionMap[$hash],
						$permissionEntityType
					);

					$isProcessed = true;
				}

				if (!$isProcessed)
				{
					$hash = md5(
						!empty($progressSteps) ? $permissionEntityType . ':' . implode(',', $progressSteps) : '-'
					);

					if (!isset($restrictionMap[$hash]))
					{
						$restrictionMap[$hash] = [
							'PROGRESS_STEPS' => $progressSteps,
						];
					}
					$this->addTypeAndCategoryToRestrictionMap(
						$restrictionMap[$hash],
						$permissionEntityType
					);
				}
			}
		}

		return $restrictionMap;
	}

	protected function tryParseAttributeValue($attribute, $regex, &$value): bool
	{
		if (preg_match($regex, $attribute, $m) !== 1)
		{
			return false;
		}

		$value = isset($m[1]) ? $m[1] : '';

		return true;
	}

	protected function tryParseUser($attribute, &$value): bool
	{
		return $this->tryParseAttributeValue($attribute, self::$userRegex, $value);
	}

	protected function tryParseDepartment($attribute, &$value): bool
	{
		return $this->tryParseAttributeValue($attribute, self::$departmentRegex, $value);
	}

	protected function getUserDepartmentIDs(int $userId): array
	{
		static $userDepartmentIDs = [];

		if (isset($userDepartmentIDs[$userId]))
		{
			return $userDepartmentIDs[$userId];
		}

		$allUserAttrs = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->getAttributesProvider()
			->getUserAttributes()
		;

		$userDepartmentIDs[$userId] = [];

		$intranetAttrs = array_merge(
			isset($allUserAttrs['INTRANET']) ? $allUserAttrs['INTRANET'] : [],
			isset($allUserAttrs['SUBINTRANET']) ? $allUserAttrs['SUBINTRANET'] : []
		);

		foreach ($intranetAttrs as $attr)
		{
			if ($this->tryParseDepartment($attr, $value) && $value > 0)
			{
				$userDepartmentIDs[$userId][] = (int)$value;
			}
		}

		return $userDepartmentIDs[$userId];
	}

	protected function getDepartmentsUsers(array $departmentIds): array
	{
		static $users = [];

		if (empty($departmentIds))
		{
			return [];
		}

		$cacheKey = md5(implode(',', $departmentIds));

		if (!isset($users[$cacheKey]))
		{

			$dbResult = UserTable::getList([
				'filter' => [
					'@UF_DEPARTMENT' => $departmentIds,
				],
				'select' => [
					'ID',
				],
			]);

			$userIds = [];
			while ($userFields = $dbResult->fetch())
			{
				$userIds[] = (int)$userFields['ID'];
			}
			$departments = \CIBlockSection::GetList(
				[],
				[
					'IBLOCK_ID' => \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', 0),
					'ID' => $departmentIds,
					'CHECK_PERMISSIONS' => 'N',
				],
				false,
				[
					'ID',
					'UF_HEAD',
				]
			);
			while ($departmentFields = $departments->fetch())
			{
				if ($departmentFields['UF_HEAD'])
				{
					$userIds[] = (int)$departmentFields['UF_HEAD'];
				}
			}

			$users[$cacheKey] = array_unique($userIds);
		}

		return $users[$cacheKey];
	}

	private function addTypeAndCategoryToRestrictionMap(
		array &$restrictionMap,
		string $permissionEntityType,
		bool $canSkipCategoryRestrictions = false
	)
	{
		if (!isset($restrictionMap['ENTITY_TYPES']))
		{
			$restrictionMap['ENTITY_TYPES'] = [];
		}
		$restrictionMap['ENTITY_TYPES'][] = $permissionEntityType;

		if (!isset($restrictionMap['CATEGORY_ID']))
		{
			$restrictionMap['CATEGORY_ID'] = [];
		}

		if ($this->controller->hasCategories() && !$canSkipCategoryRestrictions)
		{
			$restrictionMap['CATEGORY_ID'][] = $this->controller->extractCategoryId($permissionEntityType);
		}
	}

	protected function buildObserverSqlCondition(int $userId, array $permissionEntityTypes, $prefix): array
	{
		if (empty($permissionEntityTypes))
		{
			return [];
		}

		$categoryIdMap = [];
		$hasCategories = null;
		foreach ($permissionEntityTypes as $permissionEntityType)
		{
			if (!$this->controller->isObservable())
			{
				continue;
			}
			if ($hasCategories !== null && $hasCategories !== $this->controller->hasCategories())
			{
				throw new \Bitrix\Main\NotSupportedException('Several data sources for observers are not supported');
			}
			$hasCategories = $this->controller->hasCategories();

			$entityTypeID = $this->controller->getEntityTypeId();
			if (!isset($categoryIdMap[$entityTypeID]))
			{
				$categoryIdMap[$entityTypeID] = [];
			}
			$categoryIdMap[$entityTypeID][] =
				$hasCategories
					? $this->controller->extractCategoryId($permissionEntityType)
					: 0;
		}

		$sqlConditions = [];

		foreach ($categoryIdMap as $entityTypeID => $categoryIds)
		{
			$categoryIds = array_unique($categoryIds);

			if (!$hasCategories)
			{
				$sqlConditions[] = "({$prefix}P.ENTITY_ID IN (SELECT ENTITY_ID FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$entityTypeID} AND USER_ID = {$userId}))";
			}
			elseif (count($categoryIds) === 1)
			{
				$sqlConditions[] = "({$prefix}P.CATEGORY_ID = {$categoryIds[0]} AND {$prefix}P.ENTITY_ID IN (SELECT ENTITY_ID FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$entityTypeID} AND USER_ID = {$userId}))";
			}
			else
			{
				$slug = implode(",", $categoryIds);
				$sqlConditions[] = "({$prefix}P.CATEGORY_ID IN ({$slug}) AND {$prefix}P.ENTITY_ID IN (SELECT ENTITY_ID FROM b_crm_observer WHERE ENTITY_TYPE_ID = {$entityTypeID} AND USER_ID = {$userId}))";
			}
		}

		return $sqlConditions;
	}

	protected function getProgressSqlCondition($steps, string $prefix)
	{
		$progressSqlCondition = '';

		if (is_array($steps) && !empty($steps))
		{
			if (count($steps) === 1)
			{
				$progressSqlCondition = "{$prefix}P.PROGRESS_STEP = '{$steps[0]}'";
			}
			else
			{
				$slug = implode("','", $steps);
				$progressSqlCondition = "{$prefix}P.PROGRESS_STEP IN ('{$slug}')";
			}
		}

		return $progressSqlCondition;
	}
}
