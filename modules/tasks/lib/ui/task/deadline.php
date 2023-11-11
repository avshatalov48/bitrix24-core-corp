<?php

namespace Bitrix\Tasks\UI\Task;

use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use CTasks;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/tasks/lib/grid/task/row/content/date/deadline.php');

class Deadline
{
	use DateTrait;

	public function __construct()
	{

	}

	/**
	 * @param int $status
	 * @param string|null $deadline
	 * @return array
	 */
	public function buildState(int $status, string $deadline = null): array
	{
		if ($status === Status::COMPLETED)
		{
			return [
				'state' => '',
				'color' => '',
				'fill' => true,
			];
		}

		if ($status === Status::DEFERRED)
		{
			return [
				'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_DEFERRED'),
				'color' => 'default',
				'fill' => false,
			];
		}

		if ($status === \Bitrix\Tasks\Internals\Task\Status::SUPPOSEDLY_COMPLETED)
		{
			return [
				'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_SUPPOSEDLY_COMPLETED'),
				'color' => 'warning',
				'fill' => false,
			];
		}

		if (
			!$deadline
			|| !($timestamp = $this->getDateTimestamp($deadline))
		)
		{
			return [
				'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_NO_DEADLINE'),
				'color' => 'light',
				'fill' => true,
			];
		}

		$expiredTime = $this->getExpiredTime($timestamp);
		$timeFormat = UI::getHumanTimeFormat($timestamp);
		$deadlineTime = ($timeFormat ? ', '.DateTime::createFromTimestamp($timestamp)->format($timeFormat) : '');
		$deadlineDateTime = $this->formatDate($deadline);

		$states = [
			'isExpired' => [
				'state' => $expiredTime,
				'color' => 'danger',
				'fill' => true,
			],
			'isToday' => [
				'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_TODAY', ['#TIME#' => $deadlineTime]),
				'color' => 'warning',
				'fill' => true,
			],
			'isTomorrow' => [
				'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_TOMORROW', ['#TIME#' => $deadlineTime]),
				'color' => 'success',
				'fill' => true,
			],
			'isThisWeek' => [
				'state' => $deadlineDateTime,
				'color' => 'primary',
				'fill' => true,
			],
			'isNextWeek' => [
				'state' => $deadlineDateTime,
				'color' => 'secondary',
				'fill' => true,
			],
			'isWoDeadline' => [
				'state' => $deadlineDateTime,
				'color' => 'light',
				'fill' => true,
			],
			'isMoreThatTwoWeeks' => [
				'state' => $deadlineDateTime,
				'color' => 'default',
				'fill' => true,
			],
		];

		foreach ($states as $function => $data)
		{
			if (method_exists(__CLASS__, $function) && $this->$function($timestamp))
			{
				return $data;
			}
		}

		return [];
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private function isToday(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$today = DateTime::createFromTimestamp($this->getNow());

		return $this->checkMatchDates($deadline, [$today]);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private function isTomorrow(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$tomorrow = DateTime::createFromTimestamp($this->getNow());
		$tomorrow->addDay(1);

		return $this->checkMatchDates($deadline, [$tomorrow]);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private function isThisWeek(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$today = DateTime::createFromTimestamp($this->getNow());
		$firstDay = $today->setDate(
			$today->format('Y'),
			$today->format('m'),
			((int)$today->format('j') - (int)$today->format('N') + 1)
		);
		$thisWeekDays = [clone $firstDay];

		for ($i = 2; $i <= 7; $i++)
		{
			$firstDay->addDay(1);
			$clone = clone $firstDay;
			$thisWeekDays[] = $clone;
		}

		return $this->checkMatchDates($deadline, $thisWeekDays);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private function isNextWeek(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$today = DateTime::createFromTimestamp($this->getNow());
		$today->addDay(7);
		$firstDay = $today->setDate(
			$today->format('Y'),
			$today->format('m'),
			((int)$today->format('j') - (int)$today->format('N') + 1)
		);
		$nextWeekDays = [clone $firstDay];

		for ($i = 2; $i <= 7; $i++)
		{
			$firstDay->addDay(1);
			$clone = clone $firstDay;
			$nextWeekDays[] = $clone;
		}

		return $this->checkMatchDates($deadline, $nextWeekDays);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private function isMoreThatTwoWeeks(int $timestamp): bool
	{
		return true;
	}

	/**
	 * @param int $timestamp
	 * @return string
	 */
	private function getExpiredTime(int $timestamp): string
	{
		$extensionPrefix = 'TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_EXPIRED_';
		$extensions = [
			'YEAR' => [
				'value' => 31536000,
				'text' => Loc::getMessage($extensionPrefix.'YEAR'),
			],
			'MONTH' => [
				'value' => 2592000,
				'text' => Loc::getMessage($extensionPrefix.'MONTH'),
			],
			'WEEK' => [
				'value' => 604800,
				'text' => Loc::getMessage($extensionPrefix.'WEEK'),
			],
			'DAY' => [
				'value' => 86400,
				'text' => Loc::getMessage($extensionPrefix.'DAY'),
			],
			'HOUR' => [
				'value' => 3600,
				'text' => Loc::getMessage($extensionPrefix.'HOUR'),
			],
			'MINUTE' => [
				'value' => 60,
				'text' => Loc::getMessage($extensionPrefix.'MINUTE'),
			],
		];

		$today = (new DateTime())->getTimestamp() + \CTimeZone::GetOffset();
		$delta = $today - $timestamp;
		if ($delta < 0)
		{
			return '';
		}

		$expiredTime = Loc::getMessagePlural($extensionPrefix . 'MINUTE', 1, [
			'#TIME#' => 1,
		]);
		foreach ($extensions as $key => $extension)
		{
			$value = (int)floor($delta / $extension['value']);
			if ($value >= 1)
			{
				$expiredTime = Loc::getMessagePlural($extensionPrefix . $key, $value, [
					'#TIME#' => $value,
				]);
				break;
			}
			$delta -= $value * $extension['value'];
		}

		return $expiredTime;
	}

	/**
	 * @param DateTime $date
	 * @param array $datesToMatch
	 * @return bool
	 */
	private function checkMatchDates(DateTime $date, array $datesToMatch): bool
	{
		foreach ($datesToMatch as $dateToMatch)
		{
			/** @var DateTime $dateToMatch */
			if ($date->format('Y-m-d') === $dateToMatch->format('Y-m-d'))
			{
				return true;
			}
		}

		return false;
	}
}