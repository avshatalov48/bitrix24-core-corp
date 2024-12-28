<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase\Flow;

use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\Metadata;
use Bitrix\Tasks\Internals\Notification\UseCase\AbstractCase;

class TaskAddedToFlowWithHimselfDistribution extends AbstractCase
{
	public function execute(FlowEntity $flow): bool
	{
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
					EntityOperation::ADD_TO_FLOW_WITH_HIMSELF_DISTRIBUTION,
					[
						'task' => $this->task,
						'user_repository' => $this->userRepository,
						'flow' => $flow,
					]
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
}