<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

class AddLastActivity
{
	public function __invoke(array $fields): void
	{
		if (($fields['GROUP_ID'] ?? 0) <= 0)
		{
			return;
		}

		(new Async\Message\AddLastActivity($fields))->sendByTaskId($fields['ID']);
	}
}