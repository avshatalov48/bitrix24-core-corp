<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

use Bitrix\Tasks\Internals\Notification\Message;

class TaskCreated extends BaseCase
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

		$taskId = $task->getId();
		$logDate = $DB->CurrentTimeFunction();
		$curTimeTimestamp = time() + \CTimeZone::GetOffset();

		$arSoFields = !$task->isCrm()
			? ['EVENT_ID' => 'tasks', 'TITLE' => $task->getTitle(), 'MESSAGE' => '', 'MODULE_ID' => 'tasks']
			: [];

		if ($task->getCreatedDate())
		{
			$createdDateTimestamp = MakeTimeStamp($task->getCreatedDate(), $this->getDateFormat());

			if ($createdDateTimestamp > $curTimeTimestamp)
			{
				$logDate = \Bitrix\Tasks\Util\Db::charToDateFunction(
					$task->getCreatedDate(),
					'FULL',
					SITE_ID
				);
			}
		}

		$arSoFields['TEXT_MESSAGE'] = GetMessage('TASKS_SONET_NEW_TASK_MESSAGE');

		if ($task->getGroupId())
		{
			$arSoFields['ENTITY_TYPE'] = SONET_ENTITY_GROUP;
			$arSoFields['ENTITY_ID'] = $task->getGroupId();
		}
		else
		{
			$arSoFields['ENTITY_TYPE'] = SONET_ENTITY_USER;
			$arSoFields['ENTITY_ID'] = $task->getCreatedBy();
		}

		$arParamsLog = [
			'TYPE' => 'create',
			'CREATED_BY' => $message->getSender()->getId(),
			'PREV_REAL_STATUS' => $task->getRealStatus()
		];

		$arSoFields['PARAMS'] = serialize($arParamsLog);

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

		$rsSocNetLogItems = \CSocNetLog::GetList(
			['ID' => 'DESC'],
			$arLogFilter,
			false,
			false,
			['ID', 'ENTITY_TYPE', 'ENTITY_ID']
		);

		if ($rsSocNetLogItems->Fetch())
		{
			return;
		}

		$arSoFields['=LOG_DATE']       = $logDate;
		$arSoFields['CALLBACK_FUNC']   = false;
		$arSoFields['SOURCE_ID']       = $taskId;
		$arSoFields['ENABLE_COMMENTS'] = 'Y';
		$arSoFields['URL']             = ''; // url is user-specific, cant keep in database
		$arSoFields['USER_ID']         = $task->getCreatedBy();
		$arSoFields['TITLE_TEMPLATE']  = '#TITLE#';

		// Set all sites because any user from any site may be
		// added to task in future. For example, new auditor, etc.
		$arSoFields['SITE_ID'] = $this->getSiteIds();

		$logId = (int)\CSocNetLog::Add($arSoFields, false);
		if ($logId > 0)
		{
			$logFields = [
				'TMP_ID' => $logId,
				'TAG' => [],
			];

			$tagsResult = \CTaskTags::getList([], ['TASK_ID' => $taskId]);
			while ($row = $tagsResult->fetch())
			{
				$logFields['TAG'][] = $row['NAME'];
			}

			\CSocNetLog::Update($logId, $logFields);

			$taskMembers = $message->getMetaData()->getUserRepository()->getRecepients(
				$task,
				$message->getSender()
			);
			$rights = $this->recepients2Rights($taskMembers);

			if ($task->getGroupId())
			{
				$rights = array_merge(
					$rights,
					['SG' . $task->getGroupId()]
				);
			}

			\CSocNetLogRights::Add($logId, $rights);
			\CSocNetLog::SendEvent($logId, 'SONET_NEW_EVENT', $logId);
		}
	}
}