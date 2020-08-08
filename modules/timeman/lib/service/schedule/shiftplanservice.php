<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\Schedule\Result\ShiftPlanServiceResult;
use Bitrix\Timeman\Service\Worktime\Action\ShiftWithDate;

class ShiftPlanService extends BaseService
{
	/** @var ShiftRepository */
	private $shiftRepository;
	/** @var ShiftPlanRepository */
	private $shiftPlanRepository;
	/** @var WorktimeAgentManager */
	private $worktimeAgentManager;

	public function __construct(ShiftRepository $shiftRepository, ShiftPlanRepository $shiftPlanRepository, WorktimeAgentManager $worktimeAgentManager)
	{
		$this->shiftRepository = $shiftRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
		$this->worktimeAgentManager = $worktimeAgentManager;
	}

	/**
	 * @param ShiftPlanForm $shiftPlanForm
	 * @return ShiftPlanServiceResult
	 */
	public function add(ShiftPlanForm $shiftPlanForm, $forceCreateIfOverlaps = false)
	{
		if (!($shift = $this->shiftRepository->findByIdWithSchedule($shiftPlanForm->shiftId))
			|| !$shift->obtainSchedule()->isShifted())
		{
			return (new ShiftPlanServiceResult())->addShiftNotFoundError();
		}
		$shiftPlan = $this->shiftPlanRepository->findByComplexId(
			$shiftPlanForm->shiftId,
			$shiftPlanForm->userId,
			$shiftPlanForm->getDateAssigned()
		);
		if ($shiftPlan && $shiftPlan->isActive())
		{
			return new ShiftPlanServiceResult();
		}
		if (!$forceCreateIfOverlaps)
		{
			$overlapResult = $this->checkOverlappingPlans($shiftPlanForm, $shift);
			if (!$overlapResult->isSuccess())
			{
				return $overlapResult;
			}
		}

		if ($shiftPlan)
		{
			$shiftPlan->restore();
		}
		else
		{
			$shiftPlan = ShiftPlan::create($shiftPlanForm);
		}

		$res = $this->shiftPlanRepository->save($shiftPlan);
		if (!$res->isSuccess())
		{
			return ShiftPlanServiceResult::createByResult($res);
		}

		// create agent on every shiftplan, always
		$this->worktimeAgentManager->createMissedShiftChecking($shiftPlan, $shift);

		return (new ShiftPlanServiceResult())
			->setShift($shift)
			->setSchedule($shift->obtainSchedule())
			->setShiftPlan($shiftPlan);
	}

	private function checkOverlappingPlans(ShiftPlanForm $shiftPlanForm, Shift $shift)
	{
		$shiftStart = $shift->buildUtcStartByUserId($shiftPlanForm->userId, $shiftPlanForm->getDateAssignedUtc());
		$shiftStart->setTimezone(TimeHelper::getInstance()->getUserTimezone($shiftPlanForm->userId));

		$shiftWithDate = new ShiftWithDate($shift, $shift->obtainSchedule(), $shiftStart);

		$dateFrom = clone $shiftWithDate->getDateTimeStart();
		$dateFrom->sub(new \DateInterval('P1D'));

		$dateTo = clone $shiftWithDate->getDateTimeStart();
		$dateTo->add(new \DateInterval('P1D'));
		$collection = $this->shiftPlanRepository->findAllByUserDates(
			$shiftPlanForm->userId,
			new Date($dateFrom->format('Y-m-d'), 'Y-m-d'),
			new Date($dateTo->format('Y-m-d'), 'Y-m-d')
		);

		$overlappingShifts = [];
		foreach ($collection->getAll() as $shiftPlan)
		{
			$start = $shiftPlan->buildShiftStartDateTimeUtc($shiftPlan->obtainShift());
			if (!$start)
			{
				continue;
			}
			$start->setTimezone(TimeHelper::getInstance()->getUserTimezone($shiftPlanForm->userId));
			$comparing = new ShiftWithDate($shiftPlan->obtainShift(), $shiftPlan->obtainSchedule(), $start);
			if ($comparing->isEqualsTo($shiftWithDate)
				||
				$comparing->getDateTimeStart()->getTimestamp() >= $shiftWithDate->getDateTimeEnd()->getTimestamp()
				||
				$comparing->getDateTimeEnd()->getTimestamp() <= $shiftWithDate->getDateTimeStart()->getTimestamp()
			)
			{
				continue;
			}
			$overlappingShifts[] = $comparing;
		}

		if (count($overlappingShifts) > 0)
		{
			foreach ($overlappingShifts as $otherShiftWithDate)
			{
				return (new ShiftPlanServiceResult())
					->setShiftWithDate($otherShiftWithDate)
					->addError(new Error(
						'Creating shift plan overlaps with another shift plan',
						ShiftPlanServiceResult::ERROR_CODE_OVERLAPPING_SHIFT_PLAN
					));
			}
		}
		return new ShiftPlanServiceResult();
	}

	/**
	 * @param ShiftPlanForm $shiftPlanForm
	 * @return ShiftPlanServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function delete(ShiftPlanForm $shiftPlanForm)
	{
		$shiftPlan = $this->shiftPlanRepository->findActiveById($shiftPlanForm->id, ['*']);
		if (!$shiftPlan)
		{
			return (new ShiftPlanServiceResult())->addShiftPlanNotFoundError();
		}
		$shiftPlan->markDeleted();
		$agentId = 0;
		/** @var ShiftPlan $shiftPlan */
		if ($shiftPlan->getMissedShiftAgentId() > 0)
		{
			$agentId = $shiftPlan->getMissedShiftAgentId();
		}
		$shiftPlan->setMissedShiftAgentId(0);
		$res = $this->shiftPlanRepository->save($shiftPlan);
		if (!$res->isSuccess())
		{
			return ShiftPlanServiceResult::createByResult($res);
		}
		if ($agentId > 0)
		{
			$this->worktimeAgentManager->deleteAgentById($agentId);
		}
		return (new ShiftPlanServiceResult())
			->setShift($shift = $this->shiftRepository->findByIdWithSchedule($shiftPlanForm->shiftId))
			->setSchedule($shift ? $shift->obtainSchedule() : null)
			->setShiftPlan($shiftPlan);
	}
}