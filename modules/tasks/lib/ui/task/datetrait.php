<?php

namespace Bitrix\Tasks\UI\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;

trait DateTrait
{
	/**
	 * @param $date
	 * @return string
	 */
	public function formatDate($date): string
	{
		if (!$date)
		{
			return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_DATE_NOT_PRESENT') ?? '';
		}

		$timestamp = $this->getDateTimestamp($date);
		$format = UI::getHumanDateTimeFormat($timestamp);

		return UI::formatDateTime($timestamp, $format);
	}

	/**
	 * @return int
	 */
	protected function getNow(): int
	{
		return (new DateTime())->getTimestamp() + \CTimeZone::GetOffset();
	}

	/**
	 * @param $date
	 * @return int
	 */
	protected function getDateTimestamp($date): int
	{
		$timestamp = MakeTimeStamp($date);

		if ($timestamp === false)
		{
			$timestamp = strtotime($date);
			if ($timestamp !== false)
			{
				$timestamp += \CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
			}
		}

		return $timestamp;
	}

	/**
	 * @param int $timestamp
	 * @return bool
	 */
	public function isExpired(int $timestamp): bool
	{
		return $timestamp && ($timestamp <= $this->getNow());
	}
}