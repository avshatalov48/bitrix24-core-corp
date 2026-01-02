<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

class UpdateTopic
{
	public function __invoke(array $fullTaskData, array $sourceTaskData): void
	{
		if ($fullTaskData['TITLE'] === $sourceTaskData['TITLE'])
		{
			return;
		}

		(new Async\Message\UpdateTopic($fullTaskData))->sendByTaskId((int)$fullTaskData['ID']);
	}
}