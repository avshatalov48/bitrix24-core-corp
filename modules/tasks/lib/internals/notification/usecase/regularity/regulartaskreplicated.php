<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase\Regularity;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\UseCase\AbstractCase;

class RegularTaskReplicated extends AbstractCase
{
	public function execute($params = []): bool
	{
		$this->createDictionary(['options' => $params]);

		foreach ($this->providers as $provider)
		{
			$sender = $this->getCurrentSender();
			$recipients = $this->getCurrentRecipients();
			foreach ($recipients as $recipient)
			{
				$metadata = new Metadata(
					EntityCode::CODE_TASK,
					EntityOperation::REPLICATE_REGULAR,
					[
						'task' => $this->task,
						'user_repository' => $this->userRepository,
						'user_params' => $params,
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