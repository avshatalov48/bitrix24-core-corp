<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase\Strategy;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\Strategy\RecipientStrategyInterface;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class TaskDeletedStrategy implements RecipientStrategyInterface
{
	protected UserRepositoryInterface $userRepository;
	protected TaskObject $task;
	protected Dictionary $dictionary;

	public function __construct(
		UserRepositoryInterface $userRepository,
		TaskObject $task,
		Dictionary $dictionary,
	)
	{
		$this->userRepository = $userRepository;
		$this->task = $task;
		$this->dictionary = $dictionary;
	}

	public function getRecipients(): array
	{
		return $this->userRepository->getUsersByIds(
			$this->userRepository->getParticipants($this->task, $this->dictionary->get('options', []))
		);
	}

	public function getSender(): ?User
	{
		return $this->userRepository->getSender($this->task, $this->dictionary->get('options', []));
	}
}