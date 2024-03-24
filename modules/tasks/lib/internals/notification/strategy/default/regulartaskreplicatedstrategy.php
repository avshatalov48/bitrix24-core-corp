<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class RegularTaskReplicatedStrategy implements RecipientStrategyInterface
{
	use AddUserTrait;
	use StrategyConstructorTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function getRecipients(): array
	{
		$sender = $this->getSender();
		$recipients = $this->userRepository->getRecepients($this->task, $sender, $this->dictionary->get('options', []));

		return $this->addUserToRecipients($recipients, $sender);
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getSender($this->task, $this->dictionary->get('options', []));
	}
}