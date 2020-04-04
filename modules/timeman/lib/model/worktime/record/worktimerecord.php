<?php
namespace Bitrix\Timeman\Model\Worktime\Record;

use Bitrix\Main\EO_User_Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Worktime\EventLog\EO_WorktimeEvent_Collection;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEvent;
use Bitrix\Timeman\Model\Worktime\EventLog\WorktimeEventTable;
use Bitrix\Timeman\Model\Worktime\Report\WorktimeReportTable;
use Bitrix\Timeman\Service\DependencyManager;

class WorktimeRecord extends EO_WorktimeRecord
{
	/** @var TimeHelper */
	private $timeHelper;
	private $shift;
	private $schedule;

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
			$recordStartFromForm = $record->buildStartTimestampBySecondsAndDate($recordForm->recordedStartSeconds, $recordForm->recordedStartDateFormatted, $recordForm->editedBy);
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

	public function stopWork($workRecordForm, $recordStopUtcTimestamp, $stopOffset = null)
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
		if ($this->isPaused())
		{
			$newRecordedBreak = $this->calculateBreakLength($this->getRecordedStopTimestamp());
			$this->setBreaksLength($newRecordedBreak);
		}
		if ($stopOffset === null)
		{
			$stopOffset = $this->getTimeHelper()->getUserUtcOffset($this->getUserId());
		}
		$this->setStopOffset($stopOffset);
		if ((int)$this->getActualStopTimestamp() === 0 || $this->getActualStopTimestamp() === null)
		{
			$this->setActualStopTimestamp($this->getTimeHelper()->getUtcNowTimestamp());
		}
		$this->setCurrentStatus(WorktimeRecordTable::STATUS_CLOSED);
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
	}

	private function editStartByForm($recordedStartTimestamp = null)
	{
		$this->defineStartTime($recordedStartTimestamp);
		$this->updateDuration();
	}

	private function buildStartTimestampBySecondsAndDate($startSeconds, $startFormattedDate, $editedBy)
	{
		$startTimestamp = null;
		if (!$startSeconds || !$editedBy)
		{
			return null;
		}
		$timestampUtcForUserDate = $this->getTimeHelper()->getUtcNowTimestamp();
		if ($startFormattedDate)
		{
			$timestampUtcForUserDate = $this->getTimeHelper()->getTimestampByUserDate(
				$startFormattedDate, $editedBy
			);
		}
		elseif ($this->getRecordedStartTimestamp() > 0)
		{
			$timestampUtcForUserDate = $this->getRecordedStartTimestamp();
		}

		if ($timestampUtcForUserDate > 0)
		{
			$userDateTime = $this->getTimeHelper()->createUserDateTimeFromFormat('U', $timestampUtcForUserDate, $editedBy);
			if ($userDateTime)
			{
				$this->getTimeHelper()->setTimeFromSeconds($userDateTime, $startSeconds);
				$startTimestamp = $userDateTime->getTimestamp();
			}
		}
		return $startTimestamp;
	}

	/**
	 * @return mixed|null
	 */
	public function buildStopTimestampBySecondsAndDate($stopSeconds, $stopFormattedDate, $editedBy)
	{
		$recordedStopTimestamp = null;
		if (!$stopSeconds || !$editedBy)
		{
			return $recordedStopTimestamp;
		}

		if ($stopFormattedDate)
		{
			$timestampUtcForUserDate = $this->getTimeHelper()->getTimestampByUserDate(
				$stopFormattedDate, $editedBy
			);

			if ($timestampUtcForUserDate > 0)
			{
				$userDateTime = $this->getTimeHelper()->createUserDateTimeFromFormat('U', $timestampUtcForUserDate, $editedBy);
				if ($userDateTime)
				{
					$this->getTimeHelper()->setTimeFromSeconds($userDateTime, $stopSeconds);
					$recordedStopTimestamp = $userDateTime->getTimestamp();
				}
			}
		}
		else
		{
			$editorDateStart = $this->getTimeHelper()->createDateTimeFromFormat('U', $this->getRecordedStartTimestamp(), $this->getTimeHelper()->getUserUtcOffset($editedBy));
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

	/**
	 * @param WorktimeRecordForm $workRecordForm
	 * @return $this
	 */
	public function updateByForm($workRecordForm)
	{
		$recordedStartTimestamp = $this->buildStartTimestampBySecondsAndDate(
			$workRecordForm->recordedStartSeconds,
			$workRecordForm->recordedStartDateFormatted,
			$workRecordForm->editedBy
		);
		if ($this->isTimeNeedToBeSaved($recordedStartTimestamp, $this->getRecordedStartTimestamp()))
		{
			$this->editStartByForm($recordedStartTimestamp);
		}

		$recordedStopTimestamp = $this->buildStopTimestampBySecondsAndDate(
			$workRecordForm->recordedStopSeconds,
			$workRecordForm->recordedStopDateFormatted,
			$workRecordForm->editedBy
		);
		if ($this->isTimeNeedToBeSaved($recordedStopTimestamp, $this->getRecordedStopTimestamp()))
		{
			$this->editStopByForm($workRecordForm, $recordedStopTimestamp);
		}


		if ($this->isTimeNeedToBeSaved($workRecordForm->recordedBreakLength, $this->getRecordedBreakLength()))
		{
			$this->setRecordedBreakLength($workRecordForm->recordedBreakLength);
			$this->updateDuration();
		}
		return $this;
	}

	public function continueWork($continueUtcTimestamp = null)
	{
		if ($continueUtcTimestamp === null)
		{
			$continueUtcTimestamp = $this->getTimeHelper()->getUtcNowTimestamp();
		}
		$this->setDateFinish(null);
		$this->setTimeFinish(null);
		$this->setDuration(0);

		if ($continueUtcTimestamp < $this->getRecordedStopTimestamp())
		{
			$this->setRecordedDuration($this->calculateDurationSince($continueUtcTimestamp));
		}
		$this->setBreaksLength($this->calculateBreakLength($continueUtcTimestamp));
		$this->setStopOffset(0);
		$this->setRecordedStopTimestamp(0);
		$this->setActualStopTimestamp(0);
		$this->setCurrentStatus(WorktimeRecordTable::STATUS_OPENED);
	}

	private function updateDuration($endTimestamp = null)
	{
		if ($endTimestamp === null)
		{
			$endTimestamp = $this->getRecordedStopTimestamp();
		}
		if ($endTimestamp !== null && (int)$endTimestamp !== 0)
		{
			$this->setRecordedDuration($this->calculateDurationSince($endTimestamp));
		}
		$this->setDuration($this->getTimeFinish() - $this->getTimeStart() - $this->getRecordedBreakLength());
	}

	private function setBreaksLength($newRecordedBreak)
	{
		if ((int)$this->getActualBreakLength() === 0 || $this->getActualBreakLength() === null)
		{
			$this->setActualBreakLength($this->getActualBreakLength() + ($newRecordedBreak - $this->getRecordedBreakLength()));
		}
		if ($newRecordedBreak - $this->getRecordedBreakLength() > BX_TIMEMAN_ALLOWED_TIME_DELTA)
		{
			$this->setRecordedBreakLength($newRecordedBreak);
		}
	}

	private function getTimeHelper()
	{
		if (!$this->timeHelper)
		{
			$this->timeHelper = TimeHelper::getInstance();
		}
		return $this->timeHelper;
	}

	public static function isRecordEdited(&$record)
	{
		return static::isRecordStartEdited($record) || static::isRecordStopEdited($record);
	}

	public static function isRecordStartEdited(&$record)
	{
		if ($record['ACTUAL_START_TIMESTAMP'] == 0 || $record['RECORDED_START_TIMESTAMP'] == 0)
		{
			return false;
		}
		return (int)$record['ACTUAL_START_TIMESTAMP'] !== (int)$record['RECORDED_START_TIMESTAMP'];
	}

	public static function isRecordStopEdited(&$record)
	{
		if ($record['ACTUAL_STOP_TIMESTAMP'] == 0 || $record['RECORDED_STOP_TIMESTAMP'] == 0)
		{
			return false;
		}
		return (int)$record['ACTUAL_STOP_TIMESTAMP'] !== (int)$record['RECORDED_STOP_TIMESTAMP'];
	}

	public static function isRecordApproved($record)
	{
		return $record && $record['APPROVED'] !== null && $record['APPROVED'] == WorktimeRecordTable::APPROVED_YES;
	}

	public function isActive()
	{
		return $this->isOpened() || $this->isPaused();
	}

	public function isOpened()
	{
		return static::isRecordOpened($this);
	}

	public static function isRecordPaused($record)
	{
		return isset($record['CURRENT_STATUS']) ? $record['CURRENT_STATUS'] === WorktimeRecordTable::STATUS_PAUSED : false;
	}

	public static function isRecordOpened($record)
	{
		return isset($record['CURRENT_STATUS']) ? $record['CURRENT_STATUS'] === WorktimeRecordTable::STATUS_OPENED : false;
	}

	public function isClosed()
	{
		return static::isRecordClosed($this);
	}

	public static function isRecordClosed(&$fields)
	{
		return !empty($fields['CURRENT_STATUS']) ? $fields['CURRENT_STATUS'] === WorktimeRecordTable::STATUS_CLOSED : false;
	}

	public function isPaused()
	{
		return $this->getCurrentStatus() === WorktimeRecordTable::STATUS_PAUSED;
	}

	public function isApproved()
	{
		return $this->getApproved() === true;
	}

	private function calculateBreakLength($timestamp)
	{
		return $timestamp - ($this->getRecordedStartTimestamp() + $this->getRecordedDuration());
	}

	public function getLastPauseTimestamp()
	{
		return $this->getRecordedStartTimestamp() + $this->getRecordedDuration();
	}

	/**
	 * @return EO_User_Entity|null
	 */
	public function obtainUser()
	{
		try
		{
			return $this->get('USER');
		}
		catch (\Exception $exc)
		{
			return null;
		}
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

	/**
	 * @return EO_WorktimeEvent_Collection|null
	 */
	public function obtainWorktimeEvents()
	{
		try
		{
			return $this->get('WORKTIME_EVENTS');
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	private function calculateDurationSince($endTimestamp)
	{
		return ($endTimestamp - $this->getRecordedStartTimestamp()) - $this->getRecordedBreakLength();
	}

	public function approve($approved = true)
	{
		$this->setApproved($approved);
		$this->setActive($this->getApproved());
	}

	public function isEligibleToEdit($schedule = null, $shift = null)
	{
		if (!$schedule)
		{
			$schedule = $this->obtainSchedule(['SHIFTS']);
		}
		if ($schedule && !$schedule->isAllowedToEditRecord())
		{
			return false;
		}
		return !$this->isNextShiftStarted($schedule) || $this->isExpired($schedule, $shift);
	}

	public function getDayOfWeek()
	{
		return $this->getTimeHelper()->getDayOfWeek($this->buildRecordedStartDateTime());
	}

	public function isEligibleToReopen($schedule = null, $shift = null)
	{
		if ((int)$this->getRecordedStopTimestamp() === 0)
		{
			return false;
		}
		if (!$schedule)
		{
			$schedule = $this->obtainSchedule(['SHIFTS']);
		}
		if ($schedule && !$schedule->isAllowedToReopenRecord())
		{
			return false;
		}
		return !$this->isNextShiftStarted($schedule);
	}

	/**
	 * @param Schedule $schedule
	 * @return \DateTime|false|null
	 * @throws \Exception
	 */
	private function getNextShiftStart($schedule)
	{
		if (!$schedule
			|| Schedule::isScheduleFlexible($schedule)
			|| ($schedule && empty($schedule->obtainShifts()))
		)
		{
			$startDate = $this->buildRecordedStartDateTime();
			$startDate->add(new \DateInterval('P2D'));
			$startDate->setTime(0, 0, 0);
			return $startDate;
		}
		/** @var Schedule $schedule */
		$recordWeekDay = $this->getDayOfWeek();
		for ($day = 1; $day <= 7; $day++)
		{
			$searchShiftForWeekDay = ($recordWeekDay + $day) % 7;
			$nextClosestShift = $schedule->getShiftForWeekDay($searchShiftForWeekDay);
			if ($nextClosestShift)
			{
				$nextShiftStart = clone $this->buildRecordedStartDateTime();
				$nextShiftStart->modify("+$day days");
				$this->getTimeHelper()->setTimeFromSeconds($nextShiftStart, $nextClosestShift->getWorkTimeStart());
				return $nextShiftStart;
			}
		}
		return null;
	}

	private function isNextShiftStarted($schedule)
	{
		if (!$schedule)
		{
			$schedule = $this->obtainSchedule(['SHIFTS']);
		}
		if (Schedule::isScheduleShifted($schedule))
		{
			return false; // todo shifted schedule logic
		}
		$nextShiftStart = $this->getNextShiftStart($schedule);
		if ($nextShiftStart === null)
		{
			return false;
		}

		$now = $this->getTimeHelper()->getUtcNowTimestamp();
		return $now >= $nextShiftStart->getTimestamp();
	}

	public static function getRecommendedWorktimeStopTimestamp($record, $shift = null)
	{
		$shift = $shift ?: static::obtainShiftForRecord($record);
		if (!$shift)
		{
			return null;
		}
		$idealStart = TimeHelper::getInstance()->getTimestampByUserSecondsFromTimestamp(
			$shift['WORK_TIME_START'],
			$record['RECORDED_START_TIMESTAMP'],
			$record['START_OFFSET']
		);
		return $idealStart + Shift::getShiftDuration($shift);
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

	public static function isRecordExpired($record, $schedule = null, $shift = null)
	{
		if (!$record)
		{
			return false;
		}

		return static::wakeUpRecord($record)->isExpired($schedule, $shift);
	}

	public function isExpired($schedule = null, $shift = null)
	{
		if ($this->getRecordedStopTimestamp() > 0)
		{
			return false;
		}

		return $this->isNextShiftStarted($schedule);
	}

	public function getRecommendedStopTimestamp($shift = null)
	{
		return static::getRecommendedWorktimeStopTimestamp($this, $shift ?: $this->obtainShift());
	}

	/**
	 * @return Schedule|null
	 */
	public static function obtainScheduleForRecord($record, $withEntities = [])
	{
		if ($record['SCHEDULE_ID'] > 0)
		{
			return DependencyManager::getInstance()->getScheduleRepository()->findByIdWith($record['SCHEDULE_ID'], $withEntities);
		}
		return null;
	}

	private static function obtainShiftForRecord($record)
	{
		if ($record['SHIFT_ID'] > 0)
		{
			return DependencyManager::getInstance()->getShiftRepository()->findById($record['SHIFT_ID']);
		}
		return null;
	}

	public function obtainReports()
	{
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
		try
		{
			if (!$this->get('SHIFT') && $this->shift === null)
			{
				$this->shift = false;
				if ($shift = static::obtainShiftForRecord($this))
				{
					$this->shift = $shift;
					$this->set('SHIFT', $shift);
				}
			}
			return $this->get('SHIFT');
		}
		catch (\Exception $exc)
		{
			return null;
		}
	}

	/**
	 * @return Schedule|null
	 */
	public function obtainSchedule($withEntities = [])
	{
		try
		{
			if (!$this->get('SCHEDULE') && $this->schedule === null)
			{
				$this->schedule = false;
				if ($schedule = static::obtainScheduleForRecord($this, $withEntities))
				{
					$this->schedule = $schedule;
					$this->set('SCHEDULE', $schedule);
				}
			}
			return $this->get('SCHEDULE');
		}
		catch (\Exception $exc)
		{
			if ($this->schedule === null)
			{
				$this->schedule = false;
				if ($schedule = static::obtainScheduleForRecord($this, $withEntities))
				{
					$this->schedule = $schedule;
				}
			}
			return $this->schedule === false ? null : $this->schedule;
		}
	}

	public function buildRecordedStopDateTime()
	{
		return $this->getTimeHelper()->createDateTimeFromFormat('U', $this->getRecordedStopTimestamp(), $this->getStopOffset());
	}

	public function buildRecordedStartDateTime()
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
			$endTime = time();
		}
		return $endTime - $this->calculateCurrentBreakLength() - $this->getRecordedStartTimestamp();
	}

	public function calculateCurrentBreakLength()
	{
		$break = $this->getRecordedBreakLength();
		if ($this->isPaused())
		{
			$break = time() - $this->getRecordedDuration() - $this->getRecordedStartTimestamp();
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
}