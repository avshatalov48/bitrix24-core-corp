<?php
namespace Bitrix\Timeman\Helper\Form\Worktime;

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\User\User;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;

class RecordFormHelper
{
	/** @var TimeHelper */
	private $timeHelper;

	public function __construct()
	{
		$this->timeHelper = TimeHelper::getInstance();
	}

	/**
	 * @param User $mainUser
	 * @param User|null $oppositeUser
	 * @param \DateTime $firstDateTime
	 * @param \DateTime $secondDateTime
	 */
	public function buildTimeDifferenceHint(User $mainUser, $oppositeUser, $format, $firstDateTime, $secondDateTime = null)
	{
		$resultText = $this->formatDateTime($firstDateTime, $format, $mainUser->obtainUtcOffset());
		if ($secondDateTime !== null)
		{
			$resultText .= ' - ' . $this->formatDateTime($secondDateTime, $format, $mainUser->obtainUtcOffset());
		}

		if ($oppositeUser instanceof User && $mainUser->obtainUtcOffset() === $oppositeUser->obtainUtcOffset())
		{
			return $resultText;
		}

		$resultText .= ' ' . $this->buildUtcOffsetText($firstDateTime, $mainUser);
		if (!($oppositeUser instanceof User))
		{
			return $resultText;
		}

		$resultText .= '<br>' . $this->formatDateTime($firstDateTime, $format, $oppositeUser->obtainUtcOffset());
		if ($secondDateTime !== null)
		{
			$resultText .= ' - ' . $this->formatDateTime($secondDateTime, $format, $oppositeUser->obtainUtcOffset());
		}
		$resultText .= ' ' . $this->buildUtcOffsetText($firstDateTime, $oppositeUser);


		return $resultText;
	}

	/**
	 * @param \DateTime|string $dateTime
	 * @param $format
	 * @param $offset
	 * @return string
	 */
	private function formatDateTime($dateTime, $format, $offset)
	{
		if ($dateTime instanceof \DateTime)
		{
			$dateTimeNewOffset = clone $dateTime;
			$dateTimeNewOffset->setTimezone($this->timeHelper->createTimezoneByOffset($offset));
			return $this->timeHelper->formatDateTime($dateTimeNewOffset, $format);
		}
		return is_string($dateTime) ? $dateTime : '';
	}

	/**
	 * @param \DateTime $dateTime
	 * @param User $user
	 */
	private function buildUtcOffsetText($dateTime, User $user)
	{
		if (!($dateTime instanceof \DateTime))
		{
			return '';
		}
		$dateTimeNewOffset = clone $dateTime;
		$dateTimeNewOffset->setTimezone($this->timeHelper->createTimezoneByOffset($user->obtainUtcOffset()));

		if ($user->obtainUtcOffset() === 0)
		{
			return '(UTC)';
		}
		else
		{
			$hoursMinutes = TimeHelper::getInstance()->convertSecondsToHoursMinutes($user->obtainUtcOffset());
			$name = '';
			if (!empty($user->obtainTimeZone()))
			{
				$name = ' ' . $user->obtainTimeZone();
			}
			if ($user->obtainUtcOffset() < 0)
			{
				return '(UTC -' . substr($hoursMinutes, 1) . ')' . $name;
			}
			return '(UTC +' . $hoursMinutes . ')' . $name;
		}
	}

	/**
	 * @param $violations
	 * @param $notices
	 * @param WorktimeRecord $record
	 * @return string
	 */
	public function getViolationCode($violations, $notices, $record)
	{
		if (!$record)
		{
			return '';
		}
		$hasEditedViolations = !empty($violations);
		$hasOtherViolations = !empty($notices);
		if (!$hasEditedViolations && $hasOtherViolations)
		{
			return 'gray';
		}
		elseif ($hasEditedViolations && $record->isApproved() && !$hasOtherViolations)
		{
			return 'blue';
		}
		elseif ($hasEditedViolations && !$record->isApproved())
		{
			return 'red';
		}
		elseif ($hasEditedViolations && $record->isApproved() && $hasOtherViolations)
		{
			return 'orange';
		}
		return '';
	}

	public function getCssClassForViolations($violations, $notices, $record)
	{
		switch ($this->getViolationCode($violations, $notices, $record))
		{
			case 'gray':
				return 'timeman-record-violation-icon-notice';
			case 'blue':
				return 'timeman-record-violation-icon-confirmed';
			case 'red':
				return 'timeman-record-violation-icon-warning';
			case 'orange':
				return 'timeman-record-violation-icon-alert';
		}
		return '';
	}
}