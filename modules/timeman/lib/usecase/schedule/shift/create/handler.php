<?php
namespace Bitrix\Timeman\UseCase\Schedule\Shift\Create;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Schedule\ShiftForm;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\UseCase\BaseUseCaseHandler;

class Handler extends BaseUseCaseHandler
{
	/**
	 * @param ShiftForm $shiftForm
	 * @return Result|\Bitrix\Timeman\Service\BaseServiceResult
	 * @throws \Exception
	 */
	public function handle($shiftForm)
	{
		if (!$shiftForm->scheduleId || !$this->getPermissionManager()->canUpdateSchedule($shiftForm->scheduleId))
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_PERMISSION_MANAGE_SCHEDULES')));
		}

		return DependencyManager::getInstance()->getShiftService()->add($shiftForm->scheduleId, $shiftForm);
	}
}