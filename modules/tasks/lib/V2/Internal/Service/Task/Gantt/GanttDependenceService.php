<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Gantt;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\DataBase\Tree\LinkExistsException;
use Bitrix\Tasks\V2\Internal\Entity\Task\GanttLink;
use Bitrix\Tasks\V2\Internal\Exception\Task\DescendentException;
use Bitrix\Tasks\V2\Internal\Service\Task\ParentService;

class GanttDependenceService
{
	public function __construct(
		private readonly ParentService $parentService,
		private readonly ScheduleService $scheduleService,
		private readonly GanttLinkService $treeLinkService,
	)
	{

	}

	public function add(GanttLink $ganttLink): void
	{
		if (
			$this->parentService->isDescendantOf((int)$ganttLink->taskId, (int)$ganttLink->dependentId)
			|| $this->parentService->isDescendantOf((int)$ganttLink->dependentId, (int)$ganttLink->taskId)
		)
		{
			throw new DescendentException(Loc::getMessage('TASKS_GANTT_DEPENDENCE_SERVICE_DESCENDENT_ERROR'));
		}

		$this->scheduleService->recountDates((int)$ganttLink->taskId, (int)$ganttLink->creatorId);
		$this->scheduleService->recountDates((int)$ganttLink->dependentId, (int)$ganttLink->creatorId);

		try
		{
			$this->treeLinkService->create($ganttLink);
		}
		catch (LinkExistsException $e)
		{
			$message = Loc::getMessage(
				'TASKS_GANTT_CYCLIC_DEPENDENCE_ERROR',
				[
					'#TASK_ID#' => $ganttLink->taskId,
					'#DEPENDENT_ID#' => $ganttLink->dependentId,
				]
			);

			throw new LinkExistsException($message);
		}

	}

	public function update(GanttLink $ganttLink): void
	{
		$this->treeLinkService->update($ganttLink);
	}

	public function delete(GanttLink $ganttLink): void
	{
		$this->treeLinkService->delete($ganttLink);
	}
}
