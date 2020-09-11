<?php
namespace Bitrix\Timeman\Service\Worktime\Action;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleCollection;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Provider\Schedule\ScheduleProvider;
use Bitrix\Timeman\Provider\Schedule\ShiftPlanProvider;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;

class WorktimeActionList
{
	/** @var WorktimeAction[] */
	private $actions;
	private $userId;
	/** @var \DateTime */
	private $userDateTime;

	/** @var WorktimeRepository */
	private $worktimeRepository;
	/** @var ScheduleProvider */
	private $scheduleProvider;
	/** @var ShiftPlanRepository */
	private $shiftPlanProvider;
	/** @var WorktimeRecordManager */
	private $recordManager;
	/** @var ShiftsManager */
	private $shiftsManager;

	public function __construct(
		ShiftPlanProvider $shiftPlanProvider,
		WorktimeRepository $worktimeRepository,
		ScheduleProvider $scheduleProvider
	)
	{
		$this->shiftPlanProvider = $shiftPlanProvider;
		$this->worktimeRepository = $worktimeRepository;
		$this->scheduleProvider = $scheduleProvider;
	}

	/**
	 * @param $userId
	 * @param null $currentUserDateTime
	 * @return $this
	 */
	public function buildPossibleActionsListForUser($paramUserId, ?\DateTime $paramUserDate = null)
	{
		$userId = (int)$paramUserId;
		if ($paramUserDate)
		{
			$userDate = clone $paramUserDate;
		}
		else
		{
			$userDate = TimeHelper::getInstance()->getUserDateTimeNow($userId);
		}

		$this->userId = $userId;
		$this->userDateTime = $userDate;

		$record = $this->worktimeRepository->findLatestRecord($this->userId);
		$scheduleCollection = $this->scheduleProvider->findSchedulesCollectionByUserId($this->userId);

		$this->shiftsManager = new ShiftsManager(
			$this->userId,
			$scheduleCollection,
			$this->shiftPlanProvider
		);

		if ($record)
		{
			$recordSchedule = $record->obtainSchedule();

			$this->recordManager = new WorktimeRecordManager(
				$record,
				$recordSchedule,
				$record->obtainShift(),
				$this->userDateTime,
				$this->shiftsManager
			);
		}

		$this->actions = $this->buildActions($record);
		return $this;
	}

	private function buildActions(WorktimeRecord $record = null)
	{
		/** @var WorktimeAction[] $actions */
		$actions = [];
		if ($this->isEligibleToStart($record))
		{
			$action = WorktimeAction::createStartAction($this->userId);
			$shiftWithDate = $this->getRelevantShiftWithDate($this->userDateTime, $record);
			if ($shiftWithDate)
			{
				$action->setShift($shiftWithDate->getShift());
				$action->setSchedule($shiftWithDate->getSchedule());
			}
			else
			{
				$action->setSchedule($this->shiftsManager->getScheduleToStart($this->userDateTime));
			}
			$actions[] = $action;
		}
		if ($record)
		{
			if ($this->recordManager->isEligibleToStop())
			{
				$actions[] = WorktimeAction::createStopAction($this->userId);
			}

			if ($this->recordManager->isEligibleToPause())
			{
				$actions[] = WorktimeAction::createPauseAction($this->userId);
			}

			if ($this->recordManager->isEligibleToReopen())
			{
				$actions[] = WorktimeAction::createReopenAction($this->userId);
			}
			if ($this->recordManager->isEligibleToContinue())
			{
				$actions[] = WorktimeAction::createContinueAction($this->userId);
			}

			if ($this->recordManager->isEligibleToEdit())
			{
				$actions[] = WorktimeAction::createEditAction($this->userId);
			}
		}

		foreach ($actions as $action)
		{
			$action->setRecordManager($this->recordManager);
			$action->setShiftsManager($this->shiftsManager);
			if (!$action->isStart())
			{
				$action->setRecord($this->recordManager->getRecord());
				$action->setSchedule($this->recordManager->getRecordSchedule());
				$action->setShift($this->recordManager->getRecordShift());
			}
		}

		return $actions;
	}

	private function isEligibleToStart(?WorktimeRecord $record)
	{
		if (!$record || $record->getRecordedStartTimestamp() > TimeHelper::getInstance()->getUtcNowTimestamp())
		{
			return true;
		}

		if ($record->getRecordedStopTimestamp() > 0)
		{
			if ($this->recordManager->isEligibleToReopen())
			{
				$prevShift = $this->buildShiftWithDateByRecord($record);
				if ($prevShift && $this->userDateTime->getTimestamp() > $prevShift->getDateTimeEnd()->getTimestamp())
				{
					$shiftWithDate = $this->getRelevantShiftWithDate($this->userDateTime, $record);
					if ($shiftWithDate)
					{
						return true;
					}
				}
			}
			else
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getStopActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isStop())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getEditActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isEdit())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getContinueActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isContinue())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getReopenActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isReopen())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getPauseActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isPause())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getStartActions()
	{
		$res = [];
		foreach ($this->actions as $action)
		{
			if ($action->isStart())
			{
				$res[] = $action;
			}
		}
		return $res;
	}

	/**
	 * @return WorktimeAction[]
	 */
	public function getAllActions()
	{
		return $this->actions;
	}

	public function buildApproveAction(WorktimeRecord $record, ?Schedule $schedule, ?Shift $shift, \DateTime $userDateTime)
	{
		$shiftsManager = new ShiftsManager(
			$record->getUserId(),
			$this->scheduleProvider->findSchedulesCollectionByUserId($record->getUserId()),
			$this->shiftPlanProvider
		);
		$recordManager = new WorktimeRecordManager(
			$record,
			$schedule,
			$shift,
			$userDateTime,
			$shiftsManager
		);
		return WorktimeAction::createApproveAction($record->getUserId())
			->setShift($record->obtainShift())
			->setSchedule($record->obtainSchedule())
			->setRecordManager($recordManager)
			->setRecord($record);
	}

	private function buildShiftWithDateByRecord(?WorktimeRecord $record)
	{
		if ($record)
		{
			return $this->shiftsManager->buildRelevantRecordShiftWithDate(
				$record->buildRecordedStartDateTime(),
				$record->obtainSchedule(),
				$record->obtainShift()
			);
		}
		return null;
	}

	private function getRelevantShiftWithDate(\DateTime $userDateTime, ?WorktimeRecord $record)
	{
		$prevShift = $this->buildShiftWithDateByRecord($record);
		return $this->shiftsManager->buildRelevantShiftWithDate($userDateTime, $prevShift);
	}
}