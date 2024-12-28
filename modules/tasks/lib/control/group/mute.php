<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Internals\UserOption\Option;

class Mute
{
	public function runBatch(int $userId, array $taskIds): array
	{
		$result = [];

		foreach ($taskIds as $id)
		{
			$result[] = [
				UserOption::add($id, $userId, Option::MUTED),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
