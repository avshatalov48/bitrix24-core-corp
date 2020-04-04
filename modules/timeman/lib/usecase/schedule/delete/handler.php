<?php
namespace Bitrix\Timeman\UseCase\Schedule\Delete;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\UseCase\Schedule\BaseScheduleHandler;

class Handler extends BaseScheduleHandler
{
	/**
	 * @param $id
	 * @return Result|\Bitrix\Timeman\Service\BaseServiceResult
	 * @throws \Exception
	 */
	public function handle($id)
	{
		if ((int)$id <= 0)
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_WRONG_PARAMS')));
		}
		if (!$this->getPermissionManager()->canDeleteSchedule($id))
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_PERMISSION_MANAGE_SCHEDULES')));
		}

		return $this->getScheduleService()->delete($id);
	}
}
