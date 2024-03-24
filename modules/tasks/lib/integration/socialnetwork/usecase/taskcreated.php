<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Socialnetwork\Space\SpaceService;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\TaskObject;

class TaskCreated extends BaseCase
{
	private static array $ufStorage = [];
	private static array $logStorage = [];

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

		// I think this condition is ambiguous, so I comment that
		// Check that user exists
		// $rsUser = \CUser::GetList(
		// 	'ID',
		// 	'ASC',
		// 	['ID' => $task->getCreatedBy()],
		// 	['FIELDS' => ['ID']]
		// );
		//
		// if (!($arUser = $rsUser->Fetch()))
		// {
		// 	return;
		// }

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
			$ufDocID = $this->getUserFieldValue($task);
			if ($ufDocID)
			{
				$arSoFields['UF_SONET_LOG_DOC'] = $ufDocID;
			}
		}

		if ($this->getLogByTask($task))
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
			$this->invalidateLog($task);
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


			if (SpaceService::useNotificationStrategy())
			{
				if ($task->getGroupId())
				{
					$this->addGroupRights($message, $task->getGroupId(), $logId);
				}

				$this->addUserRights($message, $message->getSender(), $logId);
				$this->addUserRights($message, $message->getRecepient(), $logId);
			}
			else
			{
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
			}

			\CSocNetLog::SendEvent($logId, 'SONET_NEW_EVENT', $logId);
		}
	}

	private function getUserFieldValue(TaskObject $task): mixed
	{
		if (!isset(static::$ufStorage[$task->getId()]))
		{
			try
			{
				static::$ufStorage[$task->getId()] = $task->getFileFields();
			}
			catch (SystemException $exception)
			{
				LogFacade::logThrowable($exception);
				static::$ufStorage[$task->getId()] = [];
			}
		}

		return static::$ufStorage[$task->getId()];
	}

	private function getLogByTask(TaskObject $task): mixed
	{
		if (!isset(static::$logStorage[$task->getId()]))
		{
			static::$logStorage[$task->getId()] = \CSocNetLog::GetList(
				['ID' => 'DESC'],
				$this->getSonetLogFilter($task->getId(), $task->isCrm()),
				false,
				false,
				['ID', 'ENTITY_TYPE', 'ENTITY_ID']
			)->Fetch();
		}

		return static::$logStorage[$task->getId()];
	}

	private function invalidateLog(TaskObject $task): void
	{
		unset(static::$logStorage[$task->getId()]);
	}
}