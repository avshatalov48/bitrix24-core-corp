<?php
namespace Bitrix\Tasks\Grid\Project\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Project\Row\Content;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use CTimeZone;

/**
 * Class Date
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Date extends Content
{
	/**
	 * @param $date
	 * @return string
	 */
	public function formatDate($date): string
	{
		if (!$date)
		{
			return Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_DATE_NOT_PRESENT') ?? '';
		}

		$timestamp = $this->getDateTimestamp($date);
		$format = UI::getHumanDateTimeFormat($timestamp);

		return UI::formatDateTime($timestamp, $format);
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
				$timestamp += CTimeZone::GetOffset() - DateTime::createFromTimestamp($timestamp)->getSecondGmt();
			}
		}

		return $timestamp;
	}
}