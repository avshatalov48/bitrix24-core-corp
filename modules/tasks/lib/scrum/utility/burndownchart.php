<?php
namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\Localization\Loc;

class BurnDownChart
{
	public function prepareIdealBurnDownChartData(float $sumStoryPoints, SprintRanges $sprintRanges): array
	{
		$idealData = [
			[
				'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_NULL_DAY_LABEL'),
				'idealValue' => $sumStoryPoints
			]
		];

		$averagePointsPerDay = ($sumStoryPoints / count($sprintRanges->getWeekdays()));

		$value = 0;
		foreach ($sprintRanges->getWeekdays() as $dayNumber => $dayTime)
		{
			$realDayNumber = $sprintRanges->getRealDayNumber($dayNumber);
			$value += $averagePointsPerDay;
			$idealData[] = [
				'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_DAY_LABEL').' '.$realDayNumber,
				'idealValue' => round(($sumStoryPoints - $value), 2)
			];
		}

		return $idealData;
	}

	public function prepareRemainBurnDownChartData(
		float $sumStoryPoints,
		SprintRanges $sprintRanges,
		array $completedStoryPointsMap
	): array
	{
		$remainData = [
			[
				'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_NULL_DAY_LABEL'),
				'remainValue' => $sumStoryPoints
			]
		];

		$currentWeekDay = $sprintRanges->getCurrentWeekDay();

		foreach ($completedStoryPointsMap as $dayNumber => $remainStoryPoints)
		{
			if (array_key_exists($dayNumber, $sprintRanges->getWeekdays()))
			{
				$realDayNumber = $sprintRanges->getRealDayNumber($dayNumber);
				$remainData[$realDayNumber] = [
					'day' => Loc::getMessage('TASKS_SCRUM_SPRINT_BURN_DOWN_CHART_DAY_LABEL').' '.$realDayNumber,
					'remainValue' => $remainStoryPoints
				];
			}
			else
			{
				$realDayNumber = $sprintRanges->getRealDayNumber(
					$sprintRanges->getPreviousWeekdayByDayNumber($dayNumber)
				);
				if ($realDayNumber)
				{
					$remainData[$realDayNumber]['remainValue'] = $remainStoryPoints;
				}
			}

			if ($currentWeekDay && $realDayNumber === $currentWeekDay)
			{
				break;
			}
		}

		return array_values($remainData);
	}
}