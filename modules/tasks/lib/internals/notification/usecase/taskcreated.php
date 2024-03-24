<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;

class TaskCreated extends AbstractCase
{
	public function execute($params = []): bool
	{
		$this->createDictionary(['options' => $params]);

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
				$metadata = new Metadata(
					EntityCode::CODE_TASK,
					EntityOperation::ADD,
					[
						'task' => $this->task,
						'user_repository' => $this->userRepository,
						'user_params' => $params
					]
				);

				$provider->addMessage(new Message(
					$sender,
					$recipient,
					$metadata
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}