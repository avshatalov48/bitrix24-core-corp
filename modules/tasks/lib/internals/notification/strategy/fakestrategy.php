<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

class FakeStrategy implements RecipientStrategyInterface
{
	public function __construct(UserRepositoryInterface $userRepository, TaskObject $task, Dictionary $dictionary)
	{
	}

	public function getRecipients(): array
	{
		return [];
	}

	public function getSender(): ?User
	{
		return null;
	}
}