<?php

namespace Bitrix\HumanResources\Repository;

use Bitrix\HumanResources\Model\NodePathTable;

class NodePathRepository
{
	protected const DEFAULT_TTL = 3600;

	public static function getNearestParentDepartmentIdByDepartmentList(int $departmentId, array $checkedDepartmentIds): ?int
	{
		$nodePath = NodePathTable::query()
			->setSelect(['PARENT_ID'])
			->where('CHILD_ID', $departmentId)
			->whereIn('PARENT_ID', $checkedDepartmentIds)
			->setOrder(['DEPTH' => 'ASC'])
			->setLimit(1)
			->setCacheTtl(self::DEFAULT_TTL)
			->fetch()
		;

		return $nodePath && isset($nodePath['PARENT_ID'])
			? (int)$nodePath['PARENT_ID']
			: null
		;
	}
}