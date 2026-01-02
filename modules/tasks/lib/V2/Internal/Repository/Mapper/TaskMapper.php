<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\V2\Internal\Entity;

class TaskMapper
{
	public function __construct(
		private readonly TaskStatusMapper $taskStatusMapper,
		private readonly TaskPriorityMapper $taskPriorityMapper,
	)
	{

	}

	public function mapToEntity(
		TaskObject $taskObject,
		?Entity\Group $group = null,
		?Entity\Flow $flow = null,
		?Entity\Stage $stage = null,
		?Entity\UserCollection $members = null,
		array $aggregates = [],
		?int $chatId = null,
		?array $checkListIds = null,
		?array $crmItemIds = null,
		?array $taskParameters = null,
		?Entity\Task\UserOptionCollection $userOptions = null,
		?Entity\TagCollection $tags = null,
	): Entity\Task
	{
		return new Entity\Task(
			id: $taskObject->getId(),
			title: $taskObject->getTitle(),
			description: $taskObject->getDescription(),
			creator: $members?->findOneById($taskObject->getCreatedBy()),
			createdTs: $taskObject->getCreatedDate()?->getTimestamp(),
			responsible: $members?->findOneById($taskObject->getResponsibleId()),
			deadlineTs: $taskObject->getDeadline()?->getTimestamp(),
			needsControl: $taskObject->getTaskControl(),
			startPlanTs: $taskObject->getStartDatePlan()?->getTimestamp(),
			endPlanTs: $taskObject->getEndDatePlan()?->getTimestamp(),
			fileIds: $taskObject->getFileFields(),
			parentId: $taskObject->getParentId(),
			checklist: $checkListIds,
			group: $group,
			stage: $stage,
			flow: $flow,
			priority: $this->taskPriorityMapper->mapToEnum((int)$taskObject->getPriority()),
			status: $this->taskStatusMapper->mapToEnum((int)$taskObject->getStatus()),
			statusChangedTs: $taskObject->getStatusChangedDate()?->getTimestamp(),
			accomplices: $members?->findAllByIds($taskObject->getMemberList()->getAccompliceIds()),
			auditors: $members?->findAllByIds($taskObject->getMemberList()->getAuditorIds()),
			containsChecklist: $aggregates['containsCheckList'] ?? null,
			containsSubTasks: $aggregates['containsSubTasks'] ?? null,
			containsRelatedTasks: $aggregates['containsRelatedTasks'] ?? null,
			containsGanttLinks: $aggregates['containsGanttLinks'] ?? null,
			chatId: $chatId,
			allowsChangeDeadline: $taskObject->getAllowChangeDeadline(),
			matchesWorkTime: $taskObject->getMatchWorkTime(),
			siteId: $taskObject->getSiteId(),
			tags: $tags,
			crmItemIds: $crmItemIds,
			matchesSubTasksTime: $taskParameters['matchesSubTasksTime'] ?? null,
			allowsChangeDatePlan: $taskParameters['allowsChangeDatePlan'] ?? null,
			inFavorite: $taskObject->getFavoriteTask()?->getUserIdList(),
			inPin: $userOptions?->filter(fn (Entity\Task\UserOption $option): bool => $option->code === Option::PINNED)->getUserIdList(),
			inGroupPin: $userOptions?->filter(fn (Entity\Task\UserOption $option): bool => $option->code === Option::PINNED_IN_GROUP)->getUserIdList(),
			inMute: $userOptions?->filter(fn (Entity\Task\UserOption $option): bool => $option->code === Option::MUTED)->getUserIdList(),
		);
	}
}
