<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Timeman\Form\Schedule\ShiftPlanForm;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
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

	public function __construct(ShiftRepository $shiftRepository, ShiftPlanRepository $shiftPlanRepository, WorktimeAgentManager $worktimeAgentManager)
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
		if (!($shift = $this->shiftRepository->findByIdWithSchedule($shiftPlanForm->shiftId))
			|| !$shift->obtainSchedule()->isShifted())
		{
			return (new ShiftServiceResult())->addShiftNotFoundError();
		}
		$shiftPlan = $this->shiftPlanRepository->findByComplexId(
			$shiftPlanForm->shiftId,
			$shiftPlanForm->userId,
			$shiftPlanForm->getDateAssigned()
		);
		if ($shiftPlan && $shiftPlan->isActive())
		{
			return new ShiftServiceResult();
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
			return ShiftServiceResult::createByResult($res);
		}

		// create agent on every shiftplan, always
		$this->worktimeAgentManager->createMissedShiftChecking($shiftPlan, $shift);

		return (new ShiftServiceResult())
			->setShift($shift)
			->setSchedule($shift->obtainSchedule())
			->setShiftPlan($shiftPlan);
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
		$shiftPlan = $this->shiftPlanRepository->findActiveById($shiftPlanForm->id, ['*']);
		if (!$shiftPlan)
		{
			return (new ShiftServiceResult())->addShiftPlanNotFoundError();
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
			return ShiftServiceResult::createByResult($res);
		}
		if ($agentId > 0)
		{
			$this->worktimeAgentManager->deleteAgentById($agentId);
		}
		return (new ShiftServiceResult())
			->setShift($shift = $this->shiftRepository->findByIdWithSchedule($shiftPlanForm->shiftId))
			->setSchedule($shift ? $shift->obtainSchedule() : null)
			->setShiftPlan($shiftPlan);
	}
}