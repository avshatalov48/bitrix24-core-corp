<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Socialnetwork\Space\SpaceService;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\UserOption;

class TaskUpdated extends BaseCase
{
	public function execute(Message $message): void
	{
		// TODO: this code was moved from classes/tasksnotifications and needs reraftoring
		global $DB;

		$task = $message->getMetaData()->getTask();
		$arLogFilter = $this->getSonetLogFilter($task->getId(), $task->isCrm());

		if (empty($arLogFilter))
		{
			return;
		}

		// Check that user exists
		$rsUser = \CUser::GetList(
			'ID',
			'ASC',
			['ID' => $task->getCreatedBy()],
			['FIELDS' => ['ID']]
		);

		if (!($arUser = $rsUser->Fetch()))
		{
			return;
		}

		$changes = $message->getMetaData()->getChanges();
		$previosFields = $message->getMetaData()->getPreviousFields();

		if (empty($changes))
		{
			$rsSocNetLogItems = \CSocNetLog::GetList(
				['ID' => 'DESC'],
				$arLogFilter,
				false,
				false,
				['ID', 'ENTITY_TYPE', 'ENTITY_ID']
			);

			while ($log = $rsSocNetLogItems->Fetch())
			{
				$logId = (int)$log['ID'];
				$authorId = $task->getCreatedBy();

				$oldForumTopicId = $previosFields['FORUM_TOPIC_ID'] ?? null;
				$newForumTopicId = $task->getForumTopicId();
				$forumTopicAdded = $oldForumTopicId == 0 && isset($newForumTopicId) && $newForumTopicId > 0;

				// Add author to list of users that view log about task in livefeed
				// But only when some other person change task
				// or if added FORUM_TOPIC_ID
				if (($authorId !== $message->getSender()->getId()) || $forumTopicAdded)
				{
					$authorGroupCode = 'U'.$authorId;

					$rightsResult = \CSocNetLogRights::GetList([], [
						'LOG_ID' => $logId,
						'GROUP_CODE' => $authorGroupCode,
					]);

					// If task's author hasn't rights yet, give them
					if (!$rightsResult->fetch())
					{
						$follow = !UserOption::isOptionSet($task->getId(), $authorId, (UserOption\Option::MUTED));
						\CSocNetLogRights::Add($logId, [$authorGroupCode], false, $follow);
					}
				}
			}

			return;
		}

		if (count($changes) === 1 && isset($changes['STATUS']))
		{
			return;	// if only status changes - don't send message, because it will be sent by SendStatusMessage()
		}


		$taskId = $task->getId();
		$logDate = $DB->CurrentTimeFunction();
		$curTimeTimestamp = time() + \CTimeZone::GetOffset();

		$arSoFields = !$task->isCrm()
			? ['EVENT_ID' => 'tasks', 'TITLE' => $task->getTitle(), 'MESSAGE' => '', 'MODULE_ID' => 'tasks']
			: [];


		if ($task->getChangedDate())
		{
			$createdDateTimestamp = MakeTimeStamp($task->getChangedDate(), $this->getDateFormat());

			if ($createdDateTimestamp > $curTimeTimestamp)
			{
				$logDate = \Bitrix\Tasks\Util\Db::charToDateFunction(
					$task->getChangedDate(),
					'FULL',
					SITE_ID
				);
			}
		}

		$arChangesFields = array_keys($changes);
		$arSoFields['TEXT_MESSAGE'] = str_replace(
			'#CHANGES#',
			implode(
				', ',
				$this->fieldsToNames($arChangesFields)
			),
			Loc::getMessage('TASKS_SONET_TASK_CHANGED_MESSAGE')
		);

		if (!$task->isCrm())
		{
			$prevGroupId = (int)$previosFields['GROUP_ID'] ?? null;
			if ($task->getGroupId() || $task->getGroupId() !== $prevGroupId)
			{
				$arSoFields['ENTITY_TYPE'] = SONET_ENTITY_GROUP;
				$arSoFields['ENTITY_ID'] = $task->getGroupId() ? $task->getGroupId() : $prevGroupId;
			}
			else
			{
				$arSoFields['ENTITY_TYPE'] = SONET_ENTITY_USER;
				$arSoFields['ENTITY_ID'] = $task->getCreatedBy();
			}
		}

		$arSoFields['PARAMS'] = serialize([
			'TYPE' => 'modify',
			'CHANGED_FIELDS' => $arChangesFields,
			'CREATED_BY'  => $task->getCreatedBy(),
			'CHANGED_BY' => ($message->getSender()->getId() ?: $task->getChangedBy()),
			'PREV_REAL_STATUS' => ($previosFields['REAL_STATUS'] ?? false),
		]);

		// rating entity id (ilike)
		$arSoFields['RATING_ENTITY_ID'] =  $taskId;
		$arSoFields['RATING_TYPE_ID'] = 'TASK';

		if (IsModuleInstalled('webdav') || IsModuleInstalled('disk'))
		{
			$ufDocID = $GLOBALS['USER_FIELD_MANAGER']->GetUserFieldValue('TASKS_TASK', 'UF_TASK_WEBDAV_FILES', $taskId, LANGUAGE_ID);
			if ($ufDocID)
			{
				$arSoFields['UF_SONET_LOG_DOC'] = $ufDocID;
			}
		}

		// Update existing log item
		$arSoFields['=LOG_DATE']   = $logDate;
		$arSoFields['=LOG_UPDATE'] = $logDate;

		// All tasks posts in live feed should be from director
		$arSoFields['USER_ID'] = $task->getCreatedBy();
		$rsSocNetLogItems = \CSocNetLog::GetList(
			['ID' => 'DESC'],
			$arLogFilter,
			false,
			false,
			['ID', 'ENTITY_TYPE', 'ENTITY_ID']
		);

		while ($log = $rsSocNetLogItems->Fetch())
		{
			$logId = (int)$log['ID'];

			$arSoFields['TAG'] = [];
			$tagsResult = \CTaskTags::getList([], ['TASK_ID' => $taskId]);
			while ($tag = $tagsResult->fetch())
			{
				$arSoFields['TAG'][] = $tag['NAME'];
			}

			\CSocNetLog::Update($logId, $arSoFields);

			$params = [
				'LOG_ID' => $logId,
				'EFFECTIVE_USER_ID' => $message->getSender()->getId(),
			];
			SpaceService::useNotificationStrategy()
				? $this->setSonetLogRights($message, $params, $task)
				: $this->setSonetLogRightsEx($message, $params, $task, $previosFields);
		}
	}

	private function fieldsToNames(array $arFields): array
	{
		$arFields = array_unique(array_filter($arFields));
		$locMap = [
			'NEW_FILES' => 'FILES',
			'DELETED_FILES' => 'FILES',
			'START_DATE_PLAN' => 'START_DATE_PLAN',
			'END_DATE_PLAN' => 'END_DATE_PLAN',
		];
		$arNames = [];
		foreach($arFields as $field)
		{
			$field = $locMap[$field] ?? $field;
			$arNames[] = Loc::getMessage('TASKS_SONET_LOG_' . $field);
		}

		return array_unique(array_filter($arNames));
	}

	private function setSonetLogRights(Message $message, array $params, TaskObject $task): void
	{
		$logId = (int)$params['LOG_ID'];
		$effectiveUserId = (int)$params['EFFECTIVE_USER_ID'];

		if ($logId <= 0 || $effectiveUserId <= 0)
		{
			return;
		}

		if ($task->getGroupId() > 0)
		{
			$this->addGroupRights($message, $task->getGroupId(), $logId);
		}

		$this->addUserRights($message, $message->getSender(), $logId);
		$this->addUserRights($message, $message->getRecepient(), $logId);
		\CSocNetLogRights::deleteByLogID($logId);
	}

	private function setSonetLogRightsEx(Message $message, array $params, TaskObject $task, array $previousFields): void
	{
		// TODO: this code was moved from classes/tasksnotifications and needs reraftoring
		$logId = (int)$params['LOG_ID'];
		$effectiveUserId = (int)$params['EFFECTIVE_USER_ID'];

		if ($logId <= 0 || $effectiveUserId <= 0)
		{
			return;
		}

		// Get current rights
		$currentRights = [];
		$rightsResult = \CSocNetLogRights::getList([], ['LOG_ID' => $logId]);
		while ($right = $rightsResult->fetch())
		{
			$currentRights[] = $right['GROUP_CODE'];
		}

		$taskMembers = $message->getMetaData()->getUserRepository()->getRecepients(
			$task,
			$message->getSender()
		);
		$newRights = $this->recepients2Rights($taskMembers);

		$oldGroupId = $previousFields['GROUP_ID'] ?? null;
		$newGroupId = $task->getGroupId();
		$groupChanged = (isset($newGroupId, $oldGroupId) && $newGroupId && $newGroupId !== (int)$oldGroupId);

		// If rights really changed, update them
		if (
			$groupChanged
			|| !empty(array_diff($currentRights, $newRights))
			|| !empty(array_diff($newRights, $currentRights))
		)
		{
			$groupRights = [];
			if ($newGroupId)
			{
				$groupRights = $this->prepareRightsCodesForViewInGroupLiveFeed($newGroupId);
			}
			elseif (isset($oldGroupId))
			{
				$groupRights = $this->prepareRightsCodesForViewInGroupLiveFeed($oldGroupId);
			}

			\CSocNetLogRights::deleteByLogID($logId);

			foreach ($taskMembers as $user)
			{
				$code = $this->recepients2Rights([$user]);
				$follow = !UserOption::isOptionSet($previousFields['ID'], $user->getId(), UserOption\Option::MUTED);

				\CSocNetLogRights::add($logId, $code, false, $follow);
			}
			if (!empty($groupRights))
			{
				\CSocNetLogRights::add($logId, $groupRights);
			}
		}
	}

	private function prepareRightsCodesForViewInGroupLiveFeed(?int $groupId): array
	{
		return ($groupId)
			? ['SG' . $groupId]
			: [];
	}
}