<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskTable;

class TaskDirectorProvider
{
	public function getDirectors(int $flowId, array $filter = [], int $tail = 50): array
	{
		if ($flowId <= 0 || $tail <= 0)
		{
			return [];
		}

		try
		{
			$memberField = (new ReferenceField(
				'MEMBERS_INNER',
				MemberTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			))->configureJoinType(Join::TYPE_INNER);

			$flowTaskField = (new ReferenceField(
				'FLOW_TASK_INNER',
				FlowTaskTable::getEntity(),
				Join::on('this.ID', 'ref.TASK_ID')
			))->configureJoinType(Join::TYPE_INNER);

			$tasks = TaskTable::query()
				->setDistinct()
				->addSelect('MEMBERS_INNER.USER_ID', 'USER_ID')
				->addSelect('FLOW_TASK_INNER.FLOW_ID', 'FLOW_ID')
				->setFilter($filter)
				->where('FLOW_TASK_INNER.FLOW_ID', $flowId)
				->where('MEMBERS_INNER.TYPE', RoleDictionary::ROLE_DIRECTOR)
				->setLimit($tail)
				->registerRuntimeField($memberField)
				->registerRuntimeField($flowTaskField)
				->exec()
				->fetchAll();
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
			return [];
		}

		return array_map('intval', array_column($tasks, 'USER_ID'));
	}
}