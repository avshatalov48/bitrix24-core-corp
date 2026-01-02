<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskCreatorReminderStrategy implements RecipientStrategyInterface
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

		$recipient = $this->userRepository->getUserById($this->task->getCreatedBy());
		if ($recipient === null)
		{
			return [];
		}

		return [$recipient];
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getUserById($this->task->getCreatedBy());
	}
}