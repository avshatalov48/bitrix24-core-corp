<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\Integration\Search;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Message;
use Bitrix\Tasks\Internals;

class UpdateSearchIndex extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\UpdateSearchIndex)
		{
			return;
		}

		$task = $message->unSerialiseDateTime(
			payload: $message->task,
			dateTimeKeys: ['CHANGED_DATE', 'CREATED_DATE'],
		);

		Search\Task::index($task);
		Internals\SearchIndex::setTaskSearchIndex((int)$message->task['ID']);
	}
}
