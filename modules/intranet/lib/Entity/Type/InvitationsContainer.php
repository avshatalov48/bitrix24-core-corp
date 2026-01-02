<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Type;

use Bitrix\Intranet\Entity\Collection\DepartmentCollection;
use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\Type;
use Bitrix\Intranet\Public\Type\Collection\InvitationCollection;

class InvitationsContainer
{
	public function __construct(
		private readonly array|InvitationCollection $invitation = [],
		private readonly ?DepartmentCollection $departmentCollection = null
	)
	{
	}

	public function backwardsCompatibility(): array
	{
		$data = [];

		foreach ($this->invitation as $invitation)
		{
			$item = $invitation->toArray();
			$data[] = $item;
		}

		return ['ITEMS' => $data];
	}

	public function getInvitationCollection(): array|InvitationCollection
	{
		return $this->invitation;
	}

	public function getDepartmentCollection(): ?DepartmentCollection
	{
		return $this->departmentCollection;
	}
}