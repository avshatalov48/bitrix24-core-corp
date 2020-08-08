<?php
namespace Bitrix\Timeman\UseCase\Schedule\ShiftPlan\Create;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\UseCase\BaseUseCaseHandler;

class Handler extends BaseUseCaseHandler
{
	/**
	 * @param ShiftPlanForm $shiftPlanForm
	 * @return Result|\Bitrix\Timeman\Service\BaseServiceResult
	 * @throws \Exception
	 */
	public function handle($shiftPlanForm, $forced = false)
	{
		$scheduleId = DependencyManager::getInstance()->getShiftRepository()->findScheduleIdByShiftId($shiftPlanForm->shiftId);
		if (!$scheduleId || !$this->getPermissionManager()->canUpdateShiftPlan($scheduleId))
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_SCHEDULE_RESULT_ERROR_PERMISSION_MANAGE_SCHEDULES')));
		}

		return DependencyManager::getInstance()->getShiftPlanService()->add($shiftPlanForm, $forced);
	}
}