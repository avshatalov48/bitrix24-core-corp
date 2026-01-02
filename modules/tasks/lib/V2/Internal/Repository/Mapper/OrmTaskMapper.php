<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\TimeUnitType;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Entity\Task\Duration;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Trait\CastTrait;

class OrmTaskMapper
{
	use CastTrait;

	public function __construct(
		private readonly TaskStatusMapper $taskStatusMapper,
		private readonly TaskPriorityMapper $taskPriorityMapper,
		private readonly TaskMarkMapper $taskMarkMapper,
		private readonly TaskDurationMapper $taskDurationMapper,
	)
	{

	}

	public function mapToObject(Entity\Task $task): TaskObject
	{
		$fields = $this->mapFromEntity($task);

		return TaskObject::wakeUpObject($fields);
	}

	public function mapToEntity(array $fields): Entity\Task
	{
		$entityFields = [];
		if (isset($fields['ID']))
		{
			$entityFields['id'] = $fields['ID'];
		}

		if (isset($fields['TITLE']))
		{
			$entityFields['title'] = $fields['TITLE'];
		}

		if (isset($fields['DESCRIPTION']))
		{
			$entityFields['description'] = $fields['DESCRIPTION'];
		}

		if (isset($fields['STATUS']))
		{
			$entityFields['status'] = $this->taskStatusMapper->mapToEnum((int)($fields['STATUS']))->value;
		}

		if (isset($fields['PRIORITY']) && is_numeric($fields['PRIORITY']))
		{
			$entityFields['priority'] = $this->taskPriorityMapper->mapToEnum((int)$fields['PRIORITY'])->value;
		}

		if (isset($fields['RESPONSIBLE_ID']))
		{
			$entityFields['responsible'] = $this->castMember((int)$fields['RESPONSIBLE_ID']);
		}

		if (isset($fields['START_DATE_PLAN']))
		{
			$entityFields['startPlanTs'] = $this->castDateTime($fields['START_DATE_PLAN']);
		}

		if (isset($fields['END_DATE_PLAN']))
		{
			$entityFields['endPlanTs'] = $this->castDateTime($fields['END_DATE_PLAN']);
		}

		if (isset($fields['CREATED_BY']))
		{
			$entityFields['creator'] = $this->castMember((int)$fields['CREATED_BY']);
		}

		if (isset($fields['STATUS_CHANGED_DATE']))
		{
			$entityFields['statusChangedTs'] = $this->castDateTime($fields['STATUS_CHANGED_DATE']);
		}

		if (isset($fields['STAGE_ID']))
		{
			$entityFields['stage'] = ['id' => (int)$fields['STAGE_ID']];
		}

		if (isset($fields['GROUP_ID']))
		{
			$entityFields['group'] = ['id' => (int)$fields['GROUP_ID']];
		}

		if (isset($fields['PARENT_ID']))
		{
			$entityFields['parent'] = ['id' => (int)$fields['PARENT_ID']];
		}

		if (isset($fields['TASK_CONTROL']))
		{
			$entityFields['needsControl'] = $fields['TASK_CONTROL'] === 'Y' || $fields['TASK_CONTROL'] === true;
		}

		if (isset($fields['DURATION_PLAN']))
		{
			$entityFields['plannedDuration'] = $fields['DURATION_PLAN'];
		}

		if (isset($fields['DURATION_FACT']))
		{
			$entityFields['actualDuration'] = $fields['DURATION_FACT'];
		}

		if (isset($fields['DURATION_TYPE']))
		{
			$entityFields['durationType'] = match($fields['DURATION_TYPE'])
			{
				TimeUnitType::SECOND => Duration::Seconds->value,
				TimeUnitType::MINUTE => Duration::Minutes->value,
				TimeUnitType::HOUR => Duration::Hours->value,
				TimeUnitType::DAY => Duration::Days->value,
				TimeUnitType::WEEK => Duration::Weeks->value,
				TimeUnitType::MONTH => Duration::Months->value,
				TimeUnitType::YEAR => Duration::Years->value,
				default => null,
			};
		}

		if (isset($fields['DATE_START']))
		{
			$entityFields['startedTs'] = $this->castDateTime($fields['DATE_START']);
		}

		if (isset($fields['TIME_ESTIMATE']))
		{
			$entityFields['estimatedTime'] = $this->castDateTime($fields['TIME_ESTIMATE']);
		}

		if (isset($fields['REPLICATE']))
		{
			$entityFields['replicate'] = $fields['REPLICATE'] === 'Y' || $fields['REPLICATE'] === true;
		}

		if (isset($fields['DEADLINE']))
		{
			$entityFields['deadlineTs'] = $this->castDateTime($fields['DEADLINE']);
		}

		if (isset($fields['CREATED_DATE']))
		{
			$entityFields['createdTs'] = $this->castDateTime($fields['CREATED_DATE']);
		}

		if (isset($fields['CHANGED_DATE']))
		{
			$entityFields['changedTs'] = $this->castDateTime($fields['CHANGED_DATE']);
		}

		if (isset($fields['STATUS_CHANGED_BY']))
		{
			$entityFields['statusChangedBy'] = ['id' => (int)$fields['STATUS_CHANGED_BY']];
		}

		if (isset($fields['CLOSED_BY']))
		{
			$entityFields['closedBy'] = ['id' => (int)$fields['CLOSED_BY']];
		}

		if (isset($fields['CHANGED_BY']))
		{
			$entityFields['changedBy'] = ['id' => (int)$fields['CHANGED_BY']];
		}

		if (isset($fields['CLOSED_DATE']))
		{
			$entityFields['closedTs'] = $this->castDateTime($fields['CLOSED_DATE']);
		}

		if (isset($fields['ACTIVITY_DATE']))
		{
			$entityFields['activityTs'] = $this->castDateTime($fields['ACTIVITY_DATE']);
		}

		if (isset($fields['GUID']))
		{
			$entityFields['guid'] = $fields['GUID'];
		}

		if (isset($fields['XML_ID']))
		{
			$entityFields['xmlId'] = $fields['XML_ID'];
		}

		if (isset($fields['EXCHANGE_ID']))
		{
			$entityFields['exchangeId'] = $fields['EXCHANGE_ID'];
		}

		if (isset($fields['EXCHANGE_MODIFIED']))
		{
			$entityFields['exchangeModified'] = $fields['EXCHANGE_MODIFIED'];
		}

		if (isset($fields['OUTLOOK_VERSION']))
		{
			$entityFields['outlookVersion'] = $fields['OUTLOOK_VERSION'];
		}

		if (isset($fields['MARK']))
		{
			$entityFields['mark'] = $this->taskMarkMapper->mapToEnum((string)$fields['MARK'])->value;
		}

		if (isset($fields['ALLOW_CHANGE_DEADLINE']))
		{
			$entityFields['allowsChangeDeadline'] = $fields['ALLOW_CHANGE_DEADLINE'] === 'Y' || $fields['ALLOW_CHANGE_DEADLINE'] === true;
		}

		if (isset($fields['ALLOW_TIME_TRACKING']))
		{
			$entityFields['allowsTimeTracking'] = $fields['ALLOW_TIME_TRACKING'] === 'Y' || $fields['ALLOW_TIME_TRACKING'] === true;
		}

		if (isset($fields['MATCH_WORK_TIME']))
		{
			$entityFields['matchesWorkTime'] = $fields['MATCH_WORK_TIME'] === 'Y' || $fields['MATCH_WORK_TIME'] === true;
		}

		if (isset($fields['ADD_IN_REPORT']))
		{
			$entityFields['addInReport'] = $fields['ADD_IN_REPORT'] === 'Y' || $fields['ADD_IN_REPORT'] === true;
		}

		if (isset($fields['MULTITASK']))
		{
			$entityFields['isMultitask'] = $fields['MULTITASK'] === 'Y' || $fields['MULTITASK'] === true;
		}

		if (isset($fields['SITE_ID']))
		{
			$entityFields['siteId'] = $fields['SITE_ID'];
		}

		if (isset($fields['FORKED_BY_TEMPLATE_ID']))
		{
			$entityFields['forkedByTemplate'] = ['id' => (int)$fields['FORKED_BY_TEMPLATE_ID']];
		}

		if (isset($fields['DEADLINE_COUNTED']))
		{
			$entityFields['deadlineCount'] = $fields['DEADLINE_COUNTED'];
		}

		if (isset($fields['ZOMBIE']))
		{
			$entityFields['isZombie'] = $fields['ZOMBIE'] === 'Y' || $fields['ZOMBIE'] === true;
		}

		if (isset($fields['DECLINE_REASON']))
		{
			$entityFields['declineReason'] = (string)$fields['DECLINE_REASON'];
		}

		if (isset($fields['FORUM_TOPIC_ID']))
		{
			$entityFields['forumTopicId'] = $fields['FORUM_TOPIC_ID'];
		}

		if (isset($fields['FLOW_ID']))
		{
			$entityFields['flow'] = ['id' => (int)$fields['FLOW_ID']];
		}

		if (isset($fields['ACCOMPLICES']) && is_array($fields['ACCOMPLICES']))
		{
			$entityFields['accomplices'] = $this->castMembers($fields['ACCOMPLICES']);
		}

		if (isset($fields['AUDITORS']) && is_array($fields['AUDITORS']))
		{
			$entityFields['auditors'] = $this->castMembers($fields['AUDITORS']);
		}

		if (is_array($fields['TAGS'] ?? null))
		{
			$entityFields['tags'] = array_map(static fn (string $tag): array => ['name' => $tag], $fields['TAGS']);
		}

		if (isset($fields[Entity\UF\UserField::TASK_ATTACHMENTS]))
		{
			$entityFields['fileIds'] = $fields[Entity\UF\UserField::TASK_ATTACHMENTS];
		}

		if (isset($fields[Entity\UF\UserField::TASK_CRM]))
		{
			$entityFields['crmItemIds'] = $fields[Entity\UF\UserField::TASK_CRM];
		}

		if (isset($fields['SE_PARAMETER']) && is_array($fields['SE_PARAMETER']))
		{
			foreach ($fields['SE_PARAMETER'] as $key => $value)
			{
				if (!is_array($value))
				{
					continue;
				}

				$code = (int)($value['CODE'] ?? null);
				$value = $value['VALUE'] ?? null;

				if ($code === ParameterTable::PARAM_SUBTASKS_TIME)
				{
					$entityFields['matchesSubTasksTime'] = $value === 'Y' || $value === true;
				}
				elseif ($code === ParameterTable::PARAM_SUBTASKS_AUTOCOMPLETE)
				{
					$entityFields['autocompleteSubTasks'] = $value === 'Y' || $value === true;
				}
				elseif ($code === ParameterTable::PARAM_RESULT_REQUIRED)
				{
					$entityFields['requireResult'] = $value === 'Y' || $value === true;
				}
				elseif ($code === ParameterTable::PARAM_ALLOW_CHANGE_DATE_PLAN)
				{
					$entityFields['allowsChangeDatePlan'] = $value === 'Y' || $value === true;
				}
			}
		}

		if (isset($fields['DEPENDS_ON']) && is_array($fields['DEPENDS_ON']))
		{
			$entityFields['dependsOn'] = $fields['DEPENDS_ON'];
		}

		return Entity\Task::mapFromArray($entityFields);
	}

	public function mapFromEntity(Entity\Task $task): array
	{
		$fields = [];
		if ($task->id)
		{
			$fields['ID'] = $task->id;
		}
		else
		{
			$fields['DESCRIPTION_IN_BBCODE'] = true; // true for new tasks, ignore for existing task
		}

		if ($task->title !== null && $task->title !== '')
		{
			$fields['TITLE'] = $task->title;
		}
		if ($task->description || $task->description === '')
		{
			$fields['DESCRIPTION'] = $task->description;
		}
		if ($task->status)
		{
			$fields['STATUS'] = $this->taskStatusMapper->mapFromEnum($task->status);
		}

		if ($task->priority)
		{
			$fields['PRIORITY'] = $this->taskPriorityMapper->mapFromEnum($task->priority);
		}

		if ($task->responsible?->id)
		{
			$fields['RESPONSIBLE_ID'] = $task->responsible->id;
		}

		if ($task->startPlanTs)
		{
			$fields['START_DATE_PLAN'] = DateTime::createFromTimestamp($task->startPlanTs);
		}

		if ($task->startPlanTs === 0)
		{
			$fields['START_DATE_PLAN'] = 0;
		}

		if ($task->endPlanTs)
		{
			$fields['END_DATE_PLAN'] = DateTime::createFromTimestamp($task->endPlanTs);
		}

		if ($task->endPlanTs === 0)
		{
			$fields['END_DATE_PLAN'] = 0;
		}

		if ($task->creator?->id)
		{
			$fields['CREATED_BY'] = $task->creator->id;
		}

		if ($task->statusChangedTs)
		{
			$fields['STATUS_CHANGED_DATE'] = DateTime::createFromTimestamp($task->statusChangedTs);
		}

		if ($task->statusChangedTs === 0)
		{
			$fields['STATUS_CHANGED_DATE'] = 0;
		}

		if ($task->stage)
		{
			$fields['STAGE_ID'] = $task->stage->id;
		}

		if ($task->group)
		{
			$fields['GROUP_ID'] = $task->group->id;
		}

		if ($task->parent)
		{
			$fields['PARENT_ID'] = $task->parent->id;
		}

		if ($task->needsControl !== null)
		{
			$fields['TASK_CONTROL'] = $task->needsControl;
		}

		if ($task->plannedDuration)
		{
			$fields['DURATION_PLAN'] = $task->plannedDuration;
		}

		if ($task->actualDuration)
		{
			$fields['DURATION_FACT'] = $task->actualDuration;
		}

		if ($task->durationType)
		{
			$fields['DURATION_TYPE'] = $this->taskDurationMapper->mapFromEnum($task->durationType);
		}

		if ($task->startedTs)
		{
			$fields['DATE_START'] = DateTime::createFromTimestamp($task->startedTs);
		}

		if ($task->startedTs === 0)
		{
			$fields['DATE_START'] = 0;
		}

		if ($task->estimatedTime)
		{
			$fields['TIME_ESTIMATE'] = $task->estimatedTime;
		}

		if ($task->replicate !== null)
		{
			$fields['REPLICATE'] = $task->replicate;
		}

		if ($task->deadlineTs)
		{
			$fields['DEADLINE'] = DateTime::createFromTimestamp($task->deadlineTs);
		}

		if ($task->deadlineTs === 0)
		{
			$fields['DEADLINE'] = 0;
		}

		if ($task->createdTs)
		{
			$fields['CREATED_DATE'] = DateTime::createFromTimestamp($task->createdTs);
		}

		if ($task->changedTs)
		{
			$fields['CHANGED_DATE'] = DateTime::createFromTimestamp($task->changedTs);
		}

		if ($task->changedTs === 0)
		{
			$fields['CHANGED_DATE'] = 0;
		}

		if ($task->statusChangedBy?->id)
		{
			$fields['STATUS_CHANGED_BY'] = $task->statusChangedBy->id;
		}

		if ($task->closedBy?->id)
		{
			$fields['CLOSED_BY'] = $task->closedBy->id;
		}

		if ($task->changedBy?->id)
		{
			$fields['CHANGED_BY'] = $task->changedBy->id;
		}

		if ($task->closedTs)
		{
			$fields['CLOSED_DATE'] = DateTime::createFromTimestamp($task->closedTs);
		}

		if ($task->closedTs === 0)
		{
			$fields['CLOSED_DATE'] = 0;
		}

		if ($task->activityTs)
		{
			$fields['ACTIVITY_DATE'] = DateTime::createFromTimestamp($task->activityTs);
		}

		if ($task->guid)
		{
			$fields['GUID'] = $task->guid;
		}

		if ($task->xmlId)
		{
			$fields['XML_ID'] = $task->xmlId;
		}

		if ($task->exchangeId)
		{
			$fields['EXCHANGE_ID'] = $task->exchangeId;
		}

		if ($task->exchangeModified)
		{
			$fields['EXCHANGE_MODIFIED'] = $task->exchangeModified;
		}

		if ($task->outlookVersion)
		{
			$fields['OUTLOOK_VERSION'] = $task->outlookVersion;
		}

		if ($task->mark)
		{
			$fields['MARK'] = $this->taskMarkMapper->mapFromEnum($task->mark);
		}

		if ($task->allowsChangeDeadline !== null)
		{
			$fields['ALLOW_CHANGE_DEADLINE'] = $task->allowsChangeDeadline;
		}

		if ($task->allowsTimeTracking !== null)
		{
			$fields['ALLOW_TIME_TRACKING'] = $task->allowsTimeTracking;
		}

		if ($task->matchesWorkTime !== null)
		{
			$fields['MATCH_WORK_TIME'] = $task->matchesWorkTime;
		}

		if ($task->addInReport !== null)
		{
			$fields['ADD_IN_REPORT'] = $task->addInReport;
		}

		if ($task->isMultitask !== null)
		{
			$fields['MULTITASK'] = $task->isMultitask;
		}

		if ($task->siteId)
		{
			$fields['SITE_ID'] = $task->siteId;
		}

		if ($task->forkedByTemplate?->id)
		{
			$fields['FORKED_BY_TEMPLATE_ID'] = $task->forkedByTemplate->id;
		}

		if ($task->deadlineCount)
		{
			$fields['DEADLINE_COUNTED'] = $task->deadlineCount;
		}

		if ($task->isZombie !== null)
		{
			$fields['ZOMBIE'] = $task->isZombie;
		}

		if ($task->declineReason)
		{
			$fields['DECLINE_REASON'] = $task->declineReason;
		}

		if ($task->forumTopicId)
		{
			$fields['FORUM_TOPIC_ID'] = $task->forumTopicId;
		}

		if ($task->flow)
		{
			$fields['FLOW_ID'] = $task->flow->id;
		}

		if ($task->accomplices)
		{
			$fields['ACCOMPLICES'] = $task->accomplices->getIdList();
		}

		if ($task->auditors)
		{
			$fields['AUDITORS'] = $task->auditors->getIdList();
		}

		if ($task->tags)
		{
			$fields['TAGS'] = $task->tags->getNameList();
		}

		if ($task->fileIds !== null)
		{
			$fields[Entity\UF\UserField::TASK_ATTACHMENTS] = $task->fileIds;
		}

		if ($task->crmItemIds !== null)
		{
			$fields[Entity\UF\UserField::TASK_CRM] = $task->crmItemIds;
		}

		if ($task->userFields)
		{
			foreach ($task->userFields as $userField)
			{
				$fields[$userField->key] = $userField->value;
			}
		}

		if ($task->matchesSubTasksTime !== null)
		{
			$fields['SE_PARAMETER'] ??= [];
			$fields['SE_PARAMETER'][] = [
				'CODE' => ParameterTable::PARAM_SUBTASKS_TIME,
				'VALUE' => $task->matchesSubTasksTime ? 'Y' : 'N',
			];
		}

		if ($task->requireResult !== null)
		{
			$fields['SE_PARAMETER'] ??= [];
			$fields['SE_PARAMETER'][] = [
				'CODE' => ParameterTable::PARAM_RESULT_REQUIRED,
				'VALUE' => $task->requireResult ? 'Y' : 'N',
			];
		}

		if ($task->autocompleteSubTasks !== null)
		{
			$fields['SE_PARAMETER'] ??= [];
			$fields['SE_PARAMETER'][] = [
				'CODE' => ParameterTable::PARAM_SUBTASKS_AUTOCOMPLETE,
				'VALUE' => $task->autocompleteSubTasks ? 'Y' : 'N',
			];
		}

		if ($task->allowsChangeDatePlan !== null)
		{
			$fields['SE_PARAMETER'] ??= [];
			$fields['SE_PARAMETER'][] = [
				'CODE' => ParameterTable::PARAM_ALLOW_CHANGE_DATE_PLAN,
				'VALUE' => $task->allowsChangeDatePlan ? 'Y' : 'N',
			];
		}

		if ($task->dependsOn !== null)
		{
			$fields['DEPENDS_ON'] = $task->dependsOn;
		}

		return $fields;
	}
}
