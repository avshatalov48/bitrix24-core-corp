<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Receiver;

use Bitrix\Main\Messenger\Entity\MessageInterface;
use Bitrix\Main\Messenger\Receiver\AbstractReceiver;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Async\Message;
use Bitrix\Tasks\Integration\Forum;

class UpdateTopic extends AbstractReceiver
{
	protected function process(MessageInterface $message): void
	{
		if (!$message instanceof Message\UpdateTopic)
		{
			return;
		}

		Forum\Task\Topic::updateTopicTitle($message->task['FORUM_TOPIC_ID'], $message->task['TITLE']);
	}
}