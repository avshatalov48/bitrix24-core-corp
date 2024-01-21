<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase\Regularity;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\User;

class RegularTaskStarted
{
	use RecipientsTrait;

	public function execute($params = []): bool
	{
		$sender = $this->userRepository->getSender($this->task, $params);
		if (!$sender)
		{
			return false;
		}

		$recipient = $this->userRepository->getUserById($this->task->getResponsibleId());

		foreach ($this->providers as $provider)
		{
			$metadata = new Metadata(
				EntityCode::CODE_TASK,
				EntityOperation::START_REGULAR,
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

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}