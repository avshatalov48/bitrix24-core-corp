<?php

namespace Bitrix\Tasks\Internals\Notification\Strategy\Default;

use Bitrix\Tasks\Internals\Notification\Dictionary;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\TaskObject;

trait StrategyConstructorTrait
{
	public function __construct(UserRepositoryInterface $userRepository, TaskObject $task, Dictionary $dictionary)
	{
		$this->userRepository = $userRepository;
		$this->task = $task;
		$this->dictionary = $dictionary;
	}
}