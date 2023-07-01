<?php
namespace Bitrix\Timeman\Component\WorktimeGrid;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Helper\Form\Worktime\RecordFormHelper;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Model\User\User;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Security\UserPermissionsManager;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Worktime\Action\WorktimeRecordManager;
use Bitrix\Timeman\Service\Worktime\Violation\WorktimeViolation;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class TemplateParams
{
	/** @var WorktimeRecord */
	public $record;
	public $hintDataset = '';
	/** @var Shift */
	public $shift;
	/** @var Schedule */
	public $schedule;
	/** @var ShiftPlan */
	public $shiftPlan;
	/** @var string */
	public $recordLink = '';
	/** @var \Bitrix\Main\Type\DateTime */
	public $drawingDate;
	public $showAddShiftPlanBtn = false;
	/** @var int $userId */
	public $userId;
	/** @var array|null */
	public $absence = [];
	/** @var string */
	public $formattedStart = '';
	/** @var string */
	public $formattedEnd = '';
	/** @var string */
	public $formattedDuration = '';

	/** @var bool */
	private static $showDatesInCurrentUserTimezone;
	/** @var UserPermissionsManager */
	private static $userPermissionManager;
	private $utcShiftStart;
	/** @var array */
	public $violationsCommon = [];
	/** @var array */
	public $violationsIndividual = [];
	/** @var array */
	public $noticesCommon = [];
	/** @var array */
	public $noticesIndividual = [];
	/** @var User */
	private $user;
	/** @var User */
	private $currentUser;
	/** @var \DateTime */
	private $endDate;
	/** @var \DateTime */
	private $startDate;
	/** @var RecordFormHelper */
	private $recordFormHelper;
	/** @var TimeHelper */
	private $timeHelper;
	private $shortTimeFormat;
	private $isShiftPlan = false;
	/** @var WorktimeRecordManager */
	private $recordManager;

	/**
	 * TemplateParams constructor.
	 * @param User $user
	 * @param User $currentUser
	 * @param $record
	 * @param $schedule
	 * @param $shift
	 * @param $shiftPlan
	 * @param $drawingDate
	 * @param bool $isShiftPlan
	 */
	public function __construct(User $user,
								User $currentUser,
								?WorktimeRecordManager $recordManager,
								?Schedule $schedule,
								?Shift $shift,
								?ShiftPlan $shiftPlan,
								$drawingDate,
								$isShiftPlan = false
	)
	{
		if ($user->getId() === null)
		{
			return;
		}
		$this->isShiftPlan = $isShiftPlan;
		$this->recordFormHelper = new RecordFormHelper();
		$this->timeHelper = TimeHelper::getInstance();
		$this->shortTimeFormat = Context::getCurrent()->getCulture()->getShortTimeFormat();
		$this->user = $user;
		$this->userId = $this->user->getId();
		$this->currentUser = $currentUser;
		$this->record = $recordManager ? $recordManager->getRecord() : null;
		$this->recordManager = $recordManager;
		$this->schedule = $schedule;
		$this->shift = $shift;
		$this->shiftPlan = $shiftPlan;
		$this->drawingDate = $drawingDate;

		list($this->startDate, $this->endDate) = $this->buildFormattedStartEndDates($this->getMainUser()->getId());
		if ($this->startDate)
		{
			$this->formattedStart = TimeHelper::getInstance()->convertSecondsToHoursMinutesAmPm(
				TimeHelper::getInstance()->getSecondsFromDateTime($this->startDate)
			);
		}
		$this->formattedEnd = '...';
		if ($this->endDate)
		{
			$this->formattedEnd = TimeHelper::getInstance()->convertSecondsToHoursMinutesAmPm(
				TimeHelper::getInstance()->getSecondsFromDateTime($this->endDate)
			);
		}

		if ($this->record)
		{
			if (static::$userPermissionManager->canReadWorktime($this->record->getUserId()))
			{
				$this->recordLink = DependencyManager::getInstance()->getUrlManager()->getUriTo('recordReport', ['RECORD_ID' => $this->record->getId()]);
			}

			$this->formattedDuration = TimeHelper::getInstance()->convertSecondsToHoursMinutesLocal($this->record->calculateCurrentDuration());
			$this->formattedDuration = preg_replace('#([^0-9]+)#m', '<span>$1</span>', $this->formattedDuration);
		}

		$this->initHint();
	}

	public function isRecordExpired()
	{
		return $this->recordManager && $this->recordManager->isRecordExpired();
	}

	private function buildFormattedStartEndDates($timezoneUserId)
	{
		$formattedStart = null;
		$formattedEnd = null;
		if ($this->record)
		{
			$formattedStart = $this->buildDateInUserTimezone($this->record->getRecordedStartTimestamp(), $timezoneUserId);

			if ($this->record->getRecordedStopTimestamp() > 0)
			{
				$formattedEnd = $this->buildDateInUserTimezone($this->record->getRecordedStopTimestamp(), $timezoneUserId);
			}
		}
		elseif ($this->shiftPlan && $this->shift)
		{
			$startDate = $this->buildDateInUserTimezone($this->shift->buildUtcStartByShiftplan($this->shiftPlan), $timezoneUserId);
			if ($startDate)
			{
				$formattedStart = $startDate;
			}
			$endDate = $this->buildDateInUserTimezone($this->shift->buildUtcEndByShiftplan($this->shiftPlan), $timezoneUserId);
			if ($endDate)
			{
				$formattedEnd = $endDate;
			}
		}
		elseif ($this->shift)
		{
			$startDate = $this->buildDateInUserTimezone($this->buildUtcShiftStart(), $timezoneUserId);
			if ($startDate)
			{
				$formattedStart = $startDate;
			}
			$endDate = $this->buildDateInUserTimezone($this->buildUtcShiftEnd(), $timezoneUserId);
			if ($endDate)
			{
				$formattedEnd = $endDate;
			}
		}

		return [$formattedStart, $formattedEnd];
	}

	private function initHint()
	{
		$hint = null;

		$addBtn = !$this->record && !$this->shiftPlan && !$this->absence;
		$workedByShifted = $this->record && ($this->shiftPlan || Schedule::isScheduleShifted($this->schedule));
		$usersInDifferentTimezones = $this->getMainUser()->obtainUtcOffset() !== $this->getOppositeUser()->obtainUtcOffset();
		if (($addBtn || $workedByShifted) && $this->shift)
		{
			$hint = $this->buildHintWithShiftName();
			if (($workedByShifted || ($addBtn && $usersInDifferentTimezones)) && $this->isShiftPlan)
			{
				$hint .= '<br><br>';
				$hint .= $this->recordFormHelper->buildTimeDifferenceHint(
					$this->getMainUser(),
					$this->getOppositeUser(),
					$this->shortTimeFormat,
					$this->startDate instanceof \DateTime ? $this->startDate : $this->formattedStart,
					$this->endDate instanceof \DateTime ? $this->endDate : $this->formattedEnd
				);
			}
		}
		elseif ($usersInDifferentTimezones)
		{
			$hint = Loc::getMessage('TM_WORKTIME_GRID_RECORD_INFO_TITLE') . '<br><br>';
			$hint .= $this->recordFormHelper->buildTimeDifferenceHint(
				$this->getMainUser(),
				$this->getOppositeUser(),
				$this->shortTimeFormat,
				$this->startDate instanceof \DateTime ? $this->startDate : $this->formattedStart,
				$this->endDate instanceof \DateTime ? $this->endDate : $this->formattedEnd
			);
		}

		if ($hint !== null)
		{
			$attrs = [
				'hint-no-icon' => true,
				'hint' => $hint,
			];
			if (($workedByShifted && $this->shift) || $usersInDifferentTimezones)
			{
				$this->hintDataset .= 'data-hint-html';
			}
			foreach ($attrs as $name => $value)
			{
				$this->hintDataset .= ' data-' . $name . '="' . htmlspecialcharsbx($value) . '"';
			};
		}
	}

	public function getViolationCommonCss()
	{
		return $this->recordFormHelper->getCssClassForViolations($this->violationsCommon, $this->noticesCommon, $this->record);
	}

	public function getViolationIndividualCss()
	{
		return $this->recordFormHelper->getCssClassForViolations($this->violationsIndividual, $this->noticesIndividual, $this->record);
	}

	public function getViolationCommonHint()
	{
		return $this->buildViolationHint($this->violationsCommon, $this->noticesCommon);
	}

	public function getViolationIndividualHint()
	{
		return $this->buildViolationHint($this->violationsIndividual, $this->noticesIndividual);
	}

	private function buildViolationHint($violations, $notices)
	{
		if (!$this->record)
		{
			return '';
		}

		switch ($this->recordFormHelper->getViolationCode($violations, $notices, $this->record))
		{
			case 'gray':
				$violationTitle = $this->getHintTitleNotices();
				return $this->buildViolationsText($violationTitle, $notices);
			case 'blue':
				$violationTitle = $this->getHintTitleApproved();
				return $this->buildViolationsText($violationTitle, $violations);
			case 'red':
				$violationTitle = $this->getHintApprovalRequired();
				$violationHint = $this->buildViolationsText($violationTitle, $violations);
				if (!empty($notices))
				{
					$violationTitle = $this->getHintTitleNotices();
					$violationHint .= '<br>' . $this->buildViolationsText($violationTitle, $notices);
				}
				return $violationHint;
			case 'orange':
				$violationTitle = $this->getHintTitleApproved();
				$violationHint = $this->buildViolationsText($violationTitle, $violations);
				$violationTitle = $this->getHintTitleNotices();
				return $violationHint . '<br>' . $this->buildViolationsText($violationTitle, $notices);
			default:
				break;
		}

		return '';
	}

	public static function isUsingCurrentUserTimezone()
	{
		return static::$showDatesInCurrentUserTimezone;
	}

	public static function setShowDatesInCurrentUserTimezone($value)
	{
		static::$showDatesInCurrentUserTimezone = (bool)$value;
	}

	public static function initCurrentUserPermissionsManager($permissionsManager)
	{
		static::$userPermissionManager = $permissionsManager;
	}

	/**
	 * @param \DateTime|Date $utcDateTime
	 * @param $dateTimeFormat
	 * @param $showDatesInCurrentUserTimezone
	 * @param $userId
	 * @return \DateTime|null
	 */
	public static function buildDateInShowingTimezone($utcTimestamp, $userId, $currentUserId)
	{
		$offsetUserId = static::isUsingCurrentUserTimezone() ? $currentUserId : $userId;
		return (new TemplateParams(new User(), new User(), null, null, null, null, null, false))
			->buildDateInUserTimezone($utcTimestamp, $offsetUserId);
	}

	private function buildDateInUserTimezone($utcTimestamp, $userId)
	{
		if ($utcTimestamp instanceof \DateTime)
		{
			$dateTime = clone $utcTimestamp;
		}
		else
		{
			$createdDateTime = TimeHelper::getInstance()->createDateTimeFromFormat('U', $utcTimestamp, 0);
			if (!$createdDateTime)
			{
				return null;
			}

			$dateTime = clone $createdDateTime;
		}

		$tz = TimeHelper::getInstance()->getUserTimezone($userId);

		return $dateTime->setTimezone($tz);
	}

	public function isShiftedSchedule()
	{
		return $this->schedule && $this->schedule->isShifted();
	}

	public function buildUtcShiftEnd()
	{
		return $this->buildUtcShiftTime($this->shift->getWorkTimeEnd());
	}

	public function buildUtcShiftStartFormatted()
	{
		return $this->buildUtcShiftStart()->format(ShiftPlanTable::DATE_FORMAT);
	}

	public function buildUtcShiftStart()
	{
		if ($this->shiftPlan)
		{
			return $this->shiftPlan->getDateAssignedUtc();
		}
		if ($this->utcShiftStart === null)
		{
			$this->utcShiftStart = $this->buildUtcShiftTime($this->shift->getWorkTimeStart());
		}
		return $this->utcShiftStart;
	}

	private function buildUtcShiftTime($seconds)
	{
		if (TemplateParams::isUsingCurrentUserTimezone())
		{
			$v = $seconds - TimeHelper::getInstance()->getUserUtcOffset($this->user->getId());
			$m = TimeDictionary::SECONDS_PER_DAY;
			$utcStartSeconds = ($v % $m + $m) % $m;
			$v = $utcStartSeconds + TimeHelper::getInstance()->getUserUtcOffset($this->currentUser->getId());
			$seconds = ($v % $m + $m) % $m;
			$date = TimeHelper::getInstance()->createDateTimeFromFormat(
				'Y-m-d H:i',
				$this->drawingDate->format('Y-m-d') . ' ' . TimeHelper::getInstance()->convertSecondsToHoursMinutes($seconds),
				TimeHelper::getInstance()->getUserUtcOffset($this->currentUser->getId())
			);
		}
		else
		{
			$date = TimeHelper::getInstance()->createDateTimeFromFormat(
				'Y-m-d H:i',
				$this->drawingDate->format('Y-m-d') . ' ' . TimeHelper::getInstance()->convertSecondsToHoursMinutes($seconds),
				TimeHelper::getInstance()->getUserUtcOffset($this->user->getId())
			);
		}
		$date->setTimezone(new \DateTimeZone('UTC'));
		return $date;
	}

	/**
	 * @param WorktimeViolation $violation
	 * @return string
	 */
	private function buildViolationText($violation, $userGender)
	{
		$text = '';
		$formattedTime = TimeHelper::getInstance()->convertSecondsToHoursMinutesLocal($violation->violatedSeconds, false);
		if (strncmp('-', $formattedTime, 1) === 0)
		{
			$formattedTime = mb_substr($formattedTime, 1);
		}
		$editedText = Loc::getMessage('TM_WORKTIME_STATS_EDITED_MALE', ['#TIME#' => $formattedTime]);
		if ($userGender === 'F')
		{
			$editedText = Loc::getMessage('TM_WORKTIME_STATS_EDITED_FEMALE', ['#TIME#' => $formattedTime]);
		}
		if (!in_array($violation->type, [WorktimeViolation::TYPE_EDITED_BREAK_LENGTH, WorktimeViolation::TYPE_MIN_DAY_DURATION]))
		{
			$violationDateTime = static::buildDateInShowingTimezone($violation->recordedTimeValue, $violation->userId, $this->currentUser->getId());
			$recordedSeconds = $this->recordFormHelper->buildTimeDifferenceHint($this->getMainUser(), $this->getOppositeUser(), $this->shortTimeFormat, $violationDateTime);
		}
		else
		{
			$recordedSeconds = TimeHelper::getInstance()->convertSecondsToHoursMinutes($violation->recordedTimeValue);
		}

		switch ($violation->type)
		{
			case WorktimeViolation::TYPE_EDITED_BREAK_LENGTH:
				$text = Loc::getMessage('TM_WORKTIME_STATS_BREAK_TITLE');
				$formattedTime = $editedText;
				break;
			case WorktimeViolation::TYPE_LATE_START:
			case WorktimeViolation::TYPE_SHIFT_LATE_START:
				$text = Loc::getMessage('TM_WORKTIME_STATS_START_TITLE');
				$extraText = Loc::getMessage('TM_WORKTIME_STATS_START_LATE_MALE', ['#TIME#' => $formattedTime]);
				if ($userGender === 'F')
				{
					$extraText = Loc::getMessage('TM_WORKTIME_STATS_START_LATE_FEMALE', ['#TIME#' => $formattedTime]);
				}
				$formattedTime = $extraText;
				break;
			case WorktimeViolation::TYPE_EDITED_START:
				$text = Loc::getMessage('TM_WORKTIME_STATS_START_TITLE');
				$formattedTime = $editedText;
				break;
			case WorktimeViolation::TYPE_EARLY_START:
				$text = Loc::getMessage('TM_WORKTIME_STATS_START_TITLE');
				$formattedTime = Loc::getMessage('TM_WORKTIME_STATS_EARLY', ['#TIME#' => $formattedTime]);
				break;
			case WorktimeViolation::TYPE_MIN_DAY_DURATION:
				$text = Loc::getMessage('TM_WORKTIME_STATS_DURATION_VIOLATION');
				$extraText = Loc::getMessage('TM_WORKTIME_STATS_DURATION_MALE', ['#TIME#' => $formattedTime]);
				if ($userGender === 'F')
				{
					$extraText = Loc::getMessage('TM_WORKTIME_STATS_DURATION_FEMALE', ['#TIME#' => $formattedTime]);
				}
				$formattedTime = $extraText;
				break;
			case WorktimeViolation::TYPE_EARLY_ENDING:
				$text = Loc::getMessage('TM_WORKTIME_STATS_STOP_TITLE');
				$formattedTime = Loc::getMessage('TM_WORKTIME_STATS_EARLY', ['#TIME#' => $formattedTime]);
				break;
			case WorktimeViolation::TYPE_LATE_ENDING:
				$text = Loc::getMessage('TM_WORKTIME_STATS_STOP_TITLE');
				$formattedTime = Loc::getMessage('TM_WORKTIME_STATS_LATE', ['#TIME#' => $formattedTime]);
				break;
			case WorktimeViolation::TYPE_EDITED_ENDING:
				$text = Loc::getMessage('TM_WORKTIME_STATS_STOP_TITLE');
				$formattedTime = $editedText;
				break;
			default:
				break;
		}

		return $text . ': '
			   . $recordedSeconds
			   . "<span class=\"tm-grid-worktime-popup-violation\">&nbsp;("
			   . $formattedTime
			   . ')</span>';
	}

	public function setViolations($violations, $gender)
	{
		foreach ((array)$violations as $violation)
		{
			$text = $this->buildViolationText($violation, $gender);
			if (in_array($violation->type, [
				WorktimeViolation::TYPE_EDITED_BREAK_LENGTH,
				WorktimeViolation::TYPE_EDITED_START,
				WorktimeViolation::TYPE_EDITED_ENDING,
			], true))
			{
				if ($violation->violationRules->isForAllUsers())
				{
					$this->violationsCommon[] = $text;
				}
				else
				{
					$this->violationsIndividual[] = $text;
				}
			}
			else
			{
				if ($violation->violationRules->isForAllUsers())
				{
					$this->noticesCommon[] = $text;
				}
				else
				{
					$this->noticesIndividual[] = $text;
				}
			}
		}
	}

	public static function getDayCellIdByData($userId, $date)
	{
		if ($date instanceof Date || $date instanceof \DateTime)
		{
			return $userId . '_' . $date->format(ShiftPlanTable::DATE_FORMAT);
		}
		return $userId . '_' . $date;
	}

	public function getDayCellId()
	{
		return $this->user->getId() . '_' . $this->drawingDate->format(ShiftPlanTable::DATE_FORMAT);
	}

	public function getAbsenceDrawTitle()
	{
		return empty($this->absence['ABSENCE_TITLE']) ? 'N' : 'Y';
	}

	private function getMainUser()
	{
		return static::isUsingCurrentUserTimezone() ? $this->currentUser : $this->user;
	}

	private function getOppositeUser()
	{
		return static::isUsingCurrentUserTimezone() ? $this->user : $this->currentUser;
	}

	private function buildHintWithShiftName()
	{
		if (!$this->shift)
		{
			return '';
		}
		$hint = $this->shift->getName() . ' (';
		$hint .= $this->timeHelper->formatDateTime(
			$this->timeHelper->createDateTimeFromFormat('H:i', $this->timeHelper->convertSecondsToHoursMinutes($this->shift->getWorkTimeStart(), 0)),
			$this->shortTimeFormat
		);
		$hint .= ' - ' . $this->timeHelper->formatDateTime(
				$this->timeHelper->createDateTimeFromFormat('H:i', $this->timeHelper->convertSecondsToHoursMinutes($this->shift->getWorkTimeEnd(), 0)),
				$this->shortTimeFormat
			);
		$hint .= ')';
		return $hint;
	}

	private function getHintTitleNotices()
	{
		return '<span class="tm-violation-hint-title">' . Loc::getMessage('TM_WORKTIME_STATS_WARNING_TEXT') . "</span><br>";
	}

	private function getHintTitleApproved()
	{
		return '<span class="tm-violation-hint-title">' . Loc::getMessage('TM_WORKTIME_STATS_APPROVED') . "</span><br>";
	}

	private function buildViolationsText($violationTitle, $violations)
	{
		$resultHint = false;
		foreach ($violations as $notice)
		{
			if ($resultHint === false)
			{
				$resultHint = $violationTitle;
			}
			$resultHint .= ' ' . $notice . '<br>';
		}
		return $resultHint;
	}

	private function getHintApprovalRequired()
	{
		return '<span class="tm-violation-hint-title">' . Loc::getMessage('TM_WORKTIME_STATS_APPROVAL_REQUIRED') . "</span><br>";
	}
}