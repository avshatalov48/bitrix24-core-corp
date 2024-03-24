<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Internals\Notification\Message;

trait ByRoleTrait
{
	public function execute(): bool
	{
		// triple foreach - just because I can afford it
		foreach ($this->providers as $provider)
		{
			foreach ($this->getSupportedRoles() as $role)
			{
				$this->createDictionary(['role' => $role]);
				$sender = $this->getCurrentSender();
				if (is_null($sender))
				{
					continue;
				}

				$recipients = $this->getCurrentRecipients();
				foreach ($recipients as $recipient)
				{
					$metaData = $this->getMetadata([
						'task' => $this->task,
						'member_code' => $role,
					]);
					$message = new Message($sender, $recipient, $metaData);

					$provider->addMessage($message);
				}
			}

			$this->buffer->addProvider($provider);
		}

		return true;
	}
}