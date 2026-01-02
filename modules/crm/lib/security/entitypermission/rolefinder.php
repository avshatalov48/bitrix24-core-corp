<?php

namespace Bitrix\Crm\Security\EntityPermission;

use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;

final class RoleFinder
{
	public function getRoleIds(array $permissions): array
	{
		if (empty($permissions))
		{
			return [];
		}

		$filter = $this->getFilter($permissions);

		if (empty($filter))
		{
			return [];
		}

		$query = $this->getQuery($filter);

		return array_map(
			'intval',
			array_column($query?->fetchAll() ?? [], 'ROLE_ID')
		);
	}

	private function getFilter(array $permissions): array
	{
		$filter = [];
		foreach ($permissions as $permission)
		{
			$entity = $permission['permissionEntity'] ?? null;
			$permType = $permission['permType'] ?? null;
			$attr = $permission['attr'] ?? null;

			if (!$entity || !$permType || !$attr)
			{
				continue;
			}

			$filter[] = [
				'ENTITY' => $entity,
				'PERM_TYPE' => $permType,
				'ATTR' => $attr,
			];
		}

		return $filter;
	}

	private function getQuery(array $filter): ?Query
	{
		if (empty($filter))
		{
			return null;
		}

		$conditionTree = (new ConditionTree())->logic(ConditionTree::LOGIC_OR);
		foreach ($filter as $group)
		{
			$innerConditionTree = new ConditionTree();
			foreach ($group as $key => $value)
			{
				$innerConditionTree->where($key, '=', $value);
			}

			$conditionTree->where($innerConditionTree);
		}

		return RolePermissionTable::query()
			->setSelect(['ROLE_ID'])
			->where($conditionTree)
			->setGroup('ROLE_ID')
			->registerRuntimeField(
				'CNT',
				new ExpressionField('FIELDS_CNT', 'COUNT(CONCAT(ENTITY, \'-\', PERM_TYPE, \'-\', ATTR))')
			)
			->having('CNT',  '=', count($filter))
		;
	}
}