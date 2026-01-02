<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Async\Message;

class RecountSort extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\RecountSort)
		{
			return;
		}

		SortingTable::fixSiblingsEx($message->taskId);
	}
}