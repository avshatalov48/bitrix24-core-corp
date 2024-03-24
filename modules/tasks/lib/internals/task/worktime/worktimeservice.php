<?php

namespace Bitrix\Tasks\Internals\Task\WorkTime;

use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

class WorkTimeService
{
	public const WORK_TIME_OPTION = 'MATCH_WORK_TIME';
	private const OPTION = 'task_edit_form_state';

	protected int $userId;
	private bool $matchWorkTime;
	private Calendar $calendar;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
		$this->init();
	}

	public function getClosestWorkTime(int $offsetInDays = 7): DateTime
	{
		$dateTime = (new DateTime())->add("{$offsetInDays} days");

		$dateTime = $this->isHoliday($dateTime->getTimestamp()) && $this->matchWorkTime
			? $this->calendar->getClosestWorkTime($dateTime)
			: $dateTime;

		$settings = $this->calendar::getSettings();
		$endTimeHour = (int)($settings['HOURS']['END']['H'] ?? null);
		$endTimeMinute = (int)($settings['HOURS']['END']['M'] ?? null);

		return (new DateTime())
			->setDate($dateTime->getYear(), $dateTime->getMonth(), $dateTime->getDay())
			->setTime($endTimeHour, $endTimeMinute);
	}

	public function isWorkTime(int $timestamp): bool
	{
		return $this->calendar->isWorkTime(
			DateTime::createFromUserTimeGmt(UI::formatDateTime($timestamp))
		);
	}

	public function isHoliday(int $timestamp): bool
	{
		return !$this->calendar->isWorkTime(
			DateTime::createFromUserTimeGmt(UI::formatDateTime($timestamp))
		);
	}

	private function init(): void
	{
		$flags = Type::unSerializeArray(User::getOption(static::OPTION, $this->userId));
		$this->matchWorkTime = $flags['FLAGS'][static::WORK_TIME_OPTION] ?? false;
		$this->calendar = new Calendar();
	}
}