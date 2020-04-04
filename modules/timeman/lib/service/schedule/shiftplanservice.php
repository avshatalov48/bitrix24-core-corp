<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\Schedule\Result\ShiftServiceResult;

class ShiftPlanService extends BaseService
{
	/** @var ShiftRepository */
	private $shiftRepository;
	/** @var ShiftPlanRepository */
	private $shiftPlanRepository;
	/** @var WorktimeAgentManager */
	private $worktimeAgentManager;

	public function __construct(
		ShiftRepository $shiftRepository,
		ShiftPlanRepository $shiftPlanRepository,
		WorktimeAgentManager $worktimeAgentManager
	)
	{
		$this->shiftRepository = $shiftRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->worktimeAgentManager = $worktimeAgentManager;
	}

	/**
	 * @param ShiftPlanForm $shiftPlanForm
	 * @return ShiftServiceResult
	 */
	public function add(ShiftPlanForm $shiftPlanForm)
	{
		if (!($shift = $this->getShiftRepository()->findByIdWithSchedule($shiftPlanForm->shiftId)))
		{
			return (new ShiftServiceResult())->addShiftNotFoundError();
		}
		$shiftPlan = ShiftPlan::create($shiftPlanForm);
		$res = $this->getShiftPlanRepository()->save($shiftPlan);
		if (!$res->isSuccess())
		{
			return ShiftServiceResult::createByResult($res);
		}
		$now = TimeHelper::getInstance()->getUtcNowTimestamp();
		$shiftNotEndedYet =
			$now < TimeHelper::getInstance()->getUtcTimestampForUserTime(
				$shiftPlanForm->userId,
				$shift->getWorkTimeEnd(),
				$shiftPlanForm->getDateAssigned()
			);

		$schedule = $shift->obtainSchedule();
		if ($schedule->isShifted()
			&& $schedule->obtainScheduleViolationRules()->isMissedShiftsControlEnabled()
			&& !empty($schedule->obtainScheduleViolationRules()->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_SHIFT_MISSED_START))
			&& $shiftNotEndedYet
		)
		{
			$this->getWorktimeAgentManager()
				->createMissedShiftChecking($shiftPlan, $shift);
		}

		return (new ShiftServiceResult())
			->setShiftPlan($shiftPlan)
			->setShift($shift);
	}

	/**
	 * @param ShiftPlanForm $shiftPlanForm
	 * @return ShiftServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function delete(ShiftPlanForm $shiftPlanForm)
	{
		$shiftPlan = $this->getShiftPlanRepository()->findByComplexId(
			$shiftPlanForm->shiftId,
			$shiftPlanForm->userId,
			$shiftPlanForm->getDateAssigned()
		);
		if (!$shiftPlan)
		{
			return (new ShiftServiceResult())->addShiftPlanNotFoundError();
		}
		$res = $this->getShiftPlanRepository()->delete([
			'SHIFT_ID' => $shiftPlanForm->shiftId,
			'USER_ID' => $shiftPlanForm->userId,
			'DATE_ASSIGNED' => $shiftPlanForm->getDateAssigned(),
		]);
		if (!$res->isSuccess())
		{
			return ShiftServiceResult::createByResult($res);
		}
		return (new ShiftServiceResult())
			->setShift($this->getShiftRepository()->findByIdWithSchedule($shiftPlanForm->shiftId))
			->setShiftPlan($shiftPlan);
	}

	private function getShiftRepository()
	{
		return $this->shiftRepository;
	}

	private function getShiftPlanRepository()
	{
		return $this->shiftPlanRepository;
	}

	private function getWorktimeAgentManager()
	{
		return $this->worktimeAgentManager;
	}
}