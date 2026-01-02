<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\Integration\Search;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Message;
use Bitrix\Tasks\Internals;

class AddSearchIndex extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\AddSearchIndex)
		{
			return;
		}

		$id = (int)($message->task['ID'] ?? 0);
		$repository = Container::getInstance()->getTaskRepository();
		if (!$repository->isExists($id))
		{
			return;
		}

		$task = $message->unSerialiseDateTime(
			payload: $message->task,
			dateTimeKeys: ['CHANGED_DATE', 'CREATED_DATE'],
		);

		Search\Task::index($task);
		Internals\SearchIndex::setTaskSearchIndex($id);
	}
}