<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class NotificationReplyStrategy implements RecipientStrategyInterface
{
	use StrategyConstructorTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function getRecipients(): array
	{
		$responsible = $this->userRepository->getUserById($this->task->getResponsibleId());

		return (null === $responsible) ? [] : [$responsible];
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getUserById($this->dictionary->get('authorId'));
	}
}