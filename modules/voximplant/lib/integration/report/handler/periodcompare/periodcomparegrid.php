<?php

namespace Bitrix\Voximplant\Integration\Report\Handler\PeriodCompare;

use Bitrix\Report\VisualConstructor\IReportMultipleData;

/**
 * Class PeriodCompareGrid
 * @package Bitrix\Voximplant\Integration\Report\Handler\PeriodCompare
 */
class PeriodCompareGrid extends PeriodCompare implements IReportMultipleData
{
	/**
	 * Converts data from a report handler for a grid
	 *
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		if (!$calculatedData)
		{
			return [];
		}

		$result = [];
		foreach ($calculatedData as $row)
		{
			$currentDate = $row['DATE'];
			$previousDate = $row['PREVIOUS_DATE'];

			$currentUrlParams = [];
			$previousUrlParams = [];

			if (!is_null($row['DATE']))
			{
				$currentDate = $this->getDateForUrl($currentDate);
				$currentUrlParams = $this->getUrlParams($currentDate['start'], $currentDate['finish']);
			}

			if (!is_null($row['PREVIOUS_DATE']))
			{
				$previousDate = $this->getDateForUrl($previousDate);
				$previousUrlParams = $this->getUrlParams($previousDate['start'], $previousDate['finish']);
			}

			$result[] = [
				'value' => [
					'CURRENT_DATE' => $currentDate['date'],
					'PREVIOUS_DATE' => $previousDate['date'],
					'CURRENT_DATE_FORMATTED' => $this->formatDateForGrid($currentDate['date']),
					'PREVIOUS_DATE_FORMATTED' => $this->formatDateForGrid($previousDate['date']),
					'CURRENT_VALUE' => $row['CALL_COUNT'],
					'PREVIOUS_VALUE' => $row['PREVIOUS_CALL_COUNT'],
					'DYNAMICS' => $this->formatPeriodCompare($row['CALL_COUNT_COMPARE']),
				],
				'url' => [
					'CURRENT_DATE' => $this->createUrl(self::TELEPHONY_DETAIL_URI, $currentUrlParams),
					'PREVIOUS_DATE' => $this->createUrl(self::TELEPHONY_DETAIL_URI, $previousUrlParams),
					'CURRENT_VALUE' => $this->createUrl(self::TELEPHONY_DETAIL_URI, $currentUrlParams),
					'PREVIOUS_VALUE' => $this->createUrl(self::TELEPHONY_DETAIL_URI, $previousUrlParams),
				]
			];
		}

		return $result;
	}

	public function getMultipleDemoData()
	{

	}
}