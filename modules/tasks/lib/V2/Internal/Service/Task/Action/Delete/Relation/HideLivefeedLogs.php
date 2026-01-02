<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\SocialNetwork\Log;

class HideLivefeedLogs
{
	public function __invoke(array $fullTaskData): void
	{
		if (Loader::includeModule('socialnetwork'))
		{
			Log::hideLogByTaskId($fullTaskData['ID']);
		}
	}
}