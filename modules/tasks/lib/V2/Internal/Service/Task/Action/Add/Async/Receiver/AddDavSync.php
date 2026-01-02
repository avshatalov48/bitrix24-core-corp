<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Message;
use CTaskSync;

class AddDavSync extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\AddDavSync)
		{
			return;
		}

		$id = (int)($message->fields['ID'] ?? 0);
		$repository = Container::getInstance()->getTaskRepository();
		if (!$repository->isExists($id))
		{
			return;
		}

		CTaskSync::AddItem($message->fields);
	}
}