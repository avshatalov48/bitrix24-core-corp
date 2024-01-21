<?php

namespace Bitrix\Tasks\Member\Service;

use Bitrix\Tasks\Member\AbstractMemberService;
use Bitrix\Tasks\Member\Repository\TaskRepository;
use Bitrix\Tasks\Member\RepositoryInterface;

class TaskMemberService extends AbstractMemberService
{
	public function getRepository(): RepositoryInterface
	{
		return new TaskRepository($this->entityId);
	}
}