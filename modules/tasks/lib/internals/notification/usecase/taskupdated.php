<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use CTaskLog;

class TaskUpdated extends AbstractCase
{
	public function execute(array $newFields, array $previousFields, array $params = []): bool
	{
		$this->createDictionary(['options' => $params, 'previousFields' => $previousFields]);

		$changes = CTaskLog::GetChanges($previousFields, $newFields);
		$trackedFields = CTaskLog::getTrackedFields();

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
						EntityOperation::UPDATE,
						[
							'task' => $this->task,
							'previous_fields' => $previousFields,
							'changes' => $changes,
							'tracked_fields' => $trackedFields,
							'user_repository' => $this->userRepository,
							'user_params' => $params,
						]
					)
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}