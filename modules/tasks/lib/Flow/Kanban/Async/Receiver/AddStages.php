<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Kanban\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\Flow\Kanban\Async\Message;
use Bitrix\Tasks\Flow\Internal\DI\Container;

final class AddStages extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\AddStages)
		{
			return;
		}

		Container::getInstance()->getKanbanService()->addStages(
			projectId: $message->projectId,
			ownerId: $message->ownerId,
			flowId: $message->flowId,
		);
	}
}