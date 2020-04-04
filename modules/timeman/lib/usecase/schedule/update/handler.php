<?php
namespace Bitrix\Timeman\UseCase\Schedule\Update;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Schedule\ScheduleForm;
use Bitrix\Timeman\UseCase\Schedule\BaseScheduleHandler;

class Handler extends BaseScheduleHandler
{
	/**
	 * @param ScheduleForm $scheduleForm
	 * @return Result|\Bitrix\Timeman\Service\BaseServiceResult
	 * @throws \Exception
	 */
	public function handle($scheduleForm)
	{
		if (!$this->getPermissionManager()->canUpdateSchedule($scheduleForm->id))
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_PERMISSION_MANAGE_SCHEDULES')));
		}

		return $this->getScheduleService()->update($scheduleForm->id, $scheduleForm);
	}
}