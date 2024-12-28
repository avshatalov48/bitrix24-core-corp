<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\Util\Result;

class UnMute
{
	/**
	 * @return Result[]
	 */
	public function runBatch(int $userId, array $taskIds): array
	{
		$result = [];

		foreach ($taskIds as $id)
		{
			$result[] = [
				UserOption::delete($id, $userId, Option::MUTED),
				'taskId' => $id,
			];
		}

		return $result;
	}
}
