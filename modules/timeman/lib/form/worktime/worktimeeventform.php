<?php
namespace Bitrix\Timeman\Form\Worktime;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\WorkTime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Util\Form\Filter;

class WorktimeEventForm extends \Bitrix\Timeman\Util\Form\BaseForm
{
	public $recordId;
	public $userId;
	public $eventName;
	public $reason;

	public $recordedSeconds; // 3600
	public $recordedTime; // '09:00'
	public $recordedDate; // 2018-12-24

	public $recordedOffset; // 7200

	private $recordedDateTime;

	public function __construct(WorktimeEvent $workTimeLog = null)
	{
		if ($workTimeLog)
		{
			$this->userId = $workTimeLog->getUserId();
			$this->eventName = $workTimeLog->getEventType();

		}
	}

	/**
	 * @param mixed $recordedDateTime
	 */
	protected function setRecordedDateTime($recordedDateTime)
	{
		$this->recordedDateTime = $recordedDateTime;
	}

	protected function runAfterValidate()
	{
		parent::runAfterValidate();
		if ($this->hasErrors())
		{
			return;
		}

		if ($this->recordedTime && !$this->recordedSeconds)
		{
			$this->recordedSeconds = TimeHelper::getInstance()->convertHoursMinutesToSeconds($this->recordedTime);
		}
		if ($this->recordedSeconds && !$this->recordedTime)
		{
			$this->recordedTime = TimeHelper::getInstance()->convertSecondsToHoursMinutes($this->recordedSeconds);
		}
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Validator\NumberValidator('userId', 'recordId'))
				->configureIntegerOnly(true)
				->configureMin(1)
			,
			(new Filter\Validator\RangeValidator('eventName'))
				->configureRange(WorktimeEventTable::getEventTypeRange())
				->configureStrict(true)
			,
			(new Filter\Validator\RegularExpressionValidator('recordedTime'))
				->configurePattern(TimeHelper::getInstance()->getTimeRegExp())
			,
			(new Filter\Validator\RegularExpressionValidator('recordedDate'))
				->configurePattern(ShiftPlanTable::getDateRegExp())
			,
			(new Filter\Validator\NumberValidator('recordedTimestamp'))
				->configureIntegerOnly(true)
				->configureMin(947494800) // > 2000 year
			,
			(new Filter\Validator\NumberValidator('recordedOffset'))
				->configureIntegerOnly(true)
			,
			(new Filter\Validator\StringValidator('reason'))
			,
			(new Filter\Modifier\StringModifier('reason'))
				->configureTrim(true)
			,
			(new Filter\Validator\NumberValidator('recordedSeconds'))
				->configureIntegerOnly(true)
				->configureMax(86400)
				->configureMin(0)
			,
		];
	}

	public function getRecordedTimestamp()
	{
		if (!$this->getRecordedDateTime())
		{
			return null;
		}
		return $this->getRecordedDateTime()->format('U');
	}

	/**
	 * @param null $userOffset
	 * @return \DateTime|null
	 */
	public function getRecordedDateTime()
	{
		if ($this->recordedDateTime)
		{
			return $this->recordedDateTime;
		}

		if (!$this->recordedDate || !$this->recordedSeconds)
		{
			return null;
		}

		if (!$this->recordedDateTime)
		{
			$this->recordedDateTime = TimeHelper::getInstance()->createUserDateTimeFromFormat(
				'Y-m-d H:i',
				$this->recordedDate . ' ' . $this->recordedTime,
				$this->userId
			);
		}

		if ($this->recordedDateTime === false)
		{
			return null;
		}
		return $this->recordedDateTime;
	}

	/**
	 * @return int
	 * @throws \Exception
	 */
	public function getUserUtcOffset()
	{
		return TimeHelper::getInstance()->getUserUtcOffset($this->userId);
	}
}