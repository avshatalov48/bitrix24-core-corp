<?php

declare(strict_types=1);


namespace Bitrix\Tasks\Internals\Task\Event\View;

use Bitrix\Main\Event;

class OnTaskFirstViewEvent extends Event
{
	public function __construct(int $userId, int $taskId)
	{
		$parameters = [
			'userId' => $userId,
			'taskId' => $taskId,
		];

		parent::__construct('tasks', 'onTaskFirstView', $parameters);
	}

	public function getUserId(): int
	{
		return $this->parameters['userId'];
	}

	public function getTaskId(): int
	{
		return $this->parameters['taskId'];
	}
}