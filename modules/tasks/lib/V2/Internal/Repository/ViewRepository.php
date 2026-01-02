<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\View;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ViewMapper;

class ViewRepository implements ViewRepositoryInterface
{
	public function __construct(
		private readonly ViewMapper $viewMapper,
	)
	{

	}

	public function get(int $taskId, int $userId): ?View
	{
		$primary = [
			'TASK_ID' => $taskId,
			'USER_ID' => $userId,
		];

		$data = ViewedTable::getByPrimary($primary)->fetch();
		if (!is_array($data))
		{
			return null;
		}

		return $this->viewMapper->mapToEntity($data);
	}

	public function upsert(View $view): void
	{
		$data = $this->viewMapper->mapFromEntity($view);

		ViewedTable::addMerge($data);
	}
}