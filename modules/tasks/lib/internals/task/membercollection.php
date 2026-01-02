<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Tasks\Access\Role\RoleDictionary;

class MemberCollection extends EO_Member_Collection
{
	public function addResponsible(int $userId, int $taskId = 0): static
	{
		$this->add(MemberObject::createResponsible($userId, $taskId));
		return $this;
	}

	public function getAuditorIds(): array
	{
		$auditors = [];

		foreach ($this as $member)
		{
			if ($member->getType() === RoleDictionary::ROLE_AUDITOR)
			{
				$auditors[] = $member->getUserId();
			}
		}

		return $auditors;
	}

	public function getResponsible(): ?int
	{
		foreach ($this as $member)
		{
			if ($member->getType() === RoleDictionary::ROLE_RESPONSIBLE)
			{
				return $member->getUserId();
			}
		}

		return null;
	}

	public function getAccompliceIds(): array
	{
		$accomplices = [];

		foreach ($this as $member)
		{
			if ($member->getType() === RoleDictionary::ROLE_ACCOMPLICE)
			{
				$accomplices[] = $member->getUserId();
			}
		}

		return $accomplices;
	}
}
