<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Internals\Task\UserOptionTable;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Exception\Task\UserOptionException;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\UserOptionMapper;

class TaskUserOptionRepository implements TaskUserOptionRepositoryInterface
{
	public function __construct(
		private readonly UserOptionMapper $userOptionMapper,
	)
	{

	}

	public function get(int $taskId, ?int $userId = null): Entity\Task\UserOptionCollection
	{
		$query = UserOptionTable::query()
			->setSelect(['ID', 'OPTION_CODE'])
			->where('TASK_ID', $taskId);

		$userId ??= 0;

		if ($userId)
		{
			$query->where('USER_ID', $userId);
		}
		else
		{
			$query->addSelect('USER_ID');
		}

		$data = $query->exec()->fetchAll();

		$options = [];
		foreach ($data as $row)
		{
			$options[] = [
				'ID' => (int)$row['ID'],
				'OPTION_CODE' => (int)$row['OPTION_CODE'],
				'TASK_ID' => $taskId,
				'USER_ID' => $userId > 0 ? $userId : (int)$row['USER_ID'],
			];
		}

		return $this->userOptionMapper->mapToCollection($options);
	}

	public function isSet(int $code, int $taskId, int $userId): bool
	{
		$options = $this->get($taskId, $userId);

		return $options->findOne(['code' => $code]) !== null;
	}

	public function add(Entity\Task\UserOption $userOption): void
	{
		$data = $this->userOptionMapper->mapFromEntity($userOption);

		try
		{
			$result = UserOptionTable::add($data);
		}
		catch (DuplicateEntryException)
		{
			return;
		}

		if (!$result->isSuccess())
		{
			throw new UserOptionException($result->getError()?->getMessage());
		}
	}

	public function delete(array $codes = [], int $taskId = 0, int $userId = 0): void
	{
		$filter = [];

		Collection::normalizeArrayValuesByInt($codes, false);
		if (!empty($codes))
		{
			$filter['@OPTION_CODE'] = $codes;
		}

		if ($taskId)
		{
			$filter['=TASK_ID'] = $taskId;
		}

		if ($userId)
		{
			$filter['=USER_ID'] = $userId;
		}

		if (empty($filter))
		{
			return;
		}

		try
		{
			UserOptionTable::deleteByFilter($filter);
		}
		catch (SqlQueryException $e)
		{
			throw new UserOptionException($e->getMessage());
		}
	}
}
