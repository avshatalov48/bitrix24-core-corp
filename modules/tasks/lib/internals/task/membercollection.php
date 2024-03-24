<?php

namespace Bitrix\Tasks\Internals\Task;

class MemberCollection extends EO_Member_Collection
{
	public function addResponsible(int $userId, int $taskId = 0): static
	{
		$this->add(MemberObject::createResponsible($userId, $taskId));
		return $this;
	}
}