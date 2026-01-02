<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\TimeMan\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;
use CTimeManUser;

class UserService
{
	public function getTasks(int $userId): ?array
	{
		if (!Loader::includeModule('timeman'))
		{
			return null;
		}

		$user = new CTimeManUser($userId);

		$info = $user->GetCurrentInfo();

		if (isset($info['TASKS']) && is_array($info['TASKS']))
		{
			$taskIds = $info['TASKS'];

			Collection::normalizeArrayValuesByInt($taskIds);

			return $taskIds;
		}

		return null;
	}
}