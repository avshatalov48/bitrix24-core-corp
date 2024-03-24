<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Task\Status;

class TaskStatusChanged extends AbstractCase
{
	public function execute(int $taskCurrentStatus, $params = []): bool
	{
		if ($taskCurrentStatus < Status::NEW || $taskCurrentStatus > Status::DECLINED)
		{
			return false;
		}

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
				$provider->addMessage(new Message(
					$sender,
					$recipient,
					new Metadata(
						EntityCode::CODE_TASK,
						EntityOperation::STATUS_CHANGED,
						[
							'task' => $this->task,
							'task_current_status' => $taskCurrentStatus,
							'user_repository' => $this->userRepository,
						]
					)
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}