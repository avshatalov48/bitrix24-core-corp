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
use Bitrix\Timeman\Repository\Schedule\ViolationRulesRepository;
use Bitrix\Timeman\Repository\Worktime\WorktimeRepository;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolationParams;
use Bitrix\Timeman\Service\Worktime\Action\WorktimeAction;
use Bitrix\Timeman\Service\Worktime\WorktimeLiveFeedManager;

Loc::loadMessages(__FILE__);

abstract class WorktimeManager
{
	/** @var WorktimeRecordForm */
	protected $worktimeRecordForm;
	/** @var Schedule */
	private $schedule;
	/** @var WorktimeRecord */
	private $record;
	/** @var Shift */
	private $shift;
	private $recordManager;

	private $violationManager;
	private $violationRulesRepository;

	public function __construct(WorktimeViolationManager $violationManager,
								ViolationRulesRepository $violationRulesRepository,
								WorktimeRecordForm $worktimeRecordForm
	)
	{
		$builder = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$allowedConsumerClass = WorktimeManagerFactory::class;
		if (!isset($builder[1]['class']) || $builder[1]['class'] !== $allowedConsumerClass)
		{
			throw new SystemException(static::class . ' need to be instantiated by ' . $allowedConsumerClass);
		}
		$this->worktimeRecordForm = clone $worktimeRecordForm;
		$this->violationManager = $violationManager;
		$this->violationRulesRepository = $violationRulesRepository;
	}

	public function notifyOfActionOldStyle($record, $schedule)
	{
	}

	public function onBeforeRecordSave(WorktimeRecord $record, WorktimeLiveFeedManager $liveFeedManager)
	{
	}

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule $schedule
	 * @return \Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation[]
	 */
	protected function buildWorktimeViolations($record, $schedule, $types = [])
	{
		if (!$schedule || !$record)
		{
			return [];
		}
		$result = [];
		$violationRulesList = array_filter([$schedule->obtainScheduleViolationRules(), $this->getPersonalViolationRules()]);

		foreach ($violationRulesList as $violationRules)
		{
			$result[] = $this->violationManager->buildViolations(
				(new WorktimeViolationParams())
					->setShift($this->getShift())
					->setSchedule($schedule)
					->setViolationRules($violationRules)
					->setRecord($record),
				$types
			);
		}
		return empty($result) ? [] : array_merge(...$result);
	}

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule $schedule
	 * @param ViolationRules[] $violationRulesList
	 * @return array
	 */
	public function buildRecordViolations($record, $schedule)
	{
		return [];
	}

	protected function isExpired()
	{
		if ($this->recordManager && $this->getRecord())
		{
			return $this->recordManager->isRecordExpired();
		}
		return false;
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
		if (!$eventForm)
		{
			return [];
		}
		return [
			WorktimeEvent::create(
				$eventForm->eventName,
				$record->getUserId(),
				$record->getId(),
				null,
				$eventForm->reason,
				$this->worktimeRecordForm->device
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
	private function verifyAfterUpdatingRecord(?WorktimeRecord $record, WorktimeRepository $worktimeRepository)
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
			if ($this->checkOverlappingRecords())
			{
				if ($worktimeRepository->findOverlappingRecordByDates($record))
				{
					return $result->addError(new Error(
							Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_OTHER_RECORD_FOR_DATES_EXISTS'),
							WorktimeServiceResult::ERROR_FOR_USER)
					);
				}
			}
			if ($this->checkStartGreaterThanNow())
			{
				if ($record && $this->worktimeRecordForm->recordedStartSeconds !== null)
				{
					$startTimestamp = $record->getRecordedStartTimestamp();
					if ($startTimestamp > TimeHelper::getInstance()->getUtcNowTimestamp())
					{
						return $result->addError(new Error(
								Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_START_GREATER_THAN_NOW'),
								WorktimeServiceResult::ERROR_FOR_USER)
						);
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @param WorktimeAction $action
	 * @return WorktimeServiceResult
	 */
	public function buildActualRecord($action, WorktimeRepository $worktimeRepository)
	{
		$this->schedule = $action->getSchedule();
		$this->shift = $action->getShift();
		$this->record = $action->getRecord();
		$this->recordManager = $action->getRecordManager();
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
		$verifyResultAfter = $this->verifyAfterUpdatingRecord($record, $worktimeRepository);
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
			   || trim($this->worktimeRecordForm->getFirstEventForm()->reason) == '';
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
	protected function getPersonalViolationRules(): ?ViolationRules
	{
		static $personalViolationRules = false;
		if ($personalViolationRules === false)
		{
			$personalViolationRules = null;
			if ($this->getRecord() && $this->getRecord()->getScheduleId() > 0 && $this->getRecord()->getId() > 0)
			{
				$personalViolationRules = $this->violationRulesRepository->findFirstByScheduleIdAndEntityCode(
					$this->getRecord()->getScheduleId(), 'U' . $this->getRecord()->getId()
				);
			}
		}
		return $personalViolationRules;
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
		return !$this->getSchedule() || !$this->getSchedule()->isFlextime();
	}

	protected function checkOverlappingRecords()
	{
		return false;
	}

	protected function checkStartGreaterThanNow()
	{
		return false;
	}
}