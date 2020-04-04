<?php
namespace Bitrix\Timeman\Rest\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Service\Schedule\Result\ShiftServiceResult;
use Bitrix\Timeman\UseCase\Schedule\ShiftPlan as ShiftPlanHandler;

class ShiftPlan extends Controller
{
	public function addAction()
	{
		$shiftPlanForm = new ShiftPlanForm();

		if ($shiftPlanForm->load($this->getRequest()) && $shiftPlanForm->validate())
		{
			$result = (new ShiftPlanHandler\Create\Handler())->handle($shiftPlanForm);
			if ($result->isSuccess())
			{
				return ['shiftPlan' => $this->makeResult($result)];
			}
			$this->addErrors($result->getErrors());
			return [];
		}
		$this->addError($shiftPlanForm->getFirstError());
	}

	public function deleteAction()
	{
		$shiftPlanForm = new ShiftPlanForm();

		if ($shiftPlanForm->load($this->getRequest()) && $shiftPlanForm->validate())
		{
			$result = (new ShiftPlanHandler\Delete\Handler())->handle($shiftPlanForm);
			if ($result->isSuccess())
			{
				return ['shiftPlan' => $this->makeResult($result, $shiftPlanForm)];
			}
			$this->addErrors($result->getErrors());
			return [];
		}
		$this->addError($shiftPlanForm->getFirstError());
	}

	/**
	 * @param ShiftServiceResult $shiftPlanResult
	 * @param null $shiftPlanForm
	 * @return array
	 */
	private function makeResult($shiftPlanResult, $shiftPlanForm = null)
	{
		$shiftPlan = $shiftPlanResult->getShiftPlan();
		$shift = $shiftPlanResult->getShift();
		$res = [];
		if ($shiftPlan->getUserId())
		{
			$res = [
				'userId' => (int)$shiftPlan->getUserId(),
				'shiftId' => (int)$shiftPlan->getShiftId(),
				'dateAssigned' => $shiftPlan->getDateAssigned(),
			];
		}
		elseif ($shiftPlanForm)
		{
			/** @var ShiftPlanForm $shiftPlanForm */
			$res = [
				'userId' => (int)$shiftPlanForm->userId,
				'shiftId' => (int)$shiftPlanForm->shiftId,
				'dateAssigned' => $shiftPlanForm->getDateAssigned()->toString(),
			];
		}
		if ($shift)
		{
			$res['shift'] = [
				'id' => $shift->getId(),
				'name' => $shift->getName(),
				'breakDuration' => $shift->getBreakDuration(),
				'workTimeStart' => $shift->getWorkTimeStart(),
				'workTimeEnd' => $shift->getWorkTimeEnd(),
				'formattedWorkTimeStart' => TimeHelper::getInstance()->convertSecondsToHoursMinutes($shift->getWorkTimeStart()),
				'formattedWorkTimeEnd' => TimeHelper::getInstance()->convertSecondsToHoursMinutes($shift->getWorkTimeEnd()),
				'scheduleId' => $shift->getScheduleId(),
				'workDays' => $shift->getWorkDays(),
			];
		}
		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:timeman.worktime.grid',
			'.default',
			[
				'PARTIAL' => true,
				'PARTIAL_ITEM' => 'shiftCell',
				'DRAWING_DATE' => $shiftPlan['DATE_ASSIGNED'],
				'SHOW_ADD_SHIFT_PLAN_BTN' => (bool)$shiftPlanForm,
				'INCLUDE_CSS' => false,
				'IS_SHIFTED_SCHEDULE' => true,
				'WORK_SHIFT' => $shift->collectValues(),
				'SHIFT_PLAN' => $shiftPlan->collectValues(),
				'USER_ID' => $shiftPlan->getUserId(),
			]
		);
		$res['shiftCellHtml'] = ob_get_clean();

		return $res;
	}

}