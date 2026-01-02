<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Message;
use CTaskSync;

class UpdateDavSync extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\UpdateDavSync)
		{
			return;
		}

		CTaskSync::UpdateItem($message->fields, $message->task);
	}
}