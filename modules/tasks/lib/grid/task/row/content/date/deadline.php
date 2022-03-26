<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\Date;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content\Date;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;
use CTasks;
use CTasksTools;
use CTimeZone;

/**
 * Class Deadline
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\Date
 */
class Deadline extends Date
{
	private const BXT_SELECTOR = 'bxt-tasks-grid-deadline';
	private static $workTimeSettings = [];

	public function prepare()
	{
		$row = $this->getRowData();

		$state = $this->getDeadlineStateData();
		$timestamp = ($row['DEADLINE'] ? $this->getDateTimestamp($row['DEADLINE']) : $this->getCompanyWorkTimeEnd());

		$jsDeadline = DateTime::createFromTimestamp($timestamp - CTimeZone::GetOffset());
		$text = ($state['state'] ?: $this->formatDate($row['DEADLINE']));

		$onClick = '';
		$link = '';

		$gridLabel = [
			'html' => '<span class="'.self::BXT_SELECTOR.'">'.$text.'</span>',
		];

		if ($row['ACTION']['CHANGE_DEADLINE'])
		{
			$taskId = (int)$row['ID'];
			$onClick = "onclick=\"BX.Tasks.GridActions.onDeadlineChangeClick({$taskId}, this, '{$jsDeadline}'); event.stopPropagation();\"";
			$link = ' task-deadline-date';

			$gridLabel['events'] = [
				'click' => "BX.Tasks.GridActions.onDeadlineChangeClick.bind(BX.Tasks.GridActions, {$taskId}, null, '{$jsDeadline}', event);",
			];
		}

		if ($state['state'])
		{
			$color = mb_strtoupper($state['color']);
			$gridLabel['color'] = constant("Bitrix\Main\Grid\Cell\Label\Color::{$color}");
			$gridLabel['light'] = !$state['fill'];

			return [$gridLabel];
		}

		$link = ($link ?: 'task-deadline-datetime');
		$link .= ' '.self::BXT_SELECTOR;

		return "<span class=\"{$link}\"><span {$onClick}>{$text}</span></span>";
	}

	/**
	 * @return array
	 */
	public function getDeadlineStateData(): array
	{
		$row = $this->getRowData();

		switch ($row['REAL_STATUS'])
		{
			case CTasks::STATE_COMPLETED:
				return [
					'state' => '',
					'color' => '',
					'fill' => true,
				];

			case CTasks::STATE_DEFERRED:
				return [
					'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_DEFERRED'),
					'color' => 'default',
					'fill' => false,
				];

			case CTasks::STATE_SUPPOSEDLY_COMPLETED:
				return [
					'state' => Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DEADLINE_STATE_SUPPOSEDLY_COMPLETED'),
					'color' => 'warning',
					'fill' => false,
				];

			default:
				break;
		}

		$deadline = $row['DEADLINE'];
		if (!$deadline || !($timestamp = $this->getDateTimestamp($deadline)))
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

		$today = (new DateTime())->getTimestamp() + CTimeZone::GetOffset();
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

	private function getCompanyWorkTimeEnd(): int
	{
		if (empty(self::$workTimeSettings))
		{
			self::$workTimeSettings = Calendar::getSettings();
		}

		return (new DateTime())->setTime(
			self::$workTimeSettings['HOURS']['END']['H'],
			self::$workTimeSettings['HOURS']['END']['M'],
			self::$workTimeSettings['HOURS']['END']['S']
		)->getTimestamp();
	}
}