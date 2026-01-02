<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\Relation\Gantt;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Provider\Params\Pager;
use Bitrix\Main\Provider\Params\SelectInterface;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Enum\Type;
use Bitrix\Tasks\V2\Infrastructure\Controller\ActionFilter\Rule\GanttRestriction;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Access\Task\Gantt\Permission\Update;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission\Read;
use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Entity\Task\GanttLink;
use Bitrix\Tasks\V2\Public\Command\Gantt\AddDependenceCommand;
use Bitrix\Tasks\V2\Public\Command\Gantt\DeleteDependenceCommand;
use Bitrix\Tasks\V2\Public\Command\Gantt\UpdateDependenceCommand;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskParams;
use Bitrix\Tasks\V2\Public\Provider\Relation\GanttDependenceProvider;

class Dependence extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Relation.Gantt.Dependence.list
	 */
	#[CloseSession]
	public function listAction(
		#[Read]
		Task $task,
		SelectInterface $relationTaskSelect,
		PageNavigation $pageNavigation,
		GanttDependenceProvider $subTaskProvider,
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
	 * @ajaxAction tasks.V2.Task.Relation.Gantt.Dependence.listByIds
	 */
	#[CloseSession]
	public function listByIdsAction(
		#[ElementsType(typeEnum: Type::Numeric)]
		array $taskIds,
		GanttDependenceProvider $ganttDependenceProvider,
	): array
	{
		return [
			'tasks' => $ganttDependenceProvider->getTasksByIds($taskIds, $this->userId),
		];
	}

	/**
	 * @ajaxAction tasks.V2.Task.Gantt.Dependence.add
	 */
	#[GanttRestriction]
	public function addAction(
		#[Update]
		GanttLink $ganttLink,
	): ?bool
	{
		$result = (new AddDependenceCommand(
			taskId: $ganttLink->taskId,
			dependentId: $ganttLink->dependentId,
			userId: $this->userId,
			linkType: $ganttLink->type,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Gantt.Dependence.update
	 */
	public function updateAction(
		#[Update]
		GanttLink $ganttLink,
	): ?bool
	{
		$result = (new UpdateDependenceCommand(
			taskId: $ganttLink->taskId,
			dependentId: $ganttLink->dependentId,
			linkType: $ganttLink->type,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Gantt.Dependence.delete
	 */
	public function deleteAction(
		#[Update]
		GanttLink $ganttLink,
	): ?bool
	{
		$result = (new DeleteDependenceCommand(
			taskId: $ganttLink->taskId,
			dependentId: $ganttLink->dependentId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}
}
