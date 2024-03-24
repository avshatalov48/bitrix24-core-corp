<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

interface RecipientStrategyInterface
{
	public function __construct(UserRepositoryInterface $userRepository, TaskObject $task, Dictionary $dictionary);

	/** @return User[] */
	public function getRecipients(): array;

	public function getSender(): ?User;
}