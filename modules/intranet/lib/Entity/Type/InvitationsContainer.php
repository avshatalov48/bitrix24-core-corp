<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Type;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\Type;

class InvitationsContainer
{
	public function __construct(
		private readonly array|Type\Collection\InvitationCollection $invitation = [],
		private readonly ?DepartmentCollection $departmentCollection = null
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