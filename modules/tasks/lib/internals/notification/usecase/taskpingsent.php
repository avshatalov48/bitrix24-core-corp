<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;

class TaskPingSent extends AbstractCase
{
	public function execute(int $authorId, $params = []): bool
	{
		$this->createDictionary(['options' => $params, 'authorId' => $authorId]);

		foreach ($this->providers as $provider)
		{
			$sender = $this->getCurrentSender();
			if (is_null($sender))
			{
				continue;
			}

			$recipients = $this->getCurrentRecipients();
			foreach ($recipients as $recipient)
			{
				$provider->addMessage(new Message(
					$sender,
					$recipient,
					new Metadata(
						EntityCode::CODE_TASK,
						EntityOperation::PING_STATUS,
						[
							'task' => $this->task
						]
					)
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}