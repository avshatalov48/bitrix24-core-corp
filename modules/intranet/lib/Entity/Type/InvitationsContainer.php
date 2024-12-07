<?php

namespace Bitrix\Intranet\Entity\Type;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\Department;

class InvitationsContainer
{
	public function __construct(
		private array $invitation = [],
		private ?DepartmentCollection $departmentCollection = null
	)
	{}

	public function backwardsCompatibility(): array
	{
		$data = [];

		foreach ($this->invitation as $invitation)
		{
			$item = $invitation->toArray();
			if ($this->departmentCollection)
			{
				$item['UF_DEPARTMENT'] = $this->departmentCollection->map(
					fn(Department $department) => $department->getId()
				);
			}
			$data[] = $item;
		}

		return ['ITEMS' => $data];
	}
}