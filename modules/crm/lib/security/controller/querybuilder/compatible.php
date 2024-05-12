<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder;

use Bitrix\Crm\Item;
use Bitrix\Crm\Security\AccessAttribute\Collection;
use Bitrix\Crm\Security\Controller\QueryBuilder;
use Bitrix\Crm\Security\QueryBuilder\QueryBuilderOptions;
use Bitrix\Crm\Security\QueryBuilder\Result\InConditionResult;
use Bitrix\Crm\Security\QueryBuilder\Result\JoinResult;
use Bitrix\Crm\Security\QueryBuilder\Result\RawQueryResult;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentException;
use CCrmOwnerType;
use CCrmPerms;

/**
 * @deprecated
 */
class Compatible extends QueryBuilder
{

	public function build(Collection $attributes, QueryBuilderOptions $options): QueryBuilderData
	{
		$resultType = $options->getResult();
		$permissionEntityTypes = $attributes->getAllowedEntityTypes();
		$total = count($permissionEntityTypes);
		if ($total === 0)
		{
			throw new ArgumentException('Permission entity types can not be empty');
		}
		if ($total === 1)
		{
			return new QueryBuilderData(
				$this->buildForEntity($attributes, $options, array_pop($permissionEntityTypes))
			);
		}

		$restrictedQueries = [];
		$unrestrictedQueries = [];
		$subQueryOptions = clone $options;

		$isUseJoin = $resultType instanceof JoinResult;

		$aliasPrefix = $subQueryOptions->getAliasPrefix();
		$effectiveEntityIDs = $subQueryOptions->getLimitByIds();

		foreach ($permissionEntityTypes as $permissionEntityType)
		{
			$sql = $this->buildForEntity($attributes, $subQueryOptions, $permissionEntityType, true);
			if ($sql === '')
			{
				$subQuery = "SELECT {$aliasPrefix}P.ENTITY_ID FROM b_crm_entity_perms {$aliasPrefix}P WHERE {$aliasPrefix}P.ENTITY = '{$permissionEntityType}'";
				if (!empty($effectiveEntityIDs))
				{
					$subQuery .= " AND {$aliasPrefix}P.ENTITY_ID IN (" . implode(', ', $effectiveEntityIDs) . ")";
				}
				if ($isUseJoin)
				{
					$subQuery .= " GROUP BY {$aliasPrefix}P.ENTITY_ID";
				}

				$unrestrictedQueries[] = $subQuery;
			}
			else
			{
				$restrictedQueries[] = $sql;
			}
		}

		if (empty($restrictedQueries))
		{
			return new QueryBuilderData('');
		}
		$queries = array_merge($unrestrictedQueries, $restrictedQueries);

		$querySql = implode($options->needUseDistinctUnion() ? ' UNION ' : ' UNION ALL ', $queries);

		return new QueryBuilderData($resultType->makeCompatible($querySql, $aliasPrefix));
	}

	protected function buildForEntity(
		Collection $attributes,
		QueryBuilderOptions $options,
		string $permEntity,
		bool $forceRawQueryResult = false
	): string
	{
		$entityListAttributes = $attributes->getByEntityType($permEntity);
		$scopeRegex = $this->getScopeRegexForEntity($permEntity);

		$enableCumulativeMode = \COption::GetOptionString('crm', 'enable_permission_cumulative_mode', 'Y') === 'Y';
		$userAccessAttributes = Container::getInstance()
			->getUserPermissions($attributes->getUserId())
			->getAttributesProvider()
			->getUserAttributes()
		;

		$intranetAttrs = [];
		$allIntranetAttrs =
			isset($userAccessAttributes['INTRANET']) && is_array($userAccessAttributes['INTRANET'])
				? $userAccessAttributes['INTRANET']
				: [];

		if (!empty($allIntranetAttrs))
		{
			foreach ($allIntranetAttrs as $attr)
			{
				if (preg_match('/^D\d+$/', $attr))
				{
					$intranetAttrs[] = "'{$attr}'";
				}
			}
		}

		$subIntranetAttrs = [];
		$allSubIntranetAttrs =
			isset($userAccessAttributes['SUBINTRANET']) && is_array($userAccessAttributes['SUBINTRANET'])
				? $userAccessAttributes['SUBINTRANET']
				: [];

		if (!empty($allSubIntranetAttrs))
		{
			foreach ($allSubIntranetAttrs as $attr)
			{
				if (preg_match('/^D\d+$/', $attr))
				{
					$subIntranetAttrs[] = "'{$attr}'";
				}
			}
		}

		$permissionSets = [];
		foreach ($entityListAttributes as &$attrs)
		{
			if (empty($attrs))
			{
				continue;
			}

			$permissionSet = [
				'USER' => '',
				'CONCERNED_USER' => '',
				'DEPARTMENTS' => [],
				'OPENED_ONLY' => '',
				'SCOPES' => [],
			];

			$qty = count($attrs);
			for ($i = 0; $i < $qty; $i++)
			{
				$attr = $attrs[$i];

				if ($scopeRegex !== '' && preg_match($scopeRegex, $attr))
				{
					$permissionSet['SCOPES'][] = "'{$attr}'";
				}
				elseif ($attr === 'O')
				{
					$permissionSet['OPENED_ONLY'] = "'{$attr}'";
				}
				elseif (preg_match('/^U\d+$/', $attr))
				{
					$permissionSet['USER'] = "'{$attr}'";
					$permissionSet['CONCERNED_USER'] = "'C{$attr}'";
				}
				elseif (preg_match('/^D\d+$/', $attr))
				{
					$permissionSet['DEPARTMENTS'][] = "'{$attr}'";
				}
			}

			if (empty($permissionSet['SCOPES']))
			{
				if ($permissionSet['OPENED_ONLY'] !== '')
				{
					//HACK: for OPENED ONLY mode - allow user own entities too.
					$userAttr = isset($userAccessAttributes['USER']) && is_array($userAccessAttributes['USER']) && !empty($userAccessAttributes['USER']) ? $userAccessAttributes['USER'][0] : '';
					if ($userAttr !== '')
					{
						$permissionSets[] = [
							'USER' => "'{$userAttr}'",
							'CONCERNED_USER' => "'C{$userAttr}'",
							'DEPARTMENTS' => [],
							'OPENED_ONLY' => '',
							'SCOPES' => [],
						];
					}

					if ($enableCumulativeMode && !empty($intranetAttrs))
					{
						//OPENED ONLY mode - allow user department entities too.
						$permissionSets[] = [
							'USER' => '',
							'CONCERNED_USER' => '',
							'DEPARTMENTS' => array_unique(array_merge($intranetAttrs, $subIntranetAttrs)),
							'OPENED_ONLY' => '',
							'SCOPES' => [],
						];
					}
				}

				$permissionSets[] = &$permissionSet;
				unset($permissionSet);
			}
			else
			{
				$permissionSet = $this->registerPermissionSet($permissionSets, $permissionSet);
				if ($permissionSet['OPENED_ONLY'] !== '')
				{
					//HACK: for OPENED ONLY mode - allow user own entities too.
					$userAttr = isset($userAccessAttributes['USER']) && is_array($userAccessAttributes['USER']) && !empty($userAccessAttributes['USER']) ? $userAccessAttributes['USER'][0] : '';
					if ($userAttr !== '')
					{
						$this->registerPermissionSet(
							$permissionSets,
							[
								'USER' => "'{$userAttr}'",
								'CONCERNED_USER' => "'C{$userAttr}'",
								'DEPARTMENTS' => [],
								'OPENED_ONLY' => '',
								'SCOPES' => $permissionSet['SCOPES'],
							]
						);
					}
				}
			}
		}
		unset($attrs);

		$isRestricted = false;
		$subQueries = [];

		$effectiveEntityIDs = $options->getLimitByIds();
		$aliasPrefix = $options->getAliasPrefix();

		foreach ($permissionSets as $permissionSet)
		{
			$scopes = $permissionSet['SCOPES'];
			$scopeQty = count($scopes);
			if ($scopeQty === 0)
			{
				$restrictions = [];
				if ($permissionSet['OPENED_ONLY'] !== '')
				{
					$attr = $permissionSet['OPENED_ONLY'];
					$restrictions[] = "{$aliasPrefix}P.ATTR = {$attr}";
				}
				elseif ($permissionSet['USER'] !== '')
				{
					$restrictions[] = $aliasPrefix . 'P.ATTR = ' . $permissionSet['USER'];
					if ($permissionSet['CONCERNED_USER'] !== '')
					{
						$restrictions[] = $aliasPrefix . 'P.ATTR = ' . $permissionSet['CONCERNED_USER'];
					}
				}
				elseif (!empty($permissionSet['DEPARTMENTS']))
				{
					$departments = $permissionSet['DEPARTMENTS'];
					$restrictions[] = count($departments) > 1
						? $aliasPrefix . 'P.ATTR IN(' . implode(', ', $departments) . ')'
						: $aliasPrefix . 'P.ATTR = ' . $departments[0];
				}

				if (!empty($restrictions))
				{
					foreach ($restrictions as $restriction)
					{
						$subQuery = "SELECT {$aliasPrefix}P.ENTITY_ID FROM b_crm_entity_perms {$aliasPrefix}P WHERE {$aliasPrefix}P.ENTITY = '{$permEntity}' AND {$restriction}";
						if (!empty($effectiveEntityIDs))
						{
							$subQuery .= " AND {$aliasPrefix}P.ENTITY_ID IN (" . implode(', ', $effectiveEntityIDs) . ")";
						}
						$subQueries[] = $subQuery;
					}

					if (!$isRestricted)
					{
						$isRestricted = true;
					}
				}
			}
			else
			{
				$scopeSql = $scopeQty > 1
					? $aliasPrefix . 'P2.ATTR IN (' . implode(', ', $scopes) . ')'
					: $aliasPrefix . 'P2.ATTR = ' . $scopes[0];

				$restrictions = [];
				if ($permissionSet['OPENED_ONLY'] !== '')
				{
					$attr = $permissionSet['OPENED_ONLY'];
					$restrictions[] = "{$aliasPrefix}P1.ATTR = {$attr}";
				}
				elseif ($permissionSet['USER'] !== '')
				{
					$restrictions[] = $aliasPrefix . 'P1.ATTR = ' . $permissionSet['USER'];
					if ($permissionSet['CONCERNED_USER'] !== '')
					{
						$restrictions[] = $aliasPrefix . 'P1.ATTR = ' . $permissionSet['CONCERNED_USER'];
					}
				}
				elseif (!empty($permissionSet['DEPARTMENTS']))
				{
					$departments = $permissionSet['DEPARTMENTS'];
					$restrictions[] = count($departments) > 1
						? $aliasPrefix . 'P1.ATTR IN(' . implode(', ', $departments) . ')'
						: $aliasPrefix . 'P1.ATTR = ' . $departments[0];
				}

				if (!empty($restrictions))
				{
					foreach ($restrictions as $restriction)
					{
						$subQuery = "SELECT {$aliasPrefix}P2.ENTITY_ID FROM b_crm_entity_perms {$aliasPrefix}P1 INNER JOIN b_crm_entity_perms {$aliasPrefix}P2 ON {$aliasPrefix}P1.ENTITY = '{$permEntity}' AND {$aliasPrefix}P2.ENTITY = '{$permEntity}' AND {$aliasPrefix}P1.ENTITY_ID = {$aliasPrefix}P2.ENTITY_ID AND {$restriction} AND {$scopeSql}";
						if (!empty($effectiveEntityIDs))
						{
							$subQuery .= " AND {$aliasPrefix}P2.ENTITY_ID IN (" . implode(',', $effectiveEntityIDs) . ")";
						}
						$subQueries[] = $subQuery;
					}
				}
				else
				{
					$subQuery = "SELECT {$aliasPrefix}P2.ENTITY_ID FROM b_crm_entity_perms {$aliasPrefix}P2 WHERE {$aliasPrefix}P2.ENTITY = '{$permEntity}' AND {$scopeSql}";
					if (!empty($effectiveEntityIDs))
					{
						$subQuery .= " AND {$aliasPrefix}P2.ENTITY_ID IN (" . implode(',', $effectiveEntityIDs) . ")";
					}
					$subQueries[] = $subQuery;
				}

				if (!$isRestricted)
				{
					$isRestricted = true;
				}
			}
		}
		unset($permissionSet);

		if (!$isRestricted)
		{
			return '';
		}

		if ($options->isReadAllAllowed())
		{
			//Add permission 'Read allowed to Everyone' permission
			$readAll = CCrmPerms::ATTR_READ_ALL;
			$subQuery = "SELECT {$aliasPrefix}P.ENTITY_ID FROM b_crm_entity_perms {$aliasPrefix}P WHERE {$aliasPrefix}P.ENTITY = '{$permEntity}' AND {$aliasPrefix}P.ATTR = '{$readAll}'";
			if (!empty($effectiveEntityIDs))
			{
				$subQuery .= " AND {$aliasPrefix}P.ENTITY_ID IN (" . implode(',', $effectiveEntityIDs) . ")";
			}
			$subQueries[] = $subQuery;
		}

		$subQuerySql = implode($options->needUseDistinctUnion() ? ' UNION ' : ' UNION ALL ', $subQueries);

		if ($forceRawQueryResult)
		{
			return (new RawQueryResult())->makeCompatible($subQuerySql, $aliasPrefix);
		}

		return $options->getResult()->makeCompatible($subQuerySql, $aliasPrefix);
	}

	protected function getScopeRegexForEntity(string $permissionEntityType): string
	{
		$scopeRegex = '';
		$entityName = UserPermissions::getEntityNameByPermissionEntityType($permissionEntityType);
		$factory = Container::getInstance()->getFactory(CCrmOwnerType::ResolveID($entityName));
		if ($factory && $factory->isStagesSupported())
		{
			$stageFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);
			if ($entityName === CCrmOwnerType::QuoteName)
			{
				$stageFieldName = 'QUOTE_ID'; // strange but true
			}
			$scopeRegex = '/^' . $stageFieldName . '[0-9A-Z\:\_\-]+$/i';
		}

		return $scopeRegex;
	}

	protected function registerPermissionSet(array &$items, array $newItem): array
	{
		$qty = count($items);
		if ($qty === 0)
		{
			$items[] = $newItem;

			return $newItem;
		}

		$user = $newItem['USER'];
		$openedOnly = $newItem['OPENED_ONLY'];
		$departments = $newItem['DEPARTMENTS'];
		$departmentQty = count($departments);
		for ($i = 0; $i < $qty; $i++)
		{
			if ($user === $items[$i]['USER']
				&& $openedOnly === $items[$i]['OPENED_ONLY']
				&& $departmentQty === count($items[$i]['DEPARTMENTS'])
				&& ($departmentQty === 0 || count(array_diff($departments, $items[$i]['DEPARTMENTS'])) === 0))
			{
				$items[$i]['SCOPES'] = array_merge($items[$i]['SCOPES'], $newItem['SCOPES']);

				return $items[$i];
			}
		}

		$items[] = $newItem;

		return $newItem;
	}
}
