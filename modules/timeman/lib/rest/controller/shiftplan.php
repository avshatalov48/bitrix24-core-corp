<?php
namespace Bitrix\Timeman\Rest\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
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
				$res = ['shiftPlan' => $this->makeResult($result)];
				return $res;
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
				$res = ['shiftPlan' => $this->makeResult($result, $deleted = true)];
				return $res;
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
	private function makeResult($shiftPlanResult, $deleted = false)
	{
		$shiftPlan = $shiftPlanResult->getShiftPlan();
		if (!$shiftPlan)
		{
			return [];
		}
		$shift = $shiftPlanResult->getShift();
		$res = [];
		if ($shiftPlan->getUserId())
		{
			$res = [
				'userId' => (int)$shiftPlan->getUserId(),
				'shiftId' => (int)$shiftPlan->getShiftId(),
				'dateAssigned' => $shiftPlan->getDateAssignedUtc()->format(ShiftPlanTable::DATE_FORMAT),
			];
		}
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
		global $APPLICATION;
		ob_start();
		$start = $shiftPlan->buildShiftStartDateTimeUtc($shiftPlanResult->getShift());
		$userId = $this->getCurrentUser()->getId();
		if ($this->getRequest()->get('useEmployeesTimezone') === 'Y')
		{
			$userId = $shiftPlan->getUserId();
		}
		$start->setTimezone(TimeHelper::getInstance()->createTimezoneByOffset(
			TimeHelper::getInstance()->getUserUtcOffset($userId))
		);

		$APPLICATION->IncludeComponent(
			'bitrix:timeman.worktime.grid',
			'.default',
			[
				'PARTIAL_ITEM' => 'shiftCell',
				'IS_SHIFTPLAN' => true,
				'DRAWING_TIMESTAMP' => $start->getTimestamp(),
				'INCLUDE_CSS' => false,
				'SCHEDULE_ID' => $shift->getScheduleId(),
				'USER_ID' => $shiftPlan->getUserId(),
			]
		);
		$res['cellHtml'] = ob_get_clean();

		return $res;
	}

}