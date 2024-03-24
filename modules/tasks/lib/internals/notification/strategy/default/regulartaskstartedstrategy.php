<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class RegularTaskStartedStrategy implements RecipientStrategyInterface
{
	use StrategyConstructorTrait;

	private UserRepositoryInterface $userRepository;
	private TaskObject $task;
	private Dictionary $dictionary;

	public function getRecipients(): array
	{
		$responsible = $this->userRepository->getUserById($this->task->getResponsibleId());
		return is_null($responsible) ? [] : [$responsible];
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getSender($this->task, $this->dictionary->get('options', []));
	}
}