<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskCreatedStrategy implements RecipientStrategyInterface
{
	use AddUserTrait;
	use StrategyConstructorTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function getRecipients(): array
	{
		$sender = $this->getSender();
		if (!$sender)
		{
			return [];
		}

		$recipients = $this->userRepository->getRecepients($this->task, $sender, $this->dictionary->get('options', []));
		if (empty($recipients))
		{
			return [];
		}

		if (
			$sender->getId() !== $this->task->getCreatedBy()
			&& in_array($sender->getId(), $this->userRepository->getParticipants($this->task, $this->dictionary->get('options', [])))
		)
		{
			// special case: add sender to recipients
			$recipients = $this->addUserToRecipients($recipients, $sender);
		}

		return $recipients;
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getSender($this->task, $this->dictionary->get('options', []));
	}
}