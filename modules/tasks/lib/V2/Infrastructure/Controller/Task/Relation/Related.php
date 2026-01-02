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
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\TaskCollection;
use Bitrix\Tasks\V2\Public\Command\Task\Relation\AddRelatedTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Relation\DeleteRelatedTaskCommand;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskParams;
use Bitrix\Tasks\V2\Public\Provider\Relation\RelatedTaskProvider;
use Bitrix\Tasks\Validation\Rule\Count;

class Related extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Relation.Related.list
	 */
	public function listAction(
		#[Permission\Read]
		Task $task,
		SelectInterface $relationTaskSelect,
		PageNavigation $pageNavigation,
		RelatedTaskProvider $relatedTaskProvider,
		bool $withIds = true,
	): array
	{
		$params = new RelationTaskParams(
			userId: $this->userId,
			taskId: (int)$task->id,
			pager: Pager::buildFromPageNavigation($pageNavigation),
			checkRootAccess: false,
			select: $relationTaskSelect,
		);

		$response = [
			'tasks' => $relatedTaskProvider->getTasks($params),
		];

		if ($withIds)
		{
			$response['ids'] = $relatedTaskProvider->getTaskIds($params);
		}

		return $response;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Relation.Related.listByIds
	 */
	#[CloseSession]
	public function listByIdsAction(
		#[ElementsType(typeEnum: Type::Numeric)]
		array $taskIds,
		RelatedTaskProvider $relatedTaskProvider,
	): array
	{
		return [
			'tasks' => $relatedTaskProvider->getTasksByIds($taskIds, $this->userId),
		];
	}

	/**
	 * @ajaxAction tasks.V2.Task.Relation.Related.add
	 */
	public function addAction(
		#[Permission\Update]
		Task $task,
		#[Count(max: 50)]
		#[Permission\Read]
		TaskCollection $tasks,
	): ?array
	{
		$response = [];
		foreach ($tasks as $relatedTask)
		{
			$relatedTaskId = (int)$relatedTask->id;
			$result = (new AddRelatedTaskCommand(
				taskId: (int)$task->id,
				relatedTaskId: $relatedTaskId,
				userId: $this->userId,
			))->run();

			$response[$relatedTaskId] = $result->isSuccess();
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}
		}

		return $response;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Relation.Related.delete
	 */
	public function deleteAction(
		#[Permission\Update]
		Task $task,
		#[Count(max: 50)]
		#[Permission\Read]
		TaskCollection $tasks,
	): ?array
	{
		$response = [];
		foreach ($tasks as $relatedTask)
		{
			$relatedTaskId = (int)$relatedTask->id;
			$result = (new DeleteRelatedTaskCommand(
				taskId: (int)$task->id,
				relatedTaskId: $relatedTaskId,
				userId: $this->userId,
			))->run();

			$response[$relatedTaskId] = $result->isSuccess();
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());
			}
		}

		return $response;
	}
}
