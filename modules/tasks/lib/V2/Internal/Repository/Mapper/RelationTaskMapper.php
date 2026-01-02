<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\TaskCollection;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Entity\User;

class RelationTaskMapper
{
	public function mapToEntity(
		array $task,
		?User $responsible = null,
		?array $rights = null,
	): Task
	{
		$taskId = (int)($task['ID'] ?? 0);

		return new Task(
			id: $taskId,
			title: $task['TITLE'] ?? '',
			responsible: $responsible,
			deadlineTs: ($task['DEADLINE'] ?? null) instanceof DateTime ? $task['DEADLINE']->getTimestamp() : null,
			rights: $rights,
		);
	}

	public function mapToCollection(
		array $tasks,
		?UserCollection $users = null,
		?array $rights = null,
	): TaskCollection
	{
		$entities = [];
		foreach ($tasks as $task)
		{
			$taskId = (int)($task['ID'] ?? 0);

			$entities[]= $this->mapToEntity(
				task: $task,
				responsible: $users?->findOneById((int)($task['RESPONSIBLE_ID'] ?? 0)),
				rights: $rights[$taskId] ?? null,
			);
		}

		return new TaskCollection(...$entities);
	}
}
