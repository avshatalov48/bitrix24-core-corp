<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\CrmItemRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\TaskMapper;
use Bitrix\Tasks\V2\Internal\Repository\Task\Select;

class TaskReadRepository implements TaskReadRepositoryInterface
{
	public function __construct(
		private readonly GroupRepositoryInterface $groupRepository,
		private readonly FlowRepositoryInterface  $flowRepository,
		private readonly StageRepositoryInterface $stageRepository,
		private readonly UserRepositoryInterface  $userRepository,
		private readonly CheckListRepository      $checkListRepository,
		private readonly ChatRepositoryInterface  $chatRepository,
		private readonly TaskParameterRepositoryInterface $taskParameterRepository,
		private readonly CrmItemRepositoryInterface $crmItemRepository,
		private readonly TaskUserOptionRepositoryInterface $userOptionRepository,
		private readonly SubTaskRepositoryInterface $subTaskRepository,
		private readonly RelatedTaskRepositoryInterface $relatedTaskRepository,
		private readonly TaskTagRepositoryInterface $taskTagRepository,
		private readonly GanttLinkRepositoryInterface $ganttLinkRepository,
		private readonly TaskMapper               $taskMapper,
	)
	{
	}

	public function getById(int $id, ?Select $select = null): ?Entity\Task
	{
		$selectFields = [
			'ID',
			'TITLE',
			'PARENT_ID',
			'GROUP_ID',
			'STAGE_ID',
			'STATUS',
			'STATUS_CHANGED_DATE',
			'ALLOW_CHANGE_DEADLINE',
			'ALLOW_TIME_TRACKING',
			'MATCH_WORK_TIME',
			'DEADLINE',
			'TASK_CONTROL',
			'PRIORITY',
			'DESCRIPTION',
			'FORUM_TOPIC_ID',
			'RESPONSIBLE_ID',
			'CREATED_BY',
			'CLOSED_DATE',
			'CREATED_DATE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
			'SITE_ID',
		];

		$select ??= new Select();

		if ($select->flow)
		{
			$selectFields[] = 'FLOW_TASK.FLOW_ID';
		}

		if ($select->members)
		{
			$selectFields[] = 'MEMBER_LIST';
		}

		if ($select->favorite)
		{
			$selectFields[] = 'FAVORITE_TASK';
		}

		$task =
			TaskTable::query()
				->setSelect($selectFields)
				->where('ID', $id)
				->fetchObject()
			;

		if ($task === null)
		{
			return null;
		}

		$tags = null;
		if ($select->tags)
		{
			$tags = $this->taskTagRepository->getById($id);
		}

		$group = null;
		if ($select->group && $task->getGroupId() > 0)
		{
			$group = $this->groupRepository->getById($task->getGroupId());
		}

		$flow = null;
		if ($select->flow && $task->getFlowTask()?->getId() > 0)
		{
			$flow = $this->flowRepository->getById($task->getFlowTask()->getId());
		}

		$stage = null;
		if ($select->stage && $task->getStageId() > 0)
		{
			$stage = $this->stageRepository->getById($task->getStageId());
		}

		$members = null;
		if ($select->members)
		{
			$memberIds = array_merge($task->getMemberList()->getUserIdList(), [$task->getCreatedBy(), $task->getResponsibleId()]);

			Collection::normalizeArrayValuesByInt($memberIds, false);

			$members = $this->userRepository->getByIds($memberIds);
		}

		$checkListIds = null;
		if ($select->checkLists)
		{
			$checkListIds = $this->checkListRepository->getIdsByEntity($id, Entity\CheckList\Type::Task);
		}

		$chatId = null;
		if ($select->chat)
		{
			$chatId = $this->chatRepository->getChatIdByTaskId($id);
		}

		$crmItemIds = null;
		if ($select->crm)
		{
			$crmItemIds = $this->crmItemRepository->getIdsByTaskId($id);
		}

		$containsSubTasks = false;
		if ($select->subTasks)
		{
			$containsSubTasks = $this->subTaskRepository->containsSubTasks($id);
		}

		$containsRelatedTasks = false;
		if ($select->relatedTasks)
		{
			$containsRelatedTasks = $this->relatedTaskRepository->containsRelatedTasks($id);
		}

		$containsGanttLinks = false;
		if ($select->gantt)
		{
			$containsGanttLinks = $this->ganttLinkRepository->containsLinks($id);
		}

		$aggregates = [
			'containsCheckList' => !empty($checkListIds),
			'containsSubTasks' => $containsSubTasks,
			'containsRelatedTasks' => $containsRelatedTasks,
			'containsGanttLinks' => $containsGanttLinks,
		];

		$userOptions = null;
		if ($select->options)
		{
			$userOptions = $this->userOptionRepository->get($id);
		}

		$taskParameters = null;
		if ($select->parameters)
		{
			$taskParameters = [
				'matchesSubTasksTime' => $this->taskParameterRepository->matchesSubTasksTime($id),
				'allowsChangeDatePlan' => $this->taskParameterRepository->allowsChangeDatePlan($id),
			];
		}

		return $this->taskMapper->mapToEntity(
			taskObject: $task,
			group: $group,
			flow: $flow,
			stage: $stage,
			members: $members,
			aggregates: $aggregates,
			chatId: $chatId,
			checkListIds: $checkListIds,
			crmItemIds: $crmItemIds,
			taskParameters: $taskParameters,
			userOptions: $userOptions,
			tags: $tags,
		);
	}

	public function getAttachmentIds(int $taskId): array
	{
		$row =
			TaskTable::query()
				->setSelect(['ID', Entity\UF\UserField::TASK_ATTACHMENTS])
				->where('ID', $taskId)
				->fetch()
		;

		if (!is_array($row))
		{
			return [];
		}

		$value = $row[Entity\UF\UserField::TASK_ATTACHMENTS] ?? null;

		if (!is_array($value))
		{
			return [];
		}

		return $value;
	}
}
