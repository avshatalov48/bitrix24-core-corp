<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Tasks\Provider\Log\TaskLogQuery;
use Bitrix\Tasks\V2\Internal\Entity\HistoryLogCollection;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\HistoryLogMapper;

class TaskHistoryRepository implements TaskHistoryRepositoryInterface
{
	public function __construct(
		private readonly HistoryLogMapper $historyLogMapper
	)
	{

	}

	public function tail(int $taskId, int $offset = 0): HistoryLogCollection
	{
		$filter = new ConditionTree();
		$filter->where('TASK_ID', $taskId);

		$query = (new TaskLogQuery())
			->setSelect([
				'ID',
				'CREATED_DATE',
				'USER_ID',
				'TASK_ID',
				'FIELD',
				'FROM_VALUE',
				'TO_VALUE',
			])
			->setOrderBy(['ID' => 'DESC'])
			->setDistinct(false)
			->setOffset($offset)
			->setLimit(50)
			->setWhere($filter)
		;

		$logs = Container::getInstance()->getTaskLogProvider()->getList($query);

		return $this->historyLogMapper->mapToCollection($logs);
	}
}
