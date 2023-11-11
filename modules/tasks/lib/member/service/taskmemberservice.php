<?php

namespace Bitrix\Tasks\Member\Service;

use Bitrix\Tasks\Member\MemberService;
use Bitrix\Tasks\Member\Repository;

class TaskMemberService extends MemberService
{
	public function getRepository(): Repository
	{
		return new Repository\TaskRepository($this->entityId);
	}
}