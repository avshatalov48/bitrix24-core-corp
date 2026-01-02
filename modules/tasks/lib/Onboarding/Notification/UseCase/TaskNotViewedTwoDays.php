<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Onboarding\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\Strategy\Default\TaskCreatorReminderStrategy;
use Bitrix\Tasks\Internals\Notification\UseCase\AbstractCase;
use ReflectionClass;

class TaskNotViewedTwoDays extends AbstractCase
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
					EntityOperation::NOT_VIEWED_TWO_DAYS,
					[
						'task' => $this->task,
						'user_repository' => $this->userRepository,
						'user_params' => $params,
					],
				);

				$provider->addMessage(new Message(
					$sender,
					$recipient,
					$metadata,
				));
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}

	public function getStrategyAlias(): string
	{
		return (new ReflectionClass(TaskCreatorReminderStrategy::class))->getShortName();
	}
}
