<?php

namespace Bitrix\Intranet\Infrastructure\Controller\AutoWire;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Intranet\Integration;

trait DepartmentParameterTrait
{
	protected function createDepartmentCollectionParameter(): ExactParameter
	{
		return new ExactParameter(
			DepartmentCollection::class,
			'departmentCollection',
			function($className, ?array $departmentIds = null): ?DepartmentCollection {
				if (!$departmentIds)
				{
					$departmentCollection = new DepartmentCollection();
					$departmentCollection->add((new Integration\HumanResources\Department())->getRootDepartment());

					return $departmentCollection;
				}

				return (new Integration\HumanResources\Department())->getByIds($departmentIds);
			}
		);
	}
}