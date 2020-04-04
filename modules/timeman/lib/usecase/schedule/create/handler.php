<?php
namespace Bitrix\Timeman\UseCase\Schedule\Create;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\UseCase\Schedule\BaseScheduleHandler;

class Handler extends BaseScheduleHandler
{
	public function handle($scheduleForm)
	{
		if (!$this->getPermissionManager()->canCreateSchedule())
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_PERMISSION_MANAGE_SCHEDULES')));
		}

		return $this->getScheduleService()->add($scheduleForm);
	}
}
