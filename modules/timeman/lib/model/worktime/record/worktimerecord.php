<?php
namespace Bitrix\Timeman\Model\Worktime\Record;

use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventCollection;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Report\EO_WorktimeReport_Collection;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable;

class WorktimeRecord extends EO_WorktimeRecord
{
	/** @var TimeHelper */
	private $timeHelper;
	/** @var Schedule */
	private $schedule;
	/** @var Shift */
	private $shift;
	/*** @var EO_WorktimeReport_Collection */
	private $reports;
	private $worktimeEvents;

	/**
	 * @param WorktimeRecordForm $recordForm
	 * @param $userId
	 * @param $recordedStartTimestamp
	 * @return WorktimeRecord
	 */
	public static function startWork($recordForm, $recordStartUtcTimestamp = null, $userId = null)
	{
		$userId = $userId === null ? $recordForm->userId : $userId;
		$record = new static(true);
		$record->setScheduleId($recordForm->scheduleId);
		$record->setShiftId($recordForm->shiftId);
		$record->setUserId($userId);

		$startOffset = $record->getTimeHelper()->getUserUtcOffset($userId);
		$record->setStartOffset($startOffset);
		$record->setRecordedStartTimestamp($recordStartUtcTimestamp);
		if ($recordStartUtcTimestamp === null)
		{
			$recordStartFromForm = $recordForm->buildStartTimestampBySecondsAndDate(
				$recordForm->useEmployeesTimezone ? $record->getUserId() : $recordForm->editedBy
			);
			if ($recordStartFromForm)
			{
				$record->setRecordedStartTimestamp($recordStartFromForm);
			}
			else
			{
				$record->setRecordedStartTimestamp($record->getTimeHelper()->getUtcNowTimestamp());
			}
		}
		$record->defineStartTime($record->getRecordedStartTimestamp());
		$record->setIpOpen($recordForm->ipOpen);
		$record->setLatOpen($recordForm->latitudeOpen);
		$record->setLonOpen($recordForm->longitudeOpen);
		$record->setTasks((array)$recordForm->tasks);
		$record->setCurrentStatus(WorktimeRecordTable::STATUS_OPENED);
		$record->setPaused(false);
		$record->setActualStartTimestamp($record->getTimeHelper()->getUtcNowTimestamp());
		$record->setRecordedStopTimestamp(0);
		$record->setStopOffset(0);
		$record->setActualStopTimestamp(0);
		$record->setRecordedDuration(0);
		$record->setActualBreakLength(0);
		$record->setRecordedBreakLength(0);
		$record->approve(true);

		return $record;
	}

	/**
	 * @param WorktimeRecordForm $workRecordForm
	 * @param $recordStopUtcTimestamp
	 * @param null $stopOffset
	 */
	public function stopWork($workRecordForm, $recordStopUtcTimestamp)
	{
		$this->setLatClose($workRecordForm->latitudeClose);
		$this->setLonClose($workRecordForm->longitudeClose);
		$this->setIpClose($workRecordForm->ipClose);
		$recordStopUtcTimestamp = $recordStopUtcTimestamp ?: $this->getTimeHelper()->getUtcNowTimestamp();
		$this->setRecordedStopTimestamp($recordStopUtcTimestamp);

		if ($this->isOpened() || $this->isClosed())
		{
			$this->setRecordedDuration($this->calculateDurationSince($this->getRecordedStopTimestamp()));
		}
		elseif ($this->isPaused())
		{
			$newBreak = $this->calculateDurationSince($this->getRecordedStopTimestamp()) - $this->getRecordedDuration();
			$this->increaseBreaks($newBreak);
		}

		if ($workRecordForm->stopOffset === null)
		{
			$workRecordForm->stopOffset = $this->getTimeHelper()->getUserUtcOffset($this->getUserId());
		}
		if ($workRecordForm->editedBy === null || (int)$workRecordForm->editedBy === $this->getUserId())
		{
			$this->setStopOffset($workRecordForm->stopOffset);
		}
		if ((int)$this->getActualStopTimestamp() === 0 || $this->getActualStopTimestamp() === null)
		{
			$this->setActualStopTimestamp($this->getTimeHelper()->getUtcNowTimestamp());
		}
		$this->setCurrentStatus(WorktimeRecordTable::STATUS_CLOSED);
		$this->setPaused(false);
		$this->setDateFinish(DateTime::createFromTimestamp($this->getRecordedStopTimestamp()));
		$this->setTimeFinish(TimeHelper::getInstance()->getSecondsFromDateTime($this->buildRecordedStopDateTime()));
		$this->setDuration($this->getTimeFinish() - $this->getTimeStart() - $this->getRecordedBreakLength());
		$this->setPaused(false);
	}

	/**
	 * @param WorktimeRecordForm $recordForm
	 */
	public function pauseWork($recordForm)
	{
		$pauseStartUtcTimestamp = $this->getTimeHelper()->getUtcNowTimestamp();
		$this->setRecordedDuration($this->calculateDurationSince($pauseStartUtcTimestamp));
		$this->setCurrentStatus(WorktimeRecordTable::STATUS_PAUSED);
		$this->setIpClose($recordForm->ipClose);
		$this->setLonClose($recordForm->longitudeClose);
		$this->setLatClose($recordForm->latitudeClose);
		$this->setDateFinish(DateTime::createFromTimestamp($pauseStartUtcTimestamp));
		$this->setTimeFinish(($this->getTimeHelper()->getSecondsFromDateTime($this->getDateFinish())) % 86400);
		$this->setPaused(true);
	}

	public function continueWork()
	{
		$continueUtcTimestamp = $this->getTimeHelper()->getUtcNowTimestamp();

		$this->setDateFinish(null);
		$this->setTimeFinish(null);
		$this->setDuration(0);

		if ($continueUtcTimestamp < $this->getRecordedStopTimestamp())
		{
			$this->setRecordedDuration($this->calculateDurationSince($continueUtcTimestamp));
		}

		$newBreak = $continueUtcTimestamp - $this->getRecordedStartTimestamp() - $this->getRecordedDuration() - $this->getRecordedBreakLength();
		$this->increaseBreaks($newBreak);

		$this->setStopOffset(0);
		$this->setRecordedStopTimestamp(0);
		$this->setActualStopTimestamp(0);
		$this->setCurrentStatus(WorktimeRecordTable::STATUS_OPENED);
		$this->setPaused(false);
	}

	/**
	 * @param WorktimeRecordForm $workRecordForm
	 * @return $this
	 */
	public function updateByForm($workRecordForm)
	{
		$recordedStartTimestamp = $workRecordForm->buildStartTimestampBySecondsAndDate(
			$workRecordForm->useEmployeesTimezone ? $this->getUserId() : $workRecordForm->editedBy,
			$this->getRecordedStartTimestamp()
		);
		if ($this->isTimeNeedToBeSaved($recordedStartTimestamp, $this->getRecordedStartTimestamp()))
		{
			$this->editStartByForm($recordedStartTimestamp);
		}

		$recordedStopTimestamp = $this->buildStopTimestampBySecondsAndDate(
			$workRecordForm->recordedStopSeconds,
			$workRecordForm->recordedStopDateFormatted,
			$workRecordForm->useEmployeesTimezone ? $this->getUserId() : $workRecordForm->editedBy
		);
		if ($this->isTimeNeedToBeSaved($recordedStopTimestamp, $this->getRecordedStopTimestamp()))
		{
			$this->editStopByForm($workRecordForm, $recordedStopTimestamp);
		}

		if ($this->isTimeNeedToBeSaved($workRecordForm->recordedBreakLength, $this->getRecordedBreakLength()))
		{
			$this->setRecordedBreakLength($workRecordForm->recordedBreakLength);
			$this->updateDuration($this->getRecordedStopTimestamp() > 0 ? $this->getRecordedStopTimestamp() : $this->getTimeHelper()->getUtcNowTimestamp());
		}
		return $this;
	}

	private function editStartByForm($recordedStartTimestamp = null)
	{
		$this->defineStartTime($recordedStartTimestamp);
		$this->updateDuration();
	}

	/**
	 * @return mixed|null
	 */
	public function buildStopTimestampBySecondsAndDate($stopSeconds, $stopFormattedDate, $userIdTimezone)
	{
		$recordedStopTimestamp = null;
		if ($stopSeconds === null || $stopSeconds < 0 || !$userIdTimezone)
		{
			return $recordedStopTimestamp;
		}

		if ($stopFormattedDate)
		{
			$timestampUtcForUserDate = $this->getTimeHelper()->getTimestampByUserDate(
				$stopFormattedDate, $userIdTimezone
			);

			if ($timestampUtcForUserDate > 0)
			{
				$userDateTime = $this->getTimeHelper()->createUserDateTimeFromFormat('U', $timestampUtcForUserDate, $userIdTimezone);
				if ($userDateTime)
				{
					$this->getTimeHelper()->setTimeFromSeconds($userDateTime, $stopSeconds);
					$recordedStopTimestamp = $userDateTime->getTimestamp();
				}
			}
		}
		else
		{
			$editorDateStart = $this->getTimeHelper()->createDateTimeFromFormat('U', $this->getRecordedStartTimestamp(), $this->getTimeHelper()->getUserUtcOffset($userIdTimezone));
			if ($editorDateStart)
			{
				$startSeconds = $this->getTimeHelper()->getSecondsFromDateTime($editorDateStart);
				if ($stopSeconds > $startSeconds)
				{
					$this->getTimeHelper()->setTimeFromSeconds($editorDateStart, $stopSeconds);
					$recordedStopTimestamp = $editorDateStart->getTimestamp();
				}
				else
				{
					$editorDateStart->add(new \DateInterval('P1D'));
					$this->getTimeHelper()->setTimeFromSeconds($editorDateStart, $stopSeconds);
					$recordedStopTimestamp = $editorDateStart->getTimestamp();
				}
			}
		}
		return $recordedStopTimestamp;
	}

	private function isTimeNeedToBeSaved($formTimestamp, $recordTimestamp)
	{
		return $formTimestamp !== null
			   && abs($formTimestamp - $recordTimestamp) > 59;
	}

	/**
	 * @param WorktimeRecordForm $workRecordForm
	 */
	private function editStopByForm($workRecordForm, $recordedStopTimestamp = null)
	{
		$this->stopWork($workRecordForm, $recordedStopTimestamp);
	}

	private function updateDuration($endTimestamp = null)
	{
		if ($endTimestamp === null)
		{
			$endTimestamp = $this->getRecordedStopTimestamp();
		}
		if ($endTimestamp > 0)
		{
			$this->setRecordedDuration($this->calculateDurationSince($endTimestamp));
		}
		$this->setDuration($this->getTimeFinish() - $this->getTimeStart() - $this->getRecordedBreakLength());
	}

	private function getTimeHelper()
	{
		if (!$this->timeHelper)
		{
			$this->timeHelper = TimeHelper::getInstance();
		}
		return $this->timeHelper;
	}

	public function isActive()
	{
		return $this->isOpened() || $this->isPaused();
	}

	public function isOpened()
	{
		return $this->getCurrentStatus() === WorktimeRecordTable::STATUS_OPENED;
	}

	public static function isRecordClosed($record)
	{
		$recordObject = $record;
		if (!($recordObject instanceof WorktimeRecord))
		{
			if (!is_array($record))
			{
				return false;
			}
			$recordObject = static::wakeUpRecord($record);
		}
		return $recordObject->isClosed();
	}

	public static function isRecordPaused($record)
	{
		$recordObject = $record;
		if (!($recordObject instanceof WorktimeRecord))
		{
			if (!is_array($record))
			{
				return false;
			}
			$recordObject = static::wakeUpRecord($record);
		}
		return $recordObject->isPaused();
	}

	public static function isRecordOpened($record)
	{
		if (!is_array($record))
		{
			return false;
		}
		$recordObject = $record;
		if (!($recordObject instanceof WorktimeRecord))
		{
			$recordObject = static::wakeUpRecord($record);
		}
		return $recordObject->isOpened();
	}

	public function isClosed()
	{
		return $this->getCurrentStatus() === WorktimeRecordTable::STATUS_CLOSED;
	}

	public function isPaused()
	{
		return $this->getCurrentStatus() === WorktimeRecordTable::STATUS_PAUSED;
	}

	public function isApproved()
	{
		return $this->getApproved() === true;
	}

	/**
	 * @return \Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent|null
	 */
	public function obtainEventByType()
	{
		$args = func_get_args();
		foreach ($args as $eventType)
		{
			$events = $this->obtainWorktimeEvents();
			foreach ($events as $event)
			{
				$type = $event->getEventType();
				if ($event->getEventType() === WorktimeEventTable::EVENT_TYPE_STOP_WITH_ANOTHER_TIME)
				{
					$type = WorktimeEventTable::EVENT_TYPE_EDIT_STOP;
				}
				if ($event->getEventType() === WorktimeEventTable::EVENT_TYPE_START_WITH_ANOTHER_TIME)
				{
					$type = WorktimeEventTable::EVENT_TYPE_EDIT_START;
				}
				if ($type === $eventType)
				{
					return $event;
				}
			}
		}
		$reports = $this->obtainReports();
		if (empty($reports))
		{
			return null;
		}
		$map = [
			WorktimeEventTable::EVENT_TYPE_EDIT_START => WorktimeReportTable::REPORT_TYPE_REPORT_OPEN,
			WorktimeEventTable::EVENT_TYPE_EDIT_STOP => WorktimeReportTable::REPORT_TYPE_REPORT_CLOSE,
			WorktimeEventTable::EVENT_TYPE_EDIT_BREAK_LENGTH => WorktimeReportTable::REPORT_TYPE_REPORT_DURATION,
		];
		foreach ($args as $realEventType)
		{
			foreach ($map as $neededEventType => $neededReportType)
			{
				if ($neededEventType == $realEventType)
				{
					foreach ($reports as $report)
					{
						if ($report->getReportType() == $neededReportType)
						{
							$entity = new WorktimeEvent(false);
							$entity->setReason($report->getReport());
							$entity->setActualTimestamp($report->getTimestampX()->getTimestamp());
							return $entity;
						}
					}
				}
			}
		}
		return null;
	}

	public function defineWorktimeEvents($worktimeEvents)
	{
		$this->worktimeEvents = $worktimeEvents;
	}

	/**
	 * @return WorktimeEventCollection
	 */
	public function obtainWorktimeEvents()
	{
		if ($this->worktimeEvents instanceof WorktimeEventCollection)
		{
			return $this->worktimeEvents;
		}
		try
		{
			return $this->get('WORKTIME_EVENTS');
		}
		catch (\Exception $exc)
		{
			return new WorktimeEventCollection();
		}
	}

	private function calculateDurationSince($endTimestamp)
	{
		return ($endTimestamp - $this->getRecordedStartTimestamp()) - $this->getRecordedBreakLength();
	}

	public function approve(bool $approved = true)
	{
		$wasApproved = $this->isApproved();
		$this->setApproved($approved);
		$this->setActive($this->getApproved());
		if (!$wasApproved && $this->isApproved() && UserHelper::getCurrentUserId() > 0)
		{
			$this->setApprovedBy(UserHelper::getCurrentUserId());
		}
		if (!$approved)
		{
			$this->setApprovedBy(0);
		}
	}

	public static function wakeUpRecord($record)
	{
		if ($record instanceof WorktimeRecord)
		{
			return $record;
		}
		$result = [];
		foreach (\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable::getMap() as $item)
		{
			/** @var \Bitrix\Main\ORM\Fields\Field $item */
			foreach ($record as $name => $field)
			{
				if ($name === $item->getName())
				{
					$result[$name] = $field;
					break;
				}
			}
		}
		return WorktimeRecordTable::wakeUpObject($result);
	}

	public function defineReports($reports)
	{
		$this->reports = $reports;
	}

	public function obtainReports()
	{
		if ($this->reports instanceof EO_WorktimeReport_Collection)
		{
			return $this->reports;
		}
		try
		{
			return $this->get('REPORTS');
		}
		catch (\Exception $exc)
		{
			return [];
		}
	}

	/**
	 * @return Shift|null
	 */
	public function obtainShift()
	{
		if ($this->shift instanceof Shift)
		{
			return $this->shift;
		}
		try
		{
			return $this->get('SHIFT') ? $this->get('SHIFT') : null;
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	/**
	 * @return Schedule|null
	 */
	public function obtainSchedule()
	{
		if ($this->schedule instanceof Schedule)
		{
			return $this->schedule;
		}
		try
		{
			return $this->get('SCHEDULE') ? $this->get('SCHEDULE') : null;
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	public function buildRecordedStopDateTime()
	{
		return $this->getTimeHelper()->createDateTimeFromFormat('U', $this->getRecordedStopTimestamp(), $this->getStopOffset());
	}

	public function buildRecordedStartDateTime(): ?\DateTime
	{
		return $this->getTimeHelper()->createDateTimeFromFormat('U', $this->getRecordedStartTimestamp(), $this->getStartOffset());
	}

	private function defineStartTime($recordedStartTimestamp)
	{
		$this->setRecordedStartTimestamp($recordedStartTimestamp);
		$this->setDateStart(DateTime::createFromTimestamp($this->getRecordedStartTimestamp()));
		$this->setTimeStart(TimeHelper::getInstance()->getSecondsFromDateTime($this->buildRecordedStartDateTime()));
	}

	public function calculateCurrentDuration()
	{
		$endTime = $this->getRecordedStopTimestamp();
		if (!$endTime)
		{
			$endTime = TimeHelper::getInstance()->getUtcNowTimestamp();
		}
		return $endTime - $this->calculateCurrentBreakLength() - $this->getRecordedStartTimestamp();
	}

	public function calculateCurrentBreakLength()
	{
		$break = $this->getRecordedBreakLength();
		if ($this->isPaused())
		{
			$break = TimeHelper::getInstance()->getUtcNowTimestamp() - $this->getRecordedDuration() - $this->getRecordedStartTimestamp();
		}
		return $break;
	}

	public function isRecordedBreakLengthChanged()
	{
		return $this->isTimeLeaksChanged();
	}

	public function getRecordedBreakLength()
	{
		return $this->getTimeLeaks();
	}

	public function setRecordedBreakLength($length)
	{
		$this->setTimeLeaks($length);
		return $this;
	}

	public function defineSchedule(Schedule $schedule)
	{
		$this->schedule = $schedule;
	}

	public function defineShift(Shift $shift)
	{
		$this->shift = $shift;
	}

	private function increaseBreaks($newBreak)
	{
		if ($newBreak > BX_TIMEMAN_ALLOWED_TIME_DELTA)
		{
			$this->setRecordedBreakLength($this->getRecordedBreakLength() + $newBreak);
			$this->setActualBreakLength($this->getActualBreakLength() + $newBreak);
		}
	}

	/**
	 * @return \Bitrix\Timeman\Model\User\User|null
	 */
	public function obtainUser()
	{
		try
		{
			return $this->getUser();
		}
		catch (\Exception $exc)
		{
		}
		return null;
	}

	public function collectRawValues(): array
	{
		return $this->collectValues(\Bitrix\Main\ORM\Objectify\Values::ALL, \Bitrix\Main\ORM\Fields\FieldTypeMask::FLAT);
	}
}