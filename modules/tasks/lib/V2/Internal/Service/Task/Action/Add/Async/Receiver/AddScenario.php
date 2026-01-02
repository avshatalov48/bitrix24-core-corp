<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Async\Message;
use CTaskSync;

class AddScenario extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\AddScenario)
		{
			return;
		}

		if ($message->taskId <= 0 || empty($message->scenarios))
		{
			return;
		}

		Container::getInstance()->getScenarioService()->save($message->taskId, $message->scenarios);
	}
}