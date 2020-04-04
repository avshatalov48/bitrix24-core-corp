<?php
namespace Bitrix\Timeman\UseCase\Schedule\Assignment\Delete;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\UseCase\Schedule\BaseScheduleHandler;

class Handler extends BaseScheduleHandler
{
	public function deleteUsers($scheduleId, $userIds)
	{
		if (!$this->getPermissionManager()->canUpdateSchedule($scheduleId))
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_PERMISSION_MANAGE_SCHEDULES')));
		}
		return $this->getScheduleService()->deleteUserAssignments($scheduleId, $userIds);
	}
}