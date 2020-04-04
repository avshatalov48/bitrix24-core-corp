<?php
namespace Bitrix\Timeman\Service\Worktime\Record;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationParams;
use Bitrix\Timeman\Service\Worktime\WorktimeAction;

Loc::loadMessages(__FILE__);

abstract class WorktimeManager
{
	/** @var WorktimeRepository */
	protected $worktimeRepository;
	/** @var WorktimeRecordForm */
	protected $worktimeRecordForm;
	/** @var Schedule */
	private $schedule;
	/** @var WorktimeRecord */
	private $record;
	/** @var Shift */
	private $shift;
	/** @var WorktimeViolationManager */
	private $violationManager;
	private $personalViolationRules;
	private $shiftPlanRepository;

	public function __construct(WorktimeViolationManager $violationManager,
								WorktimeRecordForm $worktimeRecordForm,
								WorktimeRepository $worktimeRepository,
								ShiftPlanRepository $shiftPlanRepository)
	{
		$builder = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$allowedConsumerClasses = [
			'Bitrix\Timeman\Service\Worktime\Record\WorktimeRecordManagerFactory',
		];
		if (count($builder) < 2 || !isset($builder[1]['class']) ||
			!in_array($builder[1]['class'], $allowedConsumerClasses, true)
		)
		{
			throw new SystemException('Need to be instantiated by ' . implode(' or ', $allowedConsumerClasses));
		}
		$this->worktimeRecordForm = clone $worktimeRecordForm;
		$this->violationManager = $violationManager;
		$this->worktimeRepository = $worktimeRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
	}

	public function notifyOfAction($record, $schedule)
	{
	}

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule $schedule
	 * @param ViolationRules[] $violationRulesList
	 * @return \Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation[]
	 */
	protected function buildWorktimeViolations($record, $schedule, $types = [], $violationRulesList = [])
	{
		if (empty($violationRulesList) && $schedule)
		{
			$violationRulesList = [$schedule->obtainScheduleViolationRules()];
		}
		$result = [];
		$plan = null;
		if (Schedule::isScheduleShifted($schedule))
		{
			$plan = $this->shiftPlanRepository->findActiveByRecord($record);
		}
		foreach ($violationRulesList as $violationRules)
		{
			$result = array_merge(
				$result,
				$this->violationManager->buildViolations(
					(new WorktimeViolationParams())
						->setShift($schedule ? $schedule->obtainShiftByPrimary($record->getShiftId()) : null)
						->setSchedule($schedule)
						->setViolationRules($violationRules)
						->setShiftPlan($plan)
						->setRecord($record),
					$types
				)
			);
		}
		return $result;
	}

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule $schedule
	 * @param ViolationRules[] $violationRulesList
	 * @return array
	 */
	public function buildRecordViolations($record, $schedule, $violationRulesList = [])
	{
		return [];
	}

	public function validateBeforeProcess()
	{
		return new WorktimeServiceResult();
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeEvent[]
	 */
	public function buildEvents($record)
	{
		$eventForm = $this->worktimeRecordForm->getFirstEventForm();
		$recordedTimestamp = null;

		return [
			WorktimeEvent::create(
				$eventForm->eventName,
				$record->getUserId(),
				$record->getId(),
				$recordedTimestamp,
				$eventForm->reason
			),
		];
	}

	/**
	 * @param WorktimeRecord $record
	 * @return WorktimeRecord
	 */
	abstract protected function updateRecordFields($record);

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule $schedule
	 * @return WorktimeServiceResult
	 */
	protected function verifyBeforeProcessUpdatingRecord()
	{
		if ($this->worktimeRecordForm->isSystem)
		{
			return new WorktimeServiceResult();
		}
		if (!Schedule::isDeviceAllowed($this->worktimeRecordForm->device, $this->getSchedule()))
		{
			return WorktimeServiceResult::createWithErrorText(
				$this->getDeviceNotAllowedErrorText($this->worktimeRecordForm->device),
				WorktimeServiceResult::ERROR_FOR_USER
			);
		}
		return new WorktimeServiceResult();
	}

	/**
	 * @param WorktimeRecord|null $record
	 * @return WorktimeServiceResult
	 */
	protected function verifyAfterUpdatingRecord($record)
	{
		$result = new WorktimeServiceResult();
		if ($record)
		{
			if ($record->getRecordedDuration() < 0)
			{
				return $result->addError(
					new Error(
						Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_NEGATIVE_DURATION'),
						WorktimeServiceResult::ERROR_FOR_USER
					)
				);
			}
			if ($this->checkIntersectingRecords())
			{
				if ($this->worktimeRepository->findIntersectingRecordByDates(
					$record->getUserId(),
					$record->getRecordedStartTimestamp(),
					$record->getRecordedStopTimestamp(),
					$record->getId()
				))
				{
					return $result->addError(new Error(
							Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_OTHER_RECORD_FOR_DATES_EXISTS'),
							WorktimeServiceResult::ERROR_FOR_USER)
					);
				}
			}
		}
		return $result;
	}

	/**
	 * @param WorktimeAction $action
	 * @return WorktimeServiceResult
	 */
	public function buildActualRecord($schedule, $shift, $record, $personalViolationRules = null)
	{
		$this->schedule = $schedule;
		$this->shift = $shift;
		$this->record = $record;
		$this->personalViolationRules = $personalViolationRules;
		$verifyResult = $this->verifyBeforeProcessUpdatingRecord();
		if (!$verifyResult->isSuccess())
		{
			return WorktimeServiceResult::createByResult($verifyResult);
		}
		$record = $this->processBuildingActualRecord();
		if (!$record)
		{
			return (new WorktimeServiceResult())->addError(
				new Error(
					Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_NOTHING_TO_START'),
					WorktimeServiceResult::ERROR_FOR_USER
				)
			);
		}
		$verifyResultAfter = $this->verifyAfterUpdatingRecord($record);
		if (!$verifyResultAfter->isSuccess())
		{
			return WorktimeServiceResult::createByResult($verifyResultAfter);
		}
		return (new WorktimeServiceResult())->setWorktimeRecord($record);
	}

	/**
	 * @return WorktimeRecord|null
	 */
	private function preBuildRecord()
	{
		if (!$this->getSchedule())
		{
			return null;
		}
		if (!$this->getSchedule()->isAutoStarting())
		{
			return null;
		}
		$shift = $this->getShift();
		if (!$shift)
		{
			return null;
		}

		$record = null;
		$eventType = $this->worktimeRecordForm->getFirstEventName();

		switch ($eventType)
		{
			case WorktimeEventTable::EVENT_TYPE_STOP:
			case WorktimeEventTable::EVENT_TYPE_EDIT_STOP:
			case WorktimeEventTable::EVENT_TYPE_STOP_WITH_ANOTHER_TIME:
				$this->worktimeRecordForm->initScheduleId($this->getSchedule()->getId());
				$this->worktimeRecordForm->initShiftId($shift->getId());
				$record = WorktimeRecord::startWork(
					$this->worktimeRecordForm,
					TimeHelper::getInstance()->getTimestampByUserSeconds(
						$this->worktimeRecordForm->userId,
						$shift->getWorkTimeStart()
					)
				);
				break;
			default:
				break;
		}
		return $record;
	}

	protected function processBuildingActualRecord()
	{
		$record = $this->getRecord();
		if (!$record)
		{
			$record = $this->preBuildRecord();
		}

		$record = $this->updateRecordFields($record);
		if ($record)
		{
			$this->setApproved($record);
		}

		return $record;
	}

	/**
	 * @param WorktimeRecord $record
	 */
	protected function setApproved($record)
	{
	}

	protected function isEmptyEventReason()
	{
		if (!$this->worktimeRecordForm)
		{
			return true;
		}
		return !is_string($this->worktimeRecordForm->getFirstEventForm()->reason)
			   || strlen(trim($this->worktimeRecordForm->getFirstEventForm()->reason)) <= 0;
	}

	protected function getDeviceNotAllowedErrorText($device)
	{
		if ($device === ScheduleTable::ALLOWED_DEVICES_BROWSER)
		{
			return Loc::getMessage('TM_VIOLATION_WORKTIME_MANAGER_FORBIDDEN_DEVICE_BROWSER');
		}
		if ($device === ScheduleTable::ALLOWED_DEVICES_MOBILE)
		{
			return Loc::getMessage('TM_VIOLATION_WORKTIME_MANAGER_FORBIDDEN_DEVICE_MOBILE');
		}
		if ($device === ScheduleTable::ALLOWED_DEVICES_B24TIME)
		{
			return Loc::getMessage('TM_VIOLATION_WORKTIME_MANAGER_FORBIDDEN_DEVICE_B24TIME');
		}
		return '';
	}

	/**
	 * @return Schedule
	 */
	protected function getSchedule()
	{
		return $this->schedule;
	}

	/**
	 * @return ViolationRules|null
	 */
	protected function getPersonalViolationRules()
	{
		return $this->personalViolationRules;
	}

	/**
	 * @return WorktimeRecord
	 */
	protected function getRecord()
	{
		return $this->record;
	}

	/**
	 * @return Shift
	 */
	protected function getShift()
	{
		return $this->shift;
	}

	protected function needToSaveCompatibleReports()
	{
		return !$this->getSchedule() || !$this->getSchedule()->isFlexible();
	}

	protected function checkIntersectingRecords()
	{
		return false;
	}
}