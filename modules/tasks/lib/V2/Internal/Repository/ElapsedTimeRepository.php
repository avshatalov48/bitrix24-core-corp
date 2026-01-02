<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Internals\Task\ElapsedTimeTable;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Exception\Task\ElapsedTimeException;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ElapsedTimeMapper;

class ElapsedTimeRepository implements ElapsedTimeRepositoryInterface
{
	public function __construct(
		private readonly ElapsedTimeMapper $elapsedTimeMapper,
	)
	{

	}

	public function save(Entity\Task\ElapsedTime $elapsedTime): int
	{
		if ($elapsedTime->getId())
		{
			return $this->update($elapsedTime);
		}

		return $this->add($elapsedTime);
	}

	public function delete(int $id): void
	{
		$result = ElapsedTimeTable::delete($id);
		if (!$result->isSuccess())
		{
			throw new ElapsedTimeException($result->getError()?->getMessage());
		}
	}

	public function getSum(int $taskId): int
	{
		$data = ElapsedTimeTable::query()
			->setSelect([Query::expr('SUM')->sum('SECONDS')])
			->where('TASK_ID', $taskId)
			->exec()
			->fetch();

		if (!is_array($data))
		{
			return 0;
		}

		return (int)$data['SUM'];
	}

	private function add(Entity\Task\ElapsedTime $elapsedTime): int
	{
		$data = $this->elapsedTimeMapper->mapFromEntity($elapsedTime);

		$result = ElapsedTimeTable::add($data);
		if (!$result->isSuccess())
		{
			throw new ElapsedTimeException($result->getError()?->getMessage());
		}

		return (int)$result->getId();
	}

	private function update(Entity\Task\ElapsedTime $elapsedTime): int
	{
		$data = $this->elapsedTimeMapper->mapFromEntity($elapsedTime);

		$result = ElapsedTimeTable::update($elapsedTime->getId(), $data);
		if (!$result->isSuccess())
		{
			throw new ElapsedTimeException($result->getError()?->getMessage());
		}

		return $elapsedTime->getId();
	}
}