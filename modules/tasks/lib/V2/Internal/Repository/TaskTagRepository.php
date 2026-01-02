<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\V2\Internal\Entity\TagCollection;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\TagMapper;

class TaskTagRepository implements TaskTagRepositoryInterface
{
	public function __construct(
		private readonly TagMapper $tagMapper,
	)
	{

	}

	public function getById(int $taskId): TagCollection
	{
		$data = LabelTable::query()
			->setSelect(['ID', 'USER_ID', 'GROUP_ID', 'NAME'])
			->where('TASK_TAG.TASK_ID', $taskId)
			->exec()
			->fetchAll();

		$data = array_map(static fn($item): array => $item + ['TASK_ID' => $taskId], $data);

		return $this->tagMapper->mapToCollection($data);
	}

	public function getByIds(array $taskIds): TagCollection
	{
		if (empty($taskIds))
		{
			return new TagCollection();
		}

		$data = LabelTable::query()
			->setSelect(['ID', 'USER_ID', 'GROUP_ID', 'NAME', 'TASK_ID' => 'TASK_TAG.TASK_ID'])
			->whereIn('TASK_ID', $taskIds)
			->exec()
			->fetchAll();

		return $this->tagMapper->mapToCollection($data);
	}

	public function invalidate(int $taskId): void
	{
	}
}
