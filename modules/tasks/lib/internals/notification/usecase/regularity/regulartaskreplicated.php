<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase\Regularity;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;

class RegularTaskReplicated
{
	use RecipientsTrait;

	public function execute($params = []): bool
	{
		$sender = $this->userRepository->getSender($this->task, $params);
		if (!$sender)
		{
			return false;
		}

		$recipients = $this->userRepository->getRecepients($this->task, $sender, $params);
		$recipients = $this->addUserToRecipients($recipients, $sender);

		if (empty($recipients))
		{
			return false;
		}

		foreach ($this->providers as $provider)
		{
			foreach ($recipients as $recipient)
			{
				$metadata = new Metadata(
					EntityCode::CODE_TASK,
					EntityOperation::REPLICATE_REGULAR,
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