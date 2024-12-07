<?php

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Intranet\Entity\Department;

/**
 * @extends BaseCollection<Department>
 */
class DepartmentCollection extends BaseCollection
{
	protected static function getItemClassName(): string
	{
		return Department::class;
	}

	public function filterByUsersDepartmentIdList(array $departmentIds): DepartmentCollection
	{
		return $this->filter(fn (Department $department) => in_array($department->getIblockSectionId(), $departmentIds));
	}
}