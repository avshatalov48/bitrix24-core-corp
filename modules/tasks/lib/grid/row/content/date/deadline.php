<?php
namespace Bitrix\Tasks\Grid\Row\Content\Date;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content\Date;
use Bitrix\Tasks\Util\Type\DateTime;
use CTasks;
use CTasksTools;
use CTimeZone;

/**
 * Class Deadline
 *
 * @package Bitrix\Tasks\Grid\Row\Content\Date
 */
class Deadline extends Date
{
	public static function prepare(array $row, array $parameters): string
	{
		$state = static::getDeadlineStateData($row);
		$timestamp = (
			$row['DEADLINE']
				? static::getDateTimestamp($row['DEADLINE'])
				: (new DateTime())->getTimestamp() + CTimeZone::GetOffset()
		);
		$jsDeadline = DateTime::createFromTimestamp($timestamp - CTimeZone::GetOffset());
		$deadline = ($state['state'] ?: static::formatDate($row['DEADLINE']));

		$onClick = '';
		$link = '';
		if ($row['ACTION']['CHANGE_DEADLINE'])
		{
			$taskId = (int)$row['ID'];
			$onClick = "onclick=\"BX.Tasks.GridActions.onDeadlineChangeClick({$taskId}, this, '{$jsDeadline}'); event.stopPropagation();\"";
			$link = ' task-deadline-date ui-label-link';
		}

		if ($state['state'])
		{
			$color = "ui-label-{$state['color']}";
			$fill = ($state['fill'] ? ' ui-label-fill' : '');
			$link = str_replace(' task-deadline-date', '', $link);

			return "<div class=\"ui-label {$color}{$fill}{$link}\" {$onClick}><span class=\"ui-label-inner\">{$deadline}</span></div>";
		}

		$link = str_replace(' ui-label-link', '', $link);
		$link = ($link ?: 'task-deadline-datetime');

		return "<span class=\"{$link}\"><span {$onClick}>{$deadline}</span></span>";
	}

	/**
	 * @param array $row
	 * @return array
	 */
	public static function getDeadlineStateData(array $row): array
	{
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
					'state' => Loc::getMessage('TASKS_GRID_ROW_CONTENT_DEADLINE_STATE_DEFERRED'),
					'color' => 'default',
					'fill' => false,
				];

			case CTasks::STATE_SUPPOSEDLY_COMPLETED:
				return [
					'state' => Loc::getMessage('TASKS_GRID_ROW_CONTENT_DEADLINE_STATE_SUPPOSEDLY_COMPLETED'),
					'color' => 'warning',
					'fill' => false,
				];

			default:
				break;
		}

		$deadline = $row['DEADLINE'];
		if (!$deadline || !($timestamp = static::getDateTimestamp($deadline)))
		{
			return [
				'state' => Loc::getMessage('TASKS_GRID_ROW_CONTENT_DEADLINE_STATE_NO_DEADLINE'),
				'color' => 'light',
				'fill' => true,
			];
		}

		$expiredTime = static::getExpiredTime($timestamp);
		$timeFormat = static::getTimeFormat($timestamp);
		$deadlineTime = ($timeFormat ? ', '.DateTime::createFromTimestamp($timestamp)->format($timeFormat) : '');
		$deadlineDateTime = static::formatDate($deadline);

		$states = [
			'isExpired' => [
				'state' => $expiredTime,
				'color' => 'danger',
				'fill' => true,
			],
			'isToday' => [
				'state' => Loc::getMessage('TASKS_GRID_ROW_CONTENT_DEADLINE_STATE_TODAY', ['#TIME#' => $deadlineTime]),
				'color' => 'warning',
				'fill' => true,
			],
			'isTomorrow' => [
				'state' => Loc::getMessage('TASKS_GRID_ROW_CONTENT_DEADLINE_STATE_TOMORROW', ['#TIME#' => $deadlineTime]),
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
			if (method_exists(__CLASS__, $function) && static::$function($timestamp))
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
	private static function getExpiredTime(int $timestamp): string
	{
		$extensionPrefix = 'TASKS_GRID_ROW_CONTENT_DEADLINE_STATE_EXPIRED_';
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

		$expiredTime = CTasksTools::getMessagePlural(1, $extensionPrefix.'MINUTE', ['#TIME#' => 1]);
		foreach ($extensions as $key => $extension)
		{
			$value = (int)floor($delta / $extension['value']);
			if ($value >= 1)
			{
				$expiredTime = CTasksTools::getMessagePlural($value, $extensionPrefix.$key, ['#TIME#' => $value]);
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
	private static function isToday(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$today = DateTime::createFromTimestamp(static::getNow());

		return static::checkMatchDates($deadline, [$today]);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private static function isTomorrow(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$tomorrow = DateTime::createFromTimestamp(static::getNow());
		$tomorrow->addDay(1);

		return static::checkMatchDates($deadline, [$tomorrow]);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private static function isThisWeek(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$today = DateTime::createFromTimestamp(static::getNow());
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

		return static::checkMatchDates($deadline, $thisWeekDays);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private static function isNextWeek(int $timestamp): bool
	{
		if (!$timestamp)
		{
			return false;
		}

		$deadline = DateTime::createFromTimestamp($timestamp);
		$today = DateTime::createFromTimestamp(static::getNow());
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

		return static::checkMatchDates($deadline, $nextWeekDays);
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	private static function isMoreThatTwoWeeks(int $timestamp): bool
	{
		return true;
	}

	/**
	 * @param DateTime $date
	 * @param array $datesToMatch
	 * @return bool
	 */
	private static function checkMatchDates(DateTime $date, array $datesToMatch): bool
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