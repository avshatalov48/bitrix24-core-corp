<?php

namespace Bitrix\Tasks\Integration\SocialNetwork\UseCase;

use Bitrix\Tasks\Integration\Socialnetwork\Space\SpaceService;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Util\User;

class TaskStatusChanged extends BaseCase
{
	public function execute(Message $message): void
	{
		// TODO: get rid of global variables
		global $DB;

		$task = $message->getMetaData()->getTask();
		$taskCurrentStatus = $message->getMetaData()->getParams()['task_current_status'] ?? $task->getRealStatus();

		$text = ($taskCurrentStatus == \CTasks::STATE_PENDING)
			? GetMessage('TASKS_SONET_TASK_STATUS_MESSAGE_' . \CTasks::STATE_NEW)
			: GetMessage('TASKS_SONET_TASK_STATUS_MESSAGE_' . $taskCurrentStatus);

		if ($taskCurrentStatus == \CTasks::STATE_DECLINED)
		{
			$text = str_replace('#TASK_DECLINE_REASON#', $task->getDeclineReason(), $text);
		}

		$realStatus = $task->getRealStatus();

		$arSoFields = [
			'TITLE' => $task->getTitle(),
			'=LOG_UPDATE' => (
			$task->getChangedDate() <> ''?
				(MakeTimeStamp($task->getChangedDate(), \CSite::GetDateFormat("FULL", SITE_ID)) > time()+\CTimeZone::GetOffset()?
					\Bitrix\Tasks\Util\Db::charToDateFunction($task->getChangedDate(), "FULL", SITE_ID) :
					$DB->CurrentTimeFunction()) :
				$DB->CurrentTimeFunction()
			),
			'MESSAGE' => '',
			'TEXT_MESSAGE' => $text,
			'PARAMS' => serialize(
				array(
					'TYPE' => 'status',
					'CHANGED_BY' => $message->getSender()->getId(),
					'PREV_REAL_STATUS' => $realStatus ?? false
				)
			)
		];

		$arSoFields['=LOG_DATE'] = $arSoFields['=LOG_UPDATE'];

		// All tasks posts in live feed should be from director
		if (isset($params['CREATED_BY']))
		{
			$arSoFields['USER_ID'] = $params['CREATED_BY'];
		}

		$loggedInUserId = false;
		if (User::getId())
		{
			$loggedInUserId = User::getId();
		}

		$arLogFilter = $this->getSonetLogFilter($task->getId(), $task->isCrm());

		if (empty($arLogFilter))
		{
			return;
		}

		$dbRes = \CSocNetLog::GetList(
			['ID' => 'DESC'],
			$arLogFilter,
			false,
			false,
			['ID', 'ENTITY_TYPE', 'ENTITY_ID']
		);
		while ($log = $dbRes->Fetch())
		{
			if (SpaceService::useNotificationStrategy())
			{
				\CSocNetLog::Update($log['ID'], $arSoFields);
			}
			else
			{
				$logId = $log['ID'];
				$authorId = $task->getCreatedBy();

				\CSocNetLog::Update($logId, $arSoFields);

				// Add author to list of users that view log about task in livefeed
				// But only when some other person change task
				if ($authorId !== $loggedInUserId)
				{
					$authorGroupCode = 'U'.$authorId;

					$rightsResult = \CSocNetLogRights::GetList([], [
						'LOG_ID' => $logId,
						'GROUP_CODE' => $authorGroupCode,
					]);

					// If task's author hasn't rights yet, give them
					if (!$rightsResult->fetch())
					{
						$follow = !UserOption::isOptionSet($task->getId(), $authorId, UserOption\Option::MUTED);
						\CSocNetLogRights::Add($logId, [$authorGroupCode], false, $follow);
					}
				}
			}
		}
	}
}