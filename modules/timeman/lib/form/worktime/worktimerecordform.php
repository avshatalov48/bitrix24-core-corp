<?php
namespace Bitrix\Timeman\Form\Worktime;

use Bitrix\Main\EO_User;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Util\Form\CompositeForm;
use Bitrix\Timeman\Util\Form\Filter;

/**
 * @property WorktimeEventForm[] events
 */
class WorktimeRecordForm extends CompositeForm
{
	public $id;
	public $approvedBy;
	public $editedBy;
	public $userId;
	public $recordedStartTimestamp;
	public $startOffset;
	public $actualStartTimestamp;
	public $recordedStopDateFormatted;
	public $recordedStartDateFormatted;
	public $recordedStopTimestamp;
	public $stopOffset;
	public $actualStopTimestamp;
	public $currentStatus;
	public $duration;
	public $actualBreakLength;
	public $recordedBreakLength;
	public $scheduleId;
	public $shiftId;
	public $ipClose;
	public $ipOpen;

	public $recordedStartTime;
	public $recordedStopTime;
	public $recordedBreakLengthTime;

	public $recordedStartSeconds;
	public $recordedStopSeconds;

	/** @var WorktimeRecord */
	private $record;
	public $latitudeClose;
	public $longitudeClose;
	public $latitudeOpen;
	public $longitudeOpen;
	public $tasks;
	public $device;

	public static function createWithEventForm($eventName = null)
	{
		$recordForm = new WorktimeRecordForm();
		$eventForm = new WorktimeEventForm();
		$eventForm->eventName = $eventName;
		$recordForm->events = [$eventForm];
		return $recordForm;
	}

	public function getRecordedStartSeconds()
	{
		if (!is_null($this->recordedStartTime) && is_null($this->recordedStartSeconds))
		{
			return TimeHelper::getInstance()->convertHoursMinutesToSeconds($this->recordedStartTime);
		}
		return $this->recordedStartSeconds;
	}

	/**
	 * @return array 'name' => class
	 */
	protected function getInternalForms()
	{
		return [
			'events' => WorktimeEventForm::class,
		];
	}

	public function __construct(WorktimeRecord $recordEntity = null)
	{
		if ($recordEntity)
		{
			$this->record = $recordEntity;
			$this->id = $recordEntity->getId();
			$this->userId = $recordEntity->getUserId();
			$this->recordedStartTimestamp = $recordEntity->getRecordedStartTimestamp();
			$this->startOffset = $recordEntity->getStartOffset();
			$this->actualStartTimestamp = $recordEntity->getActualStartTimestamp();
			$this->recordedStopTimestamp = $recordEntity->getRecordedStopTimestamp();
			$this->stopOffset = $recordEntity->getStopOffset();
			$this->actualStopTimestamp = $recordEntity->getActualStopTimestamp();
			$this->currentStatus = $recordEntity->getCurrentStatus();
			$this->duration = $recordEntity->getRecordedDuration();
			$this->recordedBreakLength = $recordEntity->getRecordedBreakLength();
			$this->actualBreakLength = $recordEntity->getActualBreakLength();
			$this->scheduleId = $recordEntity->getScheduleId();
			$this->shiftId = $recordEntity->getShiftId();
			$eventForms = [];
			foreach ($recordEntity->obtainWorktimeEvents() as $event)
			{
				$eventForms[] = new WorktimeEventForm($event);
			}
			$this->events = $eventForms;
		}
	}

	protected function runAfterValidate()
	{
		parent::runAfterValidate();
		if ($this->hasErrors())
		{
			return;
		}
		if ($this->recordedStartTime && is_null($this->recordedStartSeconds))
		{
			$this->recordedStartSeconds = TimeHelper::getInstance()->convertHoursMinutesToSeconds($this->recordedStartTime);
		}
		if ($this->recordedStartSeconds && is_null($this->recordedStartTime))
		{
			$this->recordedStartTime = TimeHelper::getInstance()->convertSecondsToHoursMinutes($this->recordedStartSeconds);
		}
		if ($this->recordedStopTime && is_null($this->recordedStopSeconds))
		{
			$this->recordedStopSeconds = TimeHelper::getInstance()->convertHoursMinutesToSeconds($this->recordedStopTime);
		}
		if ($this->recordedStopSeconds && is_null($this->recordedStopTime))
		{
			$this->recordedStopTime = TimeHelper::getInstance()->convertSecondsToHoursMinutes($this->recordedStopSeconds);
		}

		if ($this->recordedBreakLengthTime && is_null($this->recordedBreakLength))
		{
			$this->recordedBreakLength = TimeHelper::getInstance()->convertHoursMinutesToSeconds($this->recordedBreakLengthTime);
		}
	}

	public function configureFilterRules()
	{
		Loc::loadMessages(__FILE__);
		return [
			(new Filter\Validator\RegularExpressionValidator('recordedBreakLengthTime'))
				->configurePattern(TimeHelper::getInstance()->getTimeRegExp(), 'TM_WORKTIME_RECORD_FORM_ERROR_BREAK_DURATION')
			,
			(new Filter\Validator\RegularExpressionValidator('recordedStopTime', 'recordedStartTime'))
				->configurePattern(TimeHelper::getInstance()->getTimeRegExp())
			,
			(new Filter\Validator\StringValidator('recordedStopDateFormatted', 'recordedStartDateFormatted'))
			,
			(new Filter\Validator\CallbackValidator('recordedStopDateFormatted', 'recordedStartDateFormatted'))
				->configureCallback(function ($value) {
					if (!is_string($value))
					{
						return false;
					}
					$timestamp = TimeHelper::getInstance()
						->buildTimestampByFormattedDateForServer(
							$value
						);
					return $timestamp !== false && $timestamp < \DateTime::createFromFormat('Y', 2060)->getTimestamp();
				})
			,
			(new Filter\Validator\LoadableValidator('id', 'userId', 'recordedStartTimestamp',
				'startOffset', 'actualStartTimestamp',
				'recordedStopTimestamp', 'stopOffset', 'actualStopTimestamp',
				'currentStatus', 'duration', 'actualBreakLength', 'recordedBreakLength',
				'scheduleId', 'shiftId',
				'tasks', 'longitudeOpen', 'latitudeOpen', 'longitudeClose', 'latitudeClose', 'ipClose', 'ipOpen')
			)
			,
			(new Filter\Validator\NumberValidator('userId', 'shiftId', 'scheduleId'))
				->configureIntegerOnly(true)
				->configureMin(1)
			,
			(new Filter\Validator\NumberValidator('recordedBreakLength'))
				->configureIntegerOnly(true)
				->configureMax(24 * 60 * 60, 'TM_WORKTIME_RECORD_FORM_ERROR_BREAK_DURATION')
			,
			(new Filter\Validator\RangeValidator('currentStatus'))
				->configureRange(WorktimeRecordTable::getStatusRange())
				->configureStrict(true)
			,
		];
	}

	public function resetStartFields()
	{
		$this->recordedStartSeconds = null;
		$this->recordedStartTimestamp = null;
		$this->recordedStartTime = null;
		$this->actualStartTimestamp = null;
		$this->startOffset = null;
	}

	public function resetBreakLengthFields()
	{
		$this->recordedBreakLength = null;
		$this->recordedBreakLengthTime = null;
		$this->actualBreakLength = null;
	}

	/**
	 * @return WorktimeEventForm|null
	 */
	public function getFirstEventForm()
	{
		return reset($this->events) ?: null;
	}

	public function initScheduleId($id)
	{
		if ($this->scheduleId === null)
		{
			$this->scheduleId = $id;
		}
	}

	public function initShiftId($id)
	{
		if ($this->shiftId === null)
		{
			$this->shiftId = $id;
		}
	}

	public function getFirstEventName()
	{
		$eventForm = reset($this->events);
		if ($eventForm !== null)
		{
			return $eventForm->eventName;
		}
		return null;
	}

	public function getUserUtcOffset()
	{
		return TimeHelper::getInstance()->getUserUtcOffset($this->userId);
	}

	/**
	 * @return EO_User|null
	 */
	public function getUser()
	{
		return $this->record ? $this->record->getUser() : null;
	}

	public function getRecord()
	{
		return $this->record;
	}

	public function getUserFields()
	{
		return $this->getUser() ? $this->getUser()->collectValues() : [];
	}
}