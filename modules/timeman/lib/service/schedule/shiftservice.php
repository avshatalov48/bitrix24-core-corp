<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Form\Schedule\ShiftForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\ShiftCollection;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\Schedule\Result\ScheduleServiceResult;
use Bitrix\Timeman\Service\Schedule\Result\ShiftServiceResult;

class ShiftService extends BaseService
{
	/** @var ShiftRepository */
	private $shiftRepository;
	private $shiftPlanRepository;
	private $worktimeAgentManager;

	public function __construct(
		ShiftRepository $shiftRepository,
		ShiftPlanRepository $shiftPlanRepository,
		WorktimeAgentManager $worktimeAgentManager
	)
	{
		$this->worktimeAgentManager = $worktimeAgentManager;
		$this->shiftRepository = $shiftRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
	}

	/**
	 * @param ShiftForm $shiftForm
	 * @return ShiftServiceResult
	 */
	public function add($scheduleOrId, ShiftForm $shiftForm)
	{
		$schedule = $scheduleOrId;
		if (!($schedule instanceof Schedule))
		{
			if (!($schedule = $this->shiftRepository->findScheduleById($scheduleOrId)))
			{
				return (new ShiftServiceResult())->addScheduleNotFoundError();
			}
		}
		$shift = Shift::create(
			$schedule->getId(),
			$schedule->isShifted() ? $shiftForm->name : '',
			$shiftForm->startTime,
			$shiftForm->endTime,
			$shiftForm->breakDuration,
			$shiftForm->workDays
		);
		$saveResult = $this->shiftRepository->save($shift);
		if (!$saveResult->isSuccess())
		{
			return ShiftServiceResult::createByResult($saveResult);
		}

		return (new ShiftServiceResult())->setShift($shift);
	}

	/**
	 * @param $shift
	 * @param ShiftForm $shiftForm
	 * @return \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|ShiftServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update($shiftOrId, ShiftForm $shiftForm)
	{
		$shift = $shiftOrId;
		if (!($shift instanceof Shift))
		{
			$shift = $this->shiftRepository->findByIdWithSchedule((int)$shiftOrId);
		}
		if (!$shift)
		{
			return (new ShiftServiceResult())->addShiftNotFoundError();
		}

		$shift->edit(
			$shiftForm->name,
			$shiftForm->startTime,
			$shiftForm->endTime,
			$shiftForm->breakDuration,
			$shiftForm->workDays
		);
		$res = $this->shiftRepository->save($shift);
		if ($res->isSuccess())
		{
			return (new ShiftServiceResult())->setShift($shift);
		}
		return ShiftServiceResult::createByResult($res);
	}

	/**
	 * @param Schedule $schedule
	 * @param $activeUserIds
	 * @param Shift|ShiftCollection|null $shiftParam
	 * @return ScheduleServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function deleteFutureShiftPlans($schedule, $shiftParam = null, $activeUserIds = [])
	{
		$result = (new ScheduleServiceResult())->setSchedule($schedule);
		$shifts = ShiftCollection::buildShiftCollection($shiftParam);
		if ($shiftParam === null)
		{
			$shifts = $this->shiftRepository->findShiftsBySchedule($schedule->getId());
		}
		if (empty($activeUserIds))
		{
			$activeUserIds = $this->shiftPlanRepository->findUserIdsByShiftIds($shifts->getIdList());
		}
		if ($shifts->count() === 0 || empty($activeUserIds))
		{
			return $result;
		}

		$deleteShifts = [];
		$utcNow = TimeHelper::getInstance()->getUtcNowTimestamp();

		foreach ($activeUserIds as $activeUserId)
		{
			$userToday = TimeHelper::getInstance()->getUserDateTimeNow($activeUserId);
			$userToday->setTimezone(new \DateTimeZone('UTC'));
			$shiftPlan = (new ShiftPlan($default = false))
				->setDateAssigned(new \Bitrix\Main\Type\Date($userToday->format(ShiftPlanTable::DATE_FORMAT), ShiftPlanTable::DATE_FORMAT))
				->setUserId($activeUserId);
			foreach ($shifts->getAll() as $shift)
			{
				$utcUserStart = $shift->buildUtcStartByShiftplan($shiftPlan);
				if (!$utcUserStart)
				{
					continue;
				}
				if ($utcUserStart->getTimestamp() + $shift->getDuration() < $utcNow)
				{
					$utcUserStart->add(new \DateInterval('P1D'));
				}
				$deleteShifts[$utcUserStart->format(ShiftPlanTable::DATE_FORMAT)][$shift->getId()][] = $activeUserId;
			}
		}
		if (empty($deleteShifts))
		{
			return $result;
		}
		foreach ($deleteShifts as $dateFormatted => $items)
		{
			$userIds = [];
			foreach ($items as $shiftId => $userIdsForShift)
			{
				$userIds = array_merge($userIds, $userIdsForShift);
			}
			$userIds = array_unique($userIds);
			$filter = Query::filter()
				->whereIn('USER_ID', $userIds)
				->whereIn('SHIFT_ID', array_keys($items))
				->where('DATE_ASSIGNED', '>=', new \Bitrix\Main\Type\Date($dateFormatted, ShiftPlanTable::DATE_FORMAT));
			$shiftPlans = $this->shiftPlanRepository->findAllActive(['ID', 'MISSED_SHIFT_AGENT_ID'], $filter);
			if (!empty($shiftPlans->getMissedShiftAgentIdList()))
			{
				$agentIds = $shiftPlans->getMissedShiftAgentIdList();
				foreach ($agentIds as $index => $agentId)
				{
					if ($agentId <= 0)
					{
						unset($agentIds[$index]);
					}
				}
				$this->worktimeAgentManager->deleteAgentsByIds(array_filter($agentIds));
			}
			if (!empty($shiftPlans->getIdList()))
			{
				$this->shiftPlanRepository->updateAll(
					$shiftPlans->getIdList(),
					[
						'DELETED' => ShiftPlanTable::DELETED_YES,
						'DELETED_AT' => TimeHelper::getInstance()->getUtcNowTimestamp(),
						'MISSED_SHIFT_AGENT_ID' => 0,
					]
				);
			}
		}

		return (new ScheduleServiceResult())->setSchedule($schedule);
	}

	public function deleteShiftById(Shift $shift, Schedule $schedule, $activeUserIds = [])
	{
		$result = $this->shiftRepository->markShiftDeleted($shift->getId());
		if (!$result->isSuccess())
		{
			return $result;
		}
		return $this->deleteFutureShiftPlans(
			$schedule,
			$shift,
			$activeUserIds
		);
	}
}