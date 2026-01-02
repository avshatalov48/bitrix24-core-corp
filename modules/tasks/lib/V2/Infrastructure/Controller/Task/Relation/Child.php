<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\Relation;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Provider\Params\Pager;
use Bitrix\Main\Provider\Params\SelectInterface;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Enum\Type;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission\Read;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission\Update;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Public\Command\Task\Relation\DeleteParentRelationCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Relation\SetParentRelationCommand;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskParams;
use Bitrix\Tasks\V2\Public\Provider\Relation\SubTaskProvider;
use Bitrix\Tasks\Validation\Rule\Count;
use Bitrix\Tasks\V2\Internal\Entity\TaskCollection;


class Child extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Relation.Child.list
	 */
	#[CloseSession]
	public function listAction(
		#[Read]
		Task $task,
		SelectInterface $relationTaskSelect,
		PageNavigation $pageNavigation,
		SubTaskProvider $subTaskProvider,
		bool $withIds = true,
	): array
	{
		$params = new RelationTaskParams(
			userId: $this->userId,
			taskId: (int)$task->id,
			pager: Pager::buildFromPageNavigation($pageNavigation),
			select: $relationTaskSelect,
		);

		$response = [
			'tasks' => $subTaskProvider->getTasks($params),
		];

		if ($withIds)
		{
			$response['ids'] = $subTaskProvider->getTaskIds($params);
		}

		return $response;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Relation.Child.listByIds
	 */
	#[CloseSession]
	public function listByIdsAction(
		#[ElementsType(typeEnum: Type::Numeric)]
		array $taskIds,
		SubTaskProvider $subTaskProvider,
	): array
	{
		return [
			'tasks' => $subTaskProvider->getTasksByIds($taskIds, $this->userId),
		];
	}

	/**
	 * @ajaxAction tasks.V2.Task.Relation.Child.add
	 */
	public function addAction(
		#[Read]
		Task $task,
		#[Update]
		#[Count(max: 20)]
		TaskCollection $tasks
	): ?array
	{
		$response = [];
		foreach ($tasks as $subTask)
		{
			$subTaskId = (int)$subTask->id;

			$result = (new SetParentRelationCommand(
				taskId: $subTaskId,
				userId: $this->userId,
				parentId: (int)$task->id
			))->run();

			$response[$subTaskId] = $result->isSuccess();
			if ($response[$subTaskId] === false)
			{
				$this->addErrors($result->getErrors());
			}
		}

		return $response;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Relation.Child.delete
	 */
	public function deleteAction(
		#[Update]
		#[Count(max: 20)]
		TaskCollection $tasks
	): ?array
	{
		$response = [];
		foreach ($tasks as $subTask)
		{
			$subTaskId = (int)$subTask->id;

			$result = (new DeleteParentRelationCommand(
				taskId: $subTaskId,
				userId: $this->userId,
			))->run();

			$response[$subTaskId] = $result->isSuccess();
			if ($response[$subTaskId] === false)
			{
				$this->addErrors($result->getErrors());
			}
		}

		return $response;
	}
}
